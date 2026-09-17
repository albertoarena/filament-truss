<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\FilamentTrussPlugin;
use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Filament\Facades\Filament;

/**
 * What the page says about itself above the diagram.
 *
 * A panel user arriving here has usually not heard of Truss, and an ER diagram
 * with no caption is a picture rather than an answer. The subheading says where
 * the picture comes from and, just as importantly, what it will never contain.
 */
function headerActions(SchemaPage $page): array
{
    // `getHeaderActions()` is protected, which is Filament's shape rather than
    // ours, so this reads it the way Filament's own page lifecycle does.
    $method = new ReflectionMethod($page, 'getHeaderActions');

    return $method->invoke($page);
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('testing'));
});

it('says where the diagram comes from, and what it will never show', function () {
    $subheading = (string) (new SchemaPage)->getSubheading();

    expect($subheading)->not->toBe('')
        ->and(strtolower($subheading))->toContain('structure')
        ->and(strtolower($subheading))->toContain('never');
});

it('names the package that is reading the database', function () {
    // The page reads the whole schema and, until this, named nothing: the only
    // mention of Truss was the label on a link to it. Somebody deciding whether
    // to trust a page like this wants to know what is doing the reading, and the
    // subheading is where they are already looking.
    expect((new SchemaPage)->getSubheading())->toContain('Laravel Truss');
});

it('offers the project as a link away from the panel', function () {
    $actions = headerActions(new SchemaPage);

    expect($actions)->toHaveCount(1)
        ->and($actions[0]->getUrl())->toBe(FilamentTrussPlugin::PROJECT_URL)
        ->and($actions[0]->shouldOpenUrlInNewTab())->toBeTrue();
});

it('can be told not to advertise itself', function () {
    // A plugin that puts a permanent link to its own repository in someone
    // else's admin panel and gives them no way to remove it is a plugin with a
    // billboard in it. On by default because it is genuinely where the
    // documentation lives, off in one call for a panel that would rather not.
    Filament::getCurrentPanel()->getPlugin('filament-truss')->documentationLink(false);

    expect(headerActions(new SchemaPage))->toBe([]);
});
