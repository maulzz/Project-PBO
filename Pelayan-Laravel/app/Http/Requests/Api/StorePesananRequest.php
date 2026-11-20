<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meja_id' => ['required', 'integer', 'exists:mejas,id'],
            'nama_pengunjung' => ['required', 'string', 'max:255'],
            'metode_pembayaran' => ['required', 'string', 'in:online,kasir'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.catatan' => ['nullable', 'string', 'max:500'],
            'items.*.condiment_ids' => ['nullable', 'array'],
            'items.*.condiment_ids.*' => ['integer', 'exists:condiments,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meja_id.required' => 'Meja wajib dipilih melalui scan QR Code.',
            'nama_pengunjung.required' => 'Nama pengunjung wajib diisi.',
            'metode_pembayaran.in' => 'Metode pembayaran harus online atau kasir.',
            'items.required' => 'Keranjang pesanan tidak boleh kosong.',
            'items.min' => 'Minimal harus memesan 1 item menu.',
        ];
    }
}
