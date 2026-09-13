<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Illuminate\Support\Facades\Gate;

/**
 * The page's own behaviour. The access rule it delegates to is tested in full
 * in TrussAccessTest; what matters here is that the page actually asks it, and
 * asks it about the panel's user rather than about nobody.
 */
it('hides itself when Truss is switched off', function () {
    app()->detectEnvironment(fn () => 'local');
    config()->set('truss.enabled', false);

    expect(SchemaPage::canAccess())->toBeFalse();
});

it('shows itself in local, where the Truss dashboard is open', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(SchemaPage::canAccess())->toBeTrue();
});

it('asks the gate about the signed-in user, not about a guest', function () {
    // The whole point of delegating: a page that asked without a user would
    // refuse an allow-listed admin, because Laravel turns a guest away before
    // any gate callback runs.
    app()->detectEnvironment(fn () => 'production');
    Gate::define('viewTruss', fn ($user) => $user->email === 'ada@example.com');

    expect(SchemaPage::canAccess())->toBeFalse();

    test()->actingAs(viewer('ada@example.com'));

    expect(SchemaPage::canAccess())->toBeTrue();
});

it('refuses a signed-in user the gate does not allow', function () {
    app()->detectEnvironment(fn () => 'production');
    Gate::define('viewTruss', fn ($user) => false);

    test()->actingAs(viewer('grace@example.com'));

    expect(SchemaPage::canAccess())->toBeFalse();
});

it('carries a navigation label and a slug that read as the feature, not the package', function () {
    // A user scanning a sidebar is looking for the thing, not the vendor.
    expect(SchemaPage::getNavigationLabel())->toBe('Database schema')
        ->and(SchemaPage::getSlug())->toBe('database-schema');
});
