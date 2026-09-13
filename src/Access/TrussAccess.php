<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Access;

use AlbertoArena\Truss\TrussServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * May this user see the database structure?
 *
 * **The rule is parity, not "run the gate".** This answers exactly what Truss's
 * own `Authorize` middleware would answer for the same user, in the same order,
 * because a Filament page never passes through that middleware and so gets none
 * of what it does for free.
 *
 * Checking only `viewTruss` would be wrong in both directions, and quietly:
 * too permissive in an application where the operator switched Truss off, and
 * too strict in `local`, where the dashboard is open and a page checking the
 * shipped email allow-list would simply be missing for a local developer.
 *
 * **It is also functionally required.** Truss serves its stylesheet, its
 * JavaScript and Mermaid from gated routes, so a viewer allowed onto a page but
 * refused by the gate gets a blank frame and a set of 404s rather than an
 * honest refusal.
 *
 * Kept deliberately free of Filament: it is a question about Truss, it is the
 * one piece of this package that must not be got wrong, and a plain function of
 * config, environment and gate is testable without booting a panel.
 */
final class TrussAccess
{
    /**
     * @param  Authenticatable|null  $user  the viewer, or null to ask about whoever
     *                                      the gate resolves for the current request
     */
    public static function allows(?Authenticatable $user = null): bool
    {
        // The kill switch, checked before anything else, exactly as the
        // middleware checks it. `local` does not rescue a disabled Truss.
        if (! (bool) config('truss.enabled')) {
            return false;
        }

        // Truss leaves local unconditionally open. Being stricter here would be
        // a difference from the dashboard, not a hardening of it.
        if (app()->environment('local')) {
            return true;
        }

        // An application binding no Gate cannot answer the question, and outside
        // local the answer has to be no. October CMS is the real case: it ships
        // its own authentication and binds no Gate contract.
        if (! app()->bound(GateContract::class)) {
            return false;
        }

        // Truss defines its default gate lazily, so an application that has
        // never served a Truss route may not have defined it yet. Asking without
        // this would deny an allow-listed viewer. Idempotent, and it leaves a
        // host's own definition in charge.
        TrussServiceProvider::defineGate();

        return $user instanceof Authenticatable
            ? Gate::forUser($user)->allows('viewTruss')
            : Gate::allows('viewTruss');
    }
}
