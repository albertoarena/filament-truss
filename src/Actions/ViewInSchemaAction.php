<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Actions;

use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use AlbertoArena\FilamentTruss\Schema\ExcludedTables;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * A link from anywhere in the panel to this table in the diagram.
 *
 * **There is no JavaScript of ours behind it.** Truss reads `focus` from the
 * query string and applies it on load, so the whole feature is a URL: the page,
 * plus the table to focus.
 *
 *     ViewInSchemaAction::make()->forModel(Book::class)
 *
 * It offers itself only when following it would be worth doing, which is three
 * questions rather than one:
 *
 *   - **Is the page here?** A resource can be registered on two panels where
 *     only one of them has this plugin. Building the URL there would throw, so
 *     the guard is the difference between a missing button and a 500.
 *   - **May this viewer see it?** Being allowed to list Books is not being
 *     allowed to read the database structure. The rule is the page's own, which
 *     is Truss's own.
 *   - **Will the diagram draw the table?** Truss ignores `?focus=` for a table
 *     its exclusion list removed, silently and correctly, so a button pointing
 *     at one lands on an unfocused diagram and reads as broken.
 *
 * Any of them answering no removes the button rather than disabling it: there
 * is nothing the viewer could do about it, and a greyed control invites them to
 * try.
 */
class ViewInSchemaAction extends Action
{
    protected string|Closure|null $schemaTable = null;

    public static function getDefaultName(): ?string
    {
        return 'viewInSchema';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (): string => __('filament-truss::schema.focus_action_label'));
        $this->icon(Heroicon::OutlinedCircleStack);
        $this->color('gray');

        // One question answers both: a URL that cannot be built is a button
        // that should not be there, and working it out twice is how the two
        // answers drift apart.
        $this->url(fn (): ?string => $this->getSchemaUrl());
        $this->visible(fn (): bool => $this->getSchemaUrl() !== null);
    }

    /**
     * The table to focus, named outright.
     */
    public function forTable(string|Closure|null $table): static
    {
        $this->schemaTable = $table;

        return $this;
    }

    /**
     * The table to focus, taken from a model the way Eloquent takes it.
     *
     * This is the join a panel actually has: a resource knows its model, a
     * model knows its table, and Truss draws tables. Resolved lazily, so a
     * resource naming a model is not instantiating one to build a menu.
     *
     * @param  class-string<Model>|Closure  $model
     */
    public function forModel(string|Closure $model): static
    {
        return $this->forTable(function () use ($model): string {
            $class = $this->evaluate($model);

            return (new $class)->getTable();
        });
    }

    public function getSchemaTable(): ?string
    {
        $table = $this->evaluate($this->schemaTable);

        return is_string($table) && $table !== '' ? $table : null;
    }

    private function getSchemaUrl(): ?string
    {
        $table = $this->getSchemaTable();

        if ($table === null) {
            return null;
        }

        if (! Filament::getCurrentPanel()?->hasPlugin('filament-truss')) {
            return null;
        }

        if (! SchemaPage::canAccess()) {
            return null;
        }

        if (ExcludedTables::contains($table)) {
            return null;
        }

        return SchemaPage::getUrl(['focus' => $table]);
    }
}
