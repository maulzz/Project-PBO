<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\PemilikPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    PemilikPanelProvider::class,
];
