<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss;

use AlbertoArena\FilamentTruss\Access\TrussAccess;
use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Adds the database schema page to a Filament panel.
 *
 * Registered the usual way:
 *
 *     $panel->plugin(FilamentTrussPlugin::make())
 *
 * The page decides for itself whether a given user may see it, which is why
 * there is nothing to configure here about access. See
 * {@see TrussAccess}.
 */
class FilamentTrussPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-truss';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            SchemaPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Nothing to do at boot. The page pulls its payload when it renders, so
        // there is no state to prepare and nothing that would pay for reading
        // the schema on every panel boot.
    }
}
