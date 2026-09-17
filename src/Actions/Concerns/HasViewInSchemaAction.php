<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Actions\Concerns;

use AlbertoArena\FilamentTruss\Actions\ViewInSchemaAction;

/**
 * Opts a resource into the diagram link in one line.
 *
 *     class BookResource extends Resource
 *     {
 *         use HasViewInSchemaAction;
 *     }
 *
 * and then `static::viewInSchemaAction()` wherever the panel wants it: a header
 * action, a row action, a group. Put it on a base resource class and every
 * resource extending it is opted in.
 *
 * It asks a resource for `getModel()` and nothing else, so the model to table
 * join lives here once instead of being repeated at every call site.
 */
trait HasViewInSchemaAction
{
    public static function viewInSchemaAction(): ViewInSchemaAction
    {
        return ViewInSchemaAction::make()->forModel(static::getModel());
    }
}
