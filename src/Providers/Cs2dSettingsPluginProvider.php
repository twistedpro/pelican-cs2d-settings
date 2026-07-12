<?php

namespace JasonDyer\Cs2dSettings\Providers;

use Illuminate\Support\ServiceProvider;

class Cs2dSettingsPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'cs2dsettings');
    }
}
