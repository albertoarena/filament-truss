<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Tests;

use AlbertoArena\Truss\TrussServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Truss's provider is loaded because this package's whole job is to answer
     * the same questions it does. Filament's own providers are deliberately not
     * loaded here: nothing tested so far needs a booted panel, and a test that
     * boots one to check a boolean is a slower test that proves less.
     */
    protected function getPackageProviders($app): array
    {
        return [
            TrussServiceProvider::class,
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
