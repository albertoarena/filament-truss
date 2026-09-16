<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Pages;

use AlbertoArena\FilamentTruss\Access\TrussAccess;
use AlbertoArena\FilamentTruss\FilamentTrussPlugin;
use AlbertoArena\Truss\Facades\Truss;
use BackedEnum;
use Filament\Actions\Action;
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

    /**
     * Everything the diagram partial needs, assembled in PHP.
     *
     * **The payload is fetched here and handed to the browser in the page.**
     * We are already inside the application, so asking it for its own schema
     * over HTTP would be a round trip for an array we are holding.
     *
     * The config values are read from Truss's own config rather than from
     * anything of ours, so a panel and the Truss dashboard draw the same
     * diagram from the same settings.
     *
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return [
            'payload' => Truss::payload(),
            // One connection, and the switcher stays hidden. Truss reads an
            // embedded payload once, on first load, and documents a connection
            // switch on such a page as the host's to handle, since only the host
            // can produce the other connection's payload. Offering a switcher
            // that cannot switch would be worse than not offering one.
            'connections' => [],
            'typeLabels' => config('truss.diagram.type_labels'),
            'warnAbove' => config('truss.large_schema.warn_above'),
            'focusDepth' => config('truss.focus.default_depth'),
            'minZoom' => config('truss.diagram.min_zoom'),
            'flagTables' => config('truss.doctor.flag_tables', true),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-truss::schema.title');
    }

    /**
     * What the diagram is, said before anyone has to work it out.
     *
     * A panel user arriving here has usually not heard of Truss, and an ER
     * diagram with no caption is a picture rather than an answer. It also
     * carries the promise, where the person who would most want to know it is
     * actually looking.
     */
    public function getSubheading(): string
    {
        return __('filament-truss::schema.subheading');
    }

    /**
     * The link to the project, when the panel has not turned it off.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        if (! $this->plugin()?->hasDocumentationLink()) {
            return [];
        }

        return [
            Action::make('documentation')
                ->label(__('filament-truss::schema.documentation_label'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->link()
                ->url(FilamentTrussPlugin::PROJECT_URL)
                ->openUrlInNewTab(),
        ];
    }

    /**
     * This package's plugin on the panel being rendered, if there is one.
     *
     * Null rather than a throw: the diagram partial is rendered on its own in
     * the suite, with no panel around it, and a page that cannot find its own
     * plugin should show one thing less rather than fail.
     */
    private function plugin(): ?FilamentTrussPlugin
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel?->hasPlugin('filament-truss')) {
            return null;
        }

        $plugin = $panel->getPlugin('filament-truss');

        return $plugin instanceof FilamentTrussPlugin ? $plugin : null;
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
