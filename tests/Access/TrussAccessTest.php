<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Access\TrussAccess;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;

/**
 * The rule under test is parity, not "run the gate": this answers exactly what
 * Truss's own Authorize middleware would answer for the same user, in the same
 * order. Every case below is a case that middleware distinguishes.
 *
 * Getting it wrong is quiet in both directions, which is why it is the first
 * thing in this package to have tests.
 */
it('denies when Truss is switched off, whatever the gate says', function () {
    config()->set('truss.enabled', false);
    Gate::define('viewTruss', fn ($user) => true);

    expect(TrussAccess::allows(viewer()))->toBeFalse();
});

it('denies when Truss is switched off, even in local', function () {
    // The kill switch is checked before the environment, so local does not
    // rescue it. A page ignoring this renders the schema in an application
    // where the operator switched Truss off.
    app()->detectEnvironment(fn () => 'local');
    config()->set('truss.enabled', false);

    expect(TrussAccess::allows())->toBeFalse();
});

it('allows in local without consulting the gate', function () {
    // Truss leaves local unconditionally open. A page that checks only the gate
    // is stricter than the dashboard here, and against the shipped email
    // allow-list a local developer would find the page simply absent.
    app()->detectEnvironment(fn () => 'local');
    Gate::define('viewTruss', fn ($user) => false);

    expect(TrussAccess::allows(viewer()))->toBeTrue();
});

it('defers to the gate outside local when it allows', function () {
    app()->detectEnvironment(fn () => 'production');
    Gate::define('viewTruss', fn ($user) => true);

    expect(TrussAccess::allows(viewer()))->toBeTrue();
});

it('defers to the gate outside local when it denies', function () {
    app()->detectEnvironment(fn () => 'production');
    Gate::define('viewTruss', fn ($user) => false);

    // Asserted against a real viewer rather than a guest on purpose. A guest is
    // refused before any callback runs, so a guest here would pass this test
    // whatever the gate said, and prove nothing about deferring to it.
    expect(TrussAccess::allows(viewer()))->toBeFalse();
});

it('refuses a guest outside local, whatever the gate would say for a user', function () {
    // Laravel refuses a guest unless the ability explicitly admits one, so this
    // is Laravel's behaviour rather than ours. It is pinned because the answer
    // for a guest and the answer for a user are different questions, and this
    // package is built on getting that distinction right.
    app()->detectEnvironment(fn () => 'production');
    Gate::define('viewTruss', fn ($user) => true);

    expect(TrussAccess::allows())->toBeFalse();
});

it('denies outside local when the application binds no gate', function () {
    // An application that binds no Gate cannot answer "may this person view
    // Truss", and outside local the answer has to be no. October CMS is the
    // real-world case: Truss carries its own fix for it.
    app()->detectEnvironment(fn () => 'production');
    app()->offsetUnset(GateContract::class);

    expect(TrussAccess::allows())->toBeFalse();
});

it('defines the Truss gate itself rather than relying on boot', function () {
    // Truss registers its default gate lazily, so an application that never
    // touches a Truss route may never have defined it. Asking the gate without
    // defining it first would deny an allow-listed viewer.
    app()->detectEnvironment(fn () => 'production');
    config()->set('truss.authorization.allowed_emails', ['ada@example.com']);

    expect(TrussAccess::allows(viewer('ada@example.com')))->toBeTrue();
});

it('leaves an allow-list out of it when the host defines its own gate', function () {
    // Truss's defineGate is idempotent and defers to a definition that already
    // exists, so calling it must not override the host. A viewer the host allows
    // gets in even though no allow-list mentions them.
    app()->detectEnvironment(fn () => 'production');
    config()->set('truss.authorization.allowed_emails', []);
    Gate::define('viewTruss', fn ($user) => $user->email === 'grace@example.com');

    expect(TrussAccess::allows(viewer('grace@example.com')))->toBeTrue()
        ->and(TrussAccess::allows(viewer('ada@example.com')))->toBeFalse();
});
