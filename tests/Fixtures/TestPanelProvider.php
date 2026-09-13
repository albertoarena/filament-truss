<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Tests\Fixtures;

use AlbertoArena\FilamentTruss\FilamentTrussPlugin;
use Filament\Panel;
use Filament\PanelProvider;

/**
 * The smallest panel that can host the plugin.
 *
 * Deliberately minimal: no resources, no widgets, no authentication flow. A
 * test that needs a fuller panel should build one rather than growing this,
 * because every option added here is silently applied to every test.
 */
class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('testing')
            ->path('admin')
            ->plugin(new FilamentTrussPlugin);
    }
}
