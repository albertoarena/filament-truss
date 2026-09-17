<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Schema\ExcludedTables;

/**
 * Which tables the diagram will not draw, read the way Truss reads it.
 *
 * This exists for one caller: a button offering to focus a table. Truss ignores
 * `?focus=` for a table it excluded, silently and correctly, so a button that
 * points at one does nothing at all and gets reported as a bug.
 *
 * **Read from config, never from a payload.** Asking `Truss::payload()` whether
 * one table is drawable would read the whole schema to answer a question two
 * config keys already answer, once per row of a resource table.
 */
it('knows a table Truss excludes everywhere', function () {
    config()->set('truss.excluded_tables', ['jobs']);

    expect(ExcludedTables::contains('jobs'))->toBeTrue()
        ->and(ExcludedTables::contains('books'))->toBeFalse();
});

it('knows a table excluded for this connection alone', function () {
    // Per-connection exclusions are merged over the global list, exactly as the
    // dashboard payload merges them. A table hidden on the connection the page
    // draws is hidden, whatever the global list says.
    config()->set('truss.excluded_tables', []);
    config()->set('truss.connections.'.config('database.default').'.excluded_tables', ['sessions']);

    expect(ExcludedTables::contains('sessions'))->toBeTrue();
});

it('excludes nothing when nothing is configured', function () {
    config()->set('truss.excluded_tables', []);

    expect(ExcludedTables::contains('books'))->toBeFalse();
});
