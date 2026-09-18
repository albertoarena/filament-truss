<?php

declare(strict_types=1);

use Composer\Semver\Semver;

/**
 * The Truss floor, which is a functional requirement and not housekeeping.
 *
 * This package embeds Truss's frontend in a panel, and a panel is narrower than
 * the window it sits in. Truss's toolbar sized its responsive steps from the
 * viewport until v1.13.1, so a panel sidebar took roughly 470px of a 1280px
 * window, no step fired at the ~810px the bar actually had, and the whole
 * shortfall landed on the one control with no minimum: the Filter field
 * collapsed to an empty square. Every other host of Truss was unaffected, which
 * is exactly why the floor belongs here.
 *
 * Resolution alone does not fix it. A fresh install of `^1.13` takes the patch,
 * but a host whose lock already holds v1.13.0 keeps the broken toolbar until
 * something makes them move, and a constraint that still admits v1.13.0 never
 * will.
 */
function trussConstraint(): string
{
    $composer = json_decode(
        (string) file_get_contents(__DIR__.'/../composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return $composer['require']['albertoarena/laravel-truss'];
}

it('will not resolve a Truss whose toolbar collapses when embedded', function () {
    expect(Semver::satisfies('1.13.0', trussConstraint()))->toBeFalse();
});

it('still takes the Truss line it is written against', function () {
    // The floor moves, the ceiling does not: this is a minimum, not a pin, and
    // a patch or a minor of the same line is still the version this package
    // expects to find.
    expect(Semver::satisfies('1.13.1', trussConstraint()))->toBeTrue()
        ->and(Semver::satisfies('1.14.0', trussConstraint()))->toBeTrue();
});
