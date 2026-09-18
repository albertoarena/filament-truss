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

it('points the documentation link at the guide, tagged so arrivals can be counted', function () {
    // The gap this closes, named by the 18/09 review: the header test asserts
    // the action uses this constant, not that the constant is right, so it
    // passed for the GitHub URL that shipped in front of it for weeks. This is
    // the one launch item CI could not catch, and now it can.
    //
    // **The tag is the point, not decoration.** This link renders inside other
    // people's admin panels, so it is the only signal that says whether the
    // plugin sends anybody to the site. Untagged, those arrivals cannot be told
    // from any other referral.
    expect(FilamentTrussPlugin::PROJECT_URL)
        ->toStartWith('https://trussphp.com/filament/')
        ->toContain('utm_source=filament-panel')
        ->toContain('utm_medium=referral')
        ->toContain('utm_campaign=filament-truss');
});
