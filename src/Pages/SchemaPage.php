<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Pages;

use AlbertoArena\FilamentTruss\Access\TrussAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * The database structure, drawn inside the panel.
 *
 * Structure only: tables, columns, indexes and foreign keys. No row data is
 * read to build this page and none can be reached from it.
 */
class SchemaPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $slug = 'database-schema';

    protected string $view = 'filament-truss::pages.schema';

    public static function getNavigationLabel(): string
    {
        return __('filament-truss::schema.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-truss::schema.title');
    }

    /**
     * Visible exactly when the Truss dashboard would be visible to this user.
     *
     * **Asked about the panel's own user**, resolved through Filament rather
     * than the default guard, because a panel may authenticate against a guard
     * of its own and the gate must be asked about the person actually looking.
     *
     * A guest is passed as null deliberately: Laravel refuses a guest before
     * any gate callback runs, which is the correct answer and not one worth
     * second-guessing here.
     */
    public static function canAccess(): bool
    {
        return TrussAccess::allows(Filament::auth()->user());
    }
}
