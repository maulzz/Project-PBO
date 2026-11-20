<?php

namespace App\Filament\Resources\Pesanans\Pages;

use App\Filament\Resources\Pesanans\PesananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListPesanans extends ListRecords
{
    protected static string $resource = PesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = ['semua' => Tab::make('Semua')];

        foreach (['Pesanan Masuk', 'Diterima', 'Diproses', 'Selesai', 'Dibatalkan'] as $status) {
            $tabs[str($status)->slug()->toString()] = Tab::make($status)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status_pesanan', $status));
        }

        $tabs['belum-lunas'] = Tab::make('Belum Lunas')
            ->icon('heroicon-o-banknotes')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('status_pesanan', '!=', 'Dibatalkan')
                ->where(fn (Builder $q): Builder => $q
                    ->whereDoesntHave('pembayaran')
                    ->orWhereHas('pembayaran', fn (Builder $p): Builder => $p->where('status_pembayaran', '!=', 'lunas'))));

        return $tabs;
    }

    #[On('pesanan-diperbarui')]
    public function muatUlangPesanan(): void
    {
        $this->resetTable();
    }
}
