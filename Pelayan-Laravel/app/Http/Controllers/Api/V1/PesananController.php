<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePesananRequest;
use App\Http\Resources\Api\PesananResource;
use App\Models\Condiment;
use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Services\Pembayaran\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PesananController extends Controller
{
    public function store(StorePesananRequest $request, MidtransService $midtrans): JsonResponse
    {
        $validated = $request->validated();

        $pesanan = DB::transaction(function () use ($validated, $midtrans): Pesanan {
            $menuIds = collect($validated['items'])->pluck('menu_id')->unique();
            $menus = Menu::whereIn('id', $menuIds)->get()->keyBy('id');

            foreach ($validated['items'] as $index => $item) {
                $menu = $menus->get($item['menu_id']);

                if (! $menu || ! $menu->isStokTersedia() || $menu->stok < $item['jumlah']) {
                    throw ValidationException::withMessages([
                        "items.{$index}.menu_id" => "Menu {$menu?->nama_menu} sedang habis atau stok tidak mencukupi.",
                    ]);
                }
            }

            $pesanan = Pesanan::create([
                'nomor_pesanan' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
                'meja_id' => $validated['meja_id'],
                'nama_pengunjung' => $validated['nama_pengunjung'],
                'tanggal_pesanan' => now(),
                'status_pesanan' => 'Pesanan Masuk',
                'total_harga' => 0,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $totalHarga = 0.0;

            foreach ($validated['items'] as $item) {
                /** @var Menu $menu */
                $menu = $menus->get($item['menu_id']);
                $jumlah = (int) $item['jumlah'];
                $hargaSatuan = (float) $menu->harga;

                $condimentIds = $item['condiment_ids'] ?? [];
                $condiments = empty($condimentIds)
                    ? collect()
                    : Condiment::whereIn('id', $condimentIds)
                        ->where('status_ketersediaan', true)
                        ->get();

                $hargaCondiment = (float) $condiments->sum('harga_condiment');
                $subtotal = ($hargaSatuan + $hargaCondiment) * $jumlah;
                $totalHarga += $subtotal;

                $detail = DetailPesanan::create([
                    'pesanan_id' => $pesanan->id,
                    'menu_id' => $menu->id,
                    'harga_satuan' => $hargaSatuan,
                    'jumlah' => $jumlah,
                    'catatan' => $item['catatan'] ?? null,
                    'subtotal' => $subtotal,
                ]);

                if ($condiments->isNotEmpty()) {
                    $pivotData = $condiments->mapWithKeys(fn (Condiment $condiment): array => [
                        $condiment->id => ['harga_condiment' => $condiment->harga_condiment],
                    ])->all();

                    $detail->condiments()->sync($pivotData);
                }
            }

            $pesanan->update(['total_harga' => $totalHarga]);

            $transaksiRef = strtoupper($validated['metode_pembayaran']).'-'.$pesanan->nomor_pesanan;

            if ($validated['metode_pembayaran'] === 'online') {
                $snap = $midtrans->buatTransaksiSnap($pesanan);
                $transaksiRef = $snap['token'];
            }

            Pembayaran::create([
                'pesanan_id' => $pesanan->id,
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'total_bayar' => $totalHarga,
                'status_pembayaran' => 'menunggu',
                'transaksi_ref' => $transaksiRef,
            ]);

            Cache::forget('pesanan:jumlah-masuk');

            return $pesanan;
        });

        $pesanan->load(['meja', 'pembayaran', 'detailPesanans.menu', 'detailPesanans.condiments']);

        return (new PesananResource($pesanan))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $nomorPesanan): PesananResource|JsonResponse
    {
        $pesanan = Pesanan::query()
            ->with(['meja', 'pembayaran', 'detailPesanans.menu', 'detailPesanans.condiments'])
            ->where('nomor_pesanan', $nomorPesanan)
            ->first();

        if (! $pesanan) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        return new PesananResource($pesanan);
    }

    public function cekPembayaran(string $nomorPesanan, MidtransService $midtrans): PesananResource|JsonResponse
    {
        $pesanan = Pesanan::query()
            ->with(['meja', 'pembayaran', 'detailPesanans.menu', 'detailPesanans.condiments'])
            ->where('nomor_pesanan', $nomorPesanan)
            ->first();

        if (! $pesanan) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        if ($pesanan->pembayaran?->metode_pembayaran === 'online' && ! $pesanan->pembayaran->isLunas()) {
            $statusMidtrans = $midtrans->cekStatusTransaksi($pesanan->nomor_pesanan);

            $midtrans->sinkronkanStatusPembayaran(
                $pesanan,
                (string) ($statusMidtrans['transaction_status'] ?? 'pending'),
                $statusMidtrans['fraud_status'] ?? null,
                $statusMidtrans['transaction_id'] ?? null,
            );

            $pesanan->refresh()->load(['meja', 'pembayaran', 'detailPesanans.menu', 'detailPesanans.condiments']);
        }

        return new PesananResource($pesanan);
    }
}
