<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers this package's views under the `filament-truss::` namespace.
 *
 * There is deliberately no config file. Everything this package needs to know
 * about what to show and who may see it already lives in Truss's own config,
 * and a second config file would be a second place for the two to disagree.
 */
class FilamentTrussServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-truss')
            ->hasViews()
            ->hasTranslations()
            ->hasRoute('web');
    }
}
