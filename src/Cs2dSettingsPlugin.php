<?php

namespace JasonDyer\Cs2dSettings;

use Filament\Contracts\Plugin;
use Filament\Panel;

class Cs2dSettingsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'cs2dsettings';
    }

    public function register(Panel $panel): void
    {
        foreach ($panel->getId() === 'server' ? ['Server'] : [] as $id) {
            $panel->discoverPages(
                plugin_path($this->getId(), "src/Filament/$id/Pages"),
                "JasonDyer\\Cs2dSettings\\Filament\\$id\\Pages"
            );
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
