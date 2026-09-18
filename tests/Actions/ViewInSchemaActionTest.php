<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Actions\ViewInSchemaAction;
use AlbertoArena\FilamentTruss\Tests\Fixtures\Book;
use Filament\Facades\Filament;
use Filament\Panel;

/**
 * The one feature of this package that needs a panel to be worth anything: a
 * button on a resource that opens the diagram with that resource's table
 * already focused.
 *
 * **There is no JavaScript of ours behind it.** Truss parses `focus` from the
 * query string and applies it on load, so the feature is a link and the whole
 * job here is to build the right URL and to know when not to offer it.
 *
 * Both guards below were learned rather than guessed, and each is a bug report
 * waiting to happen if it is dropped.
 */
beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('testing'));

    // Local is where Truss leaves the dashboard open, so the access rule is out
    // of the way of every test that is not about the access rule.
    app()->detectEnvironment(fn () => 'local');

    config()->set('truss.excluded_tables', []);
});

it('opens the diagram with the table already focused', function () {
    $url = ViewInSchemaAction::make()->forTable('books')->getUrl();

    expect($url)->toContain('database-schema')
        ->and($url)->toContain('focus=books');
});

it('takes the table from the model, the way Eloquent would', function () {
    // The join a panel actually has: a resource knows its model, a model knows
    // its table, and Truss draws tables.
    $url = ViewInSchemaAction::make()->forModel(Book::class)->getUrl();

    expect($url)->toContain('focus=books');
});

it('is offered when the table is drawn and the viewer may look', function () {
    expect(ViewInSchemaAction::make()->forTable('books')->isVisible())->toBeTrue();
});

it('is hidden from a viewer who may not see the page', function () {
    // A user who may list Books is not thereby allowed to read the database
    // structure. Offering the button anyway advertises a page that will refuse
    // them, which is worse than not offering it.
    config()->set('truss.enabled', false);

    expect(ViewInSchemaAction::make()->forTable('books')->isVisible())->toBeFalse();
});

it('is hidden when Truss excludes the table', function () {
    // `?focus=cache` is parsed, found to name no drawn table, and ignored. The
    // button would land on an unfocused diagram and read as broken.
    config()->set('truss.excluded_tables', ['cache']);

    expect(ViewInSchemaAction::make()->forTable('cache')->isVisible())->toBeFalse();
});

it('is hidden when the table is excluded for this connection alone', function () {
    config()->set('truss.connections.'.config('database.default').'.excluded_tables', ['sessions']);

    expect(ViewInSchemaAction::make()->forTable('sessions')->isVisible())->toBeFalse();
});

it('is hidden when nothing told it which table to focus', function () {
    expect(ViewInSchemaAction::make()->isVisible())->toBeFalse();
});

it('is hidden in a panel that does not have this page', function () {
    // A resource shared between two panels, only one of which registered the
    // plugin. Without this the button is rendered and building its URL throws,
    // so the guard is the difference between a missing button and a 500.
    Filament::setCurrentPanel(Panel::make()->id('bare')->path('bare'));

    expect(ViewInSchemaAction::make()->forTable('books')->isVisible())->toBeFalse();
});

it('is labelled as what it does, not as the package', function () {
    expect(ViewInSchemaAction::make()->forTable('books')->getLabel())
        ->toBe(__('filament-truss::schema.focus_action_label'));
});
