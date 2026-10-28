<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Where the page sits in the panel's sidebar.
 *
 * Filament's navigation setters write `static::$property`, and a static
 * property belongs to the class that declares it. Until the page declared its
 * own, `SchemaPage::navigationGroup('System')` wrote `Filament\Pages\Page`
 * itself and moved the Dashboard, and every other page that does not declare
 * the property, into that group as well. Reported in PR #5.
 */
beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('testing'));
});

afterEach(function () {
    // Statics outlive the application between tests, so a failed assertion
    // must not leave a group behind for the next test to read.
    SchemaPage::navigationGroup(null);
    SchemaPage::navigationSort(null);
    SchemaPage::navigationParentItem(null);
});

it('keeps the static setters to the schema page', function () {
    SchemaPage::navigationGroup('System');
    SchemaPage::navigationSort(9);
    SchemaPage::navigationParentItem('Settings');

    expect(SchemaPage::getNavigationGroup())->toBe('System')
        ->and(SchemaPage::getNavigationSort())->toBe(9)
        ->and(SchemaPage::getNavigationParentItem())->toBe('Settings')
        ->and(Page::getNavigationGroup())->toBeNull()
        ->and(Page::getNavigationSort())->toBeNull()
        ->and(Page::getNavigationParentItem())->toBeNull()
        ->and(Dashboard::getNavigationGroup())->toBeNull();
});

it('takes its group, sort and icon from the plugin', function () {
    Filament::getCurrentPanel()->getPlugin('filament-truss')
        ->navigationGroup('System')
        ->navigationSort(9)
        ->navigationIcon(Heroicon::OutlinedTableCells);

    expect(SchemaPage::getNavigationGroup())->toBe('System')
        ->and(SchemaPage::getNavigationSort())->toBe(9)
        ->and(SchemaPage::getNavigationIcon())->toBe(Heroicon::OutlinedTableCells)
        ->and(Dashboard::getNavigationGroup())->toBeNull();
});

it('carries the plugin placement into the navigation item itself', function () {
    // The getters are what Filament reads, but the item is what the sidebar
    // draws, so this checks the value arrives where it is used.
    Filament::getCurrentPanel()->getPlugin('filament-truss')
        ->navigationGroup('System')
        ->navigationSort(9);

    $item = SchemaPage::getNavigationItems()[0];

    expect($item->getGroup())->toBe('System')
        ->and($item->getSort())->toBe(9);
});

it('evaluates closures given to the plugin', function () {
    Filament::getCurrentPanel()->getPlugin('filament-truss')
        ->navigationGroup(fn (): string => 'System')
        ->navigationSort(fn (): int => 9)
        ->navigationIcon(fn (): string => 'heroicon-o-table-cells');

    expect(SchemaPage::getNavigationGroup())->toBe('System')
        ->and(SchemaPage::getNavigationSort())->toBe(9)
        ->and(SchemaPage::getNavigationIcon())->toBe('heroicon-o-table-cells');
});

it('prefers the plugin over the static setters', function () {
    SchemaPage::navigationGroup('Static');

    Filament::getCurrentPanel()->getPlugin('filament-truss')->navigationGroup('Plugin');

    expect(SchemaPage::getNavigationGroup())->toBe('Plugin');
});

it('stays ungrouped with its own icon when nothing is configured', function () {
    expect(SchemaPage::getNavigationGroup())->toBeNull()
        ->and(SchemaPage::getNavigationSort())->toBeNull()
        ->and(SchemaPage::getNavigationIcon())->toBe(Heroicon::OutlinedCircleStack);
});

it('keeps its defaults when rendered outside a panel', function () {
    // Navigation can be asked for with no current panel, and a page that
    // cannot find its plugin should fall back rather than fail.
    Filament::setCurrentPanel(null);

    expect(SchemaPage::getNavigationGroup())->toBeNull()
        ->and(SchemaPage::getNavigationIcon())->toBe(Heroicon::OutlinedCircleStack);
});
