# Filament Truss

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/albertoarena/filament-truss/main/art/filamentphp/image-dark.jpg" class="filament-hidden">
  <img src="https://raw.githubusercontent.com/albertoarena/filament-truss/main/art/filamentphp/image-light.jpg" alt="Filament Truss" class="filament-hidden">
</picture>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/albertoarena/filament-truss.svg)](https://packagist.org/packages/albertoarena/filament-truss)
[![Tests](https://github.com/albertoarena/filament-truss/actions/workflows/run-tests.yml/badge.svg)](https://github.com/albertoarena/filament-truss/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/albertoarena/filament-truss.svg)](https://packagist.org/packages/albertoarena/filament-truss)

> **Not released yet.** The first tag will be `v1.0.0`, and until it exists
> `composer require` will not find this package. Everything below describes what
> that release installs.

A live **ER diagram (ERD)** of your real database, as a native page inside a
[Filament](https://filamentphp.com) panel. Built on
[Laravel Truss](https://github.com/albertoarena/laravel-truss), which reads the
database you actually have rather than the migrations you think you ran.

**Structure only. No row data is ever read, sent, or rendered.** That is the core
promise of Truss and this package inherits it without exception.

[![Read the docs](https://img.shields.io/badge/Read%20the%20docs-f59e0b?style=for-the-badge)](https://trussphp.com/filament/?utm_source=github&utm_medium=readme&utm_campaign=filament-truss)

## What it is, and what it is not

**It is a Filament page**, built from the panel's own components and styled by
the panel's own theme, so it looks like part of the admin rather than a visitor
in it.

**It is not the Truss dashboard in an iframe.** That was considered and rejected:
a frame around another page inherits none of the panel's chrome, breaks its dark
mode, and reads as a bolted-on tab. If that is all you want, Truss already ships
a dashboard and you can link to it.

**It is read-only.** It exposes nothing Truss does not already expose, and it
gains no write path just because it lives inside an admin panel.

## Requirements

- PHP `^8.2`
- Laravel `^12.0 | ^13.0`
- Filament `^5.0`
- `albertoarena/laravel-truss` `^1.13`, which is where the schema, the diff and
  the structural findings come from

Filament 4 is deliberately not supported. See [`docs/DECISIONS.md`](https://github.com/albertoarena/filament-truss/blob/main/docs/DECISIONS.md).

## Installation

```bash
composer require albertoarena/filament-truss
```

Laravel Truss comes with it as a regular dependency, so there is nothing else to
install and no asset to publish.

Register the plugin on your panel:

```php
use AlbertoArena\FilamentTruss\FilamentTrussPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(FilamentTrussPlugin::make());
    }
}
```

The page appears in the panel navigation as **Database schema**, at
`/database-schema` under your panel's path. There is nothing to configure for the
diagram itself: it follows Truss's config and the panel's theme.

## Who can see the page

**A Filament page does not pass through Truss's own middleware**, so this package
asks the same questions itself. The rule is that the page is visible exactly when
the Truss dashboard would be visible to the same user, in the same order:

1. `truss.enabled`, which defaults to **on in `local` and off everywhere else**.
   Set `TRUSS_ENABLED=true` to run it anywhere else.
2. In `local`, that is all: Truss leaves the dashboard open there and so does
   this page.
3. Everywhere else, the `viewTruss` gate. Truss ships a default that admits the
   emails in `TRUSS_ALLOWED_EMAILS`, and an application can define its own gate
   instead:

   ```php
   Gate::define('viewTruss', fn ($user) => $user->isAdmin());
   ```

Being allowed into the panel is not the same as being allowed to read the
database structure, and this package never treats it as such. Both switches are
Truss's, so a viewer who can see the Truss dashboard can see this page, and a
viewer who cannot, cannot.

## Open the diagram on a table

A resource can offer a button that opens the diagram with **its own table already
focused**. Add the trait to the resource and put the action where you want it:

```php
use AlbertoArena\FilamentTruss\Actions\Concerns\HasViewInSchemaAction;
use Filament\Resources\Resource;

class BookResource extends Resource
{
    use HasViewInSchemaAction;

    // ...
}
```

```php
// In a page's header, a table's row actions, or anywhere else that takes an action
BookResource::viewInSchemaAction();
```

Put the trait on a base resource class and every resource extending it is opted
in. Or skip the trait and name the table yourself:

```php
use AlbertoArena\FilamentTruss\Actions\ViewInSchemaAction;

ViewInSchemaAction::make()->forModel(Book::class);
ViewInSchemaAction::make()->forTable('books');
```

**The button removes itself rather than disabling itself** when the panel has no
such page, when the viewer fails the check above, or when the table is one Truss
excludes from the diagram. In each case there is nothing the viewer could do
about it, and a greyed control invites them to try.

## Hidden tables

Truss keeps framework plumbing out of the diagram through its own
`truss.excluded_tables`, so a panel on 16 tables may draw 8 of them. The footer
says so (`8 of 16 tables`), and where `truss.reveal_excluded` allows it, a **Show
hidden tables** toggle draws them muted. That is on by default in `local` and off
elsewhere, matching `truss.enabled` and the `viewTruss` gate.

**Both switches live in Truss's config and this package adds none of its own.**
So the toggle being absent in production is a setting rather than a bug, and
excluding a table to keep it off a shared panel keeps working: there is no query
parameter and no plugin option that puts it back.

## Configuration

The diagram reads Truss's config, which is the single place to change what is
drawn. Publish it if you have not already:

```bash
php artisan vendor:publish --tag=truss-config
```

The one option this package adds is the link to the project in the page header,
which is on by default and off in one call:

```php
$panel->plugin(FilamentTrussPlugin::make()->documentationLink(false));
```

## Documentation

**The user guide lives at [trussphp.com](https://trussphp.com/filament/?utm_source=github&utm_medium=readme&utm_campaign=filament-truss)**, in a section of
its own alongside the Laravel Truss documentation. This README stays the source
of truth for installing and configuring the package, because it is what Packagist
and the plugin directory render, and the site carries the narrative, the
screenshots and the guide.

In this repository:

- [`docs/DESIGN.md`](https://github.com/albertoarena/filament-truss/blob/main/docs/DESIGN.md): architecture, and how the page gets its data
- [`docs/DECISIONS.md`](https://github.com/albertoarena/filament-truss/blob/main/docs/DECISIONS.md): why it is built this way
- [`CONTRIBUTING.md`](https://github.com/albertoarena/filament-truss/blob/main/CONTRIBUTING.md): how to work on it
- [`CHANGELOG.md`](https://github.com/albertoarena/filament-truss/blob/main/CHANGELOG.md): what changed

## Testing

```bash
composer test   # Pest
composer lint   # Laravel Pint
npm test        # Vitest, over the client-side files
```

Rendering and interaction in a real panel are covered by hand, against
[`docs/MANUAL-TESTS.md`](https://github.com/albertoarena/filament-truss/blob/main/docs/MANUAL-TESTS.md), because neither suite has a panel
to render into.

## Changelog

See [CHANGELOG.md](https://github.com/albertoarena/filament-truss/blob/main/CHANGELOG.md) for recent changes.

## Contributing

See [CONTRIBUTING.md](https://github.com/albertoarena/filament-truss/blob/main/CONTRIBUTING.md).

## Security

Report a vulnerability privately to **hello@albertoarena.it** rather than in a
public issue.

## Related packages

- [albertoarena/laravel-truss](https://github.com/albertoarena/laravel-truss) is
  the engine: the schema reader, the diagram, the diff and the structural doctor,
  with a dashboard and a CLI of its own.
- [albertoarena/filament-event-sourcing](https://github.com/albertoarena/filament-event-sourcing)
  brings `spatie/laravel-event-sourcing` into a Filament panel.

## 📬 Stay updated

Practical notes on Laravel, database tooling and AI-assisted development, roughly
once a month. No spam.

**[Subscribe →](https://albertoarena.it/subscribe/?utm_source=github&utm_medium=readme&utm_campaign=newsletter&utm_content=filament-truss)**

## Credits

- [Alberto Arena](https://github.com/albertoarena)
- Built on [Laravel Truss](https://github.com/albertoarena/laravel-truss)

## License

MIT. See [LICENSE](https://github.com/albertoarena/filament-truss/blob/main/LICENSE).

The licence covers the code. The project name and brand are separate: see
[TRADEMARK.md](https://github.com/albertoarena/filament-truss/blob/main/TRADEMARK.md), which defers to the
[Laravel Truss policy](https://github.com/albertoarena/laravel-truss/blob/main/TRADEMARK.md).

**This plugin is not official, endorsed, or affiliated with the Filament
project.** "Filament" is a trademark of its owners.
