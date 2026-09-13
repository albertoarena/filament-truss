<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Tests;

use AlbertoArena\FilamentTruss\FilamentTrussServiceProvider;
use AlbertoArena\FilamentTruss\Tests\Fixtures\TestPanelProvider;
use AlbertoArena\Truss\TrussServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Truss's provider is loaded because this package's whole job is to answer
     * the same questions it does, and Filament's so a panel can boot.
     *
     * The test panel is registered for every test rather than only where it is
     * needed. Filament resolves the *default* panel to answer questions as
     * ordinary as "who is signed in", so a page test without one fails on
     * NoDefaultPanelSetException long before it reaches what it meant to assert.
     * One page and no resources is cheap enough that opting in per file would
     * buy nothing but a sharp edge.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            TrussServiceProvider::class,
            FilamentTrussServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Truss is switched on by default here so a test that cares about the
        // kill switch has to say so, and a test that does not is never silently
        // passing because everything was off.
        $app['config']->set('truss.enabled', true);
    }
}
