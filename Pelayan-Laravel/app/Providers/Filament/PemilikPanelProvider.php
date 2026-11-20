<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pemilik\Widgets\PemilikStatsOverview;
use App\Filament\Widgets\MenuTerlarisChart;
use App\Filament\Widgets\PendapatanChart;
use App\Filament\Widgets\StatusPesananChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PemilikPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pemilik')
            ->path('pemilik')
            ->login(Login::class)
            ->brandName('Cafe Pemilik')
            ->colors([
                'primary' => Color::Orange,
            ])
            ->darkMode(true)
            ->spa(hasPrefetching: true)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Pemantauan Transaksi',
                'Pemantauan Stok',
            ])
            ->discoverResources(in: app_path('Filament/Pemilik/Resources'), for: 'App\\Filament\\Pemilik\\Resources')
            ->discoverPages(in: app_path('Filament/Pemilik/Pages'), for: 'App\\Filament\\Pemilik\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                PemilikStatsOverview::class,
                PendapatanChart::class,
                StatusPesananChart::class,
                MenuTerlarisChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
