# Filament Truss

A live **ER diagram (ERD)** of your real database, as a native page inside a
[Filament](https://filamentphp.com) panel. Built on
[Laravel Truss](https://github.com/albertoarena/laravel-truss), which reads the
database you actually have rather than the migrations you think you ran.

**Structure only. No row data is ever read, sent, or rendered.** That is the core
promise of Truss and this package inherits it without exception.

## Status: in development, nothing released

There is no tagged version and nothing to install yet. This repository exists so
the work has a home; treat everything here as subject to change until a `v0.1.0`
tag appears.

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

- PHP 8.2+
- Laravel 12+
- Filament 5+
- `albertoarena/laravel-truss` v1.12.0+, which is where the schema, the diff and
  the structural findings come from

Filament 4 is deliberately not supported. See [`docs/DECISIONS.md`](docs/DECISIONS.md).

## Authorization

**A Filament page does not pass through Truss's own middleware**, so this package
has to ask the same questions itself. The rule it follows is that the page is
visible exactly when the Truss dashboard would be visible to the same user: the
`truss.enabled` switch, then the `viewTruss` gate, and `local` open as Truss
leaves it.

Being allowed into the panel is not the same as being allowed to read the
database structure, and this package never treats it as such.

## Documentation

- [`docs/DESIGN.md`](docs/DESIGN.md): architecture, and how the page gets its data
- [`docs/DECISIONS.md`](docs/DECISIONS.md): why it is built this way
- [`CONTRIBUTING.md`](CONTRIBUTING.md): how to work on it
- [`CHANGELOG.md`](CHANGELOG.md): what changed, once anything has

**The user guide will live at [trussphp.com](https://trussphp.com)**, in a
section of its own alongside the Laravel Truss documentation, rather than being
duplicated here. That site is built from a separate repository,
`albertoarena/laravel-truss-docs`, and it reads from the latest **release**, so
the section appears once there is something installable.

The split is deliberate: this README stays the source of truth for installing and
configuring the package, because that is what Packagist and the plugin directory
render, and the site carries the narrative, the screenshots and the guide.

## Security

Report a vulnerability privately to **hello@albertoarena.it** rather than in a
public issue.

## Credits

- [Alberto Arena](https://github.com/albertoarena)
- Built on [Laravel Truss](https://github.com/albertoarena/laravel-truss)

## License

MIT. See [LICENSE](LICENSE).

The licence covers the code. The project name and brand are separate: see
[TRADEMARK.md](TRADEMARK.md), which defers to the
[Laravel Truss policy](https://github.com/albertoarena/laravel-truss/blob/main/TRADEMARK.md).

**This plugin is not official, endorsed, or affiliated with the Filament
project.** "Filament" is a trademark of its owners.
