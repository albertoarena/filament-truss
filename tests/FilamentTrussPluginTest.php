<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\FilamentTrussPlugin;
use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;

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

it('places the page where the panel says, without touching the base page', function () {
    // The page owns its navigation slot: group, sort and icon come from the
    // plugin, the same three knobs other panel plugins offer.
    $panel = Panel::make()->id('placed')->plugin(
        FilamentTrussPlugin::make()->navigationGroup('System')->navigationSort(9)->navigationIcon('heroicon-o-table-cells'),
    );

    Filament::setCurrentPanel($panel);

    expect(SchemaPage::getNavigationGroup())->toBe('System')
        ->and(SchemaPage::getNavigationSort())->toBe(9)
        ->and(SchemaPage::getNavigationIcon())->toBe('heroicon-o-table-cells')
        ->and(Page::getNavigationGroup())->toBeNull();
});

it('keeps a static setter on the page from bleeding into every other page', function () {
    // A static property is shared down the hierarchy unless redeclared. With
    // its own declarations the page can be configured the old way too and
    // the Dashboard stays where it was.
    SchemaPage::navigationGroup('System');
    SchemaPage::navigationSort(9);

    expect(Page::getNavigationGroup())->toBeNull()
        ->and(Page::getNavigationSort())->toBeNull()
        ->and(SchemaPage::getNavigationGroup())->toBe('System');

    SchemaPage::navigationGroup(null);
    SchemaPage::navigationSort(null);
});

it('stays ungrouped when the panel says nothing', function () {
    $panel = Panel::make()->id('ungrouped')->plugin(FilamentTrussPlugin::make());

    Filament::setCurrentPanel($panel);

    expect(SchemaPage::getNavigationGroup())->toBeNull()
        ->and(SchemaPage::getNavigationSort())->toBeNull()
        ->and(SchemaPage::getNavigationIcon())->toBe(Heroicon::OutlinedCircleStack);
});
