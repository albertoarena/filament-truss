<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\FilamentTrussPlugin;
use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Filament\Panel;

it('identifies itself by the package name', function () {
    // The id is how a panel addresses the plugin, so it is a published
    // contract. Changing it breaks every application that configured it.
    expect((new FilamentTrussPlugin)->getId())->toBe('filament-truss');
});

it('registers the schema page on the panel it is added to', function () {
    $panel = Panel::make()->id('registers')->plugin(new FilamentTrussPlugin);

    expect($panel->getPages())->toContain(SchemaPage::class);
});

it('can be made through the static helper, as a panel expects', function () {
    // Filament's convention is `->plugin(Thing::make())`, so the helper is part
    // of the shape rather than sugar.
    expect(FilamentTrussPlugin::make())->toBeInstanceOf(FilamentTrussPlugin::class);
});
