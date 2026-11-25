<?php

namespace App\Providers;

use App\Contracts\LaporanExporter;
use App\Filament\Widgets\PesananNotifier;
use App\Services\Export\SimpleXlsxExporter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LaporanExporter::class, SimpleXlsxExporter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('pesanan-notifier', PesananNotifier::class);
    }
}
