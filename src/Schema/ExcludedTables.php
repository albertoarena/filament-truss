<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Schema;

/**
 * The tables Truss keeps out of the diagram, read from its config.
 *
 * **Config, never a payload.** `Truss::payload()` reads the whole schema, and
 * the question here is asked once per button on a resource, so answering it
 * from the two keys that decide it is the difference between a link and a
 * schema read.
 *
 * The merge is Truss's own: the global list plus the list for the connection
 * being drawn. Kept in one place because a second reading of it that drifted
 * would show a button for a table the diagram will not draw.
 */
final class ExcludedTables
{
    public static function contains(string $table, ?string $connection = null): bool
    {
        return in_array($table, self::all($connection), true);
    }

    /**
     * @return list<string>
     */
    public static function all(?string $connection = null): array
    {
        $connection = $connection !== null && $connection !== ''
            ? $connection
            : (string) config('database.default');

        return array_values(array_unique([
            ...(array) config('truss.excluded_tables', []),
            ...(array) config("truss.connections.{$connection}.excluded_tables", []),
        ]));
    }
}
