<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss;

use AlbertoArena\FilamentTruss\Access\TrussAccess;
use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Adds the database schema page to a Filament panel.
 *
 * Registered the usual way:
 *
 *     $panel->plugin(FilamentTrussPlugin::make())
 *
 * The page decides for itself whether a given user may see it, which is why
 * there is nothing to configure here about access. See
 * {@see TrussAccess}.
 */
class FilamentTrussPlugin implements Plugin
{
    /**
     * Where the documentation lives, which is where the page header points.
     *
     * **Tagged deliberately.** This link renders inside other people's admin
     * panels, so it is the only signal that says whether the plugin sends
     * anybody to the site: untagged, those arrivals cannot be told from any
     * other referral. `filament-panel` as the source is what makes them
     * countable.
     */
    public const PROJECT_URL = 'https://trussphp.com/filament/?utm_source=filament-panel&utm_medium=referral&utm_campaign=filament-truss';

    protected bool $hasDocumentationLink = true;

    public function getId(): string
    {
        return 'filament-truss';
    }

    /**
     * Whether the page offers a link to the project.
     *
     * On by default, because it is where a developer looking at this page goes
     * next. Off in one call, because a plugin that puts a permanent link to its
     * own repository in someone else's admin panel and gives them no way to
     * remove it is a plugin with a billboard in it.
     */
    public function documentationLink(bool $condition = true): static
    {
        $this->hasDocumentationLink = $condition;

        return $this;
    }

    public function hasDocumentationLink(): bool
    {
        return $this->hasDocumentationLink;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            SchemaPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Nothing to do at boot. The page pulls its payload when it renders, so
        // there is no state to prepare and nothing that would pay for reading
        // the schema on every panel boot.
    }
}
