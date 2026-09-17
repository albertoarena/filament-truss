<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A model to derive a table name from, and nothing else.
 *
 * `$table` is deliberately left unset: the join this package relies on is
 * Eloquent's own convention, and a fixture that spells the table out would test
 * the fixture rather than the join.
 */
class Book extends Model
{
    //
}
