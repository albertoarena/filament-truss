# Changelog

All notable changes to `filament-truss` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Entries say what changed and what it means for you. The reasoning behind a change
lives in its commit, and the decisions behind a feature in
[`docs/DECISIONS.md`](docs/DECISIONS.md).

## [1.0.1] - 2026-09-18

### Fixed

- The toolbar's **Filter** field no longer collapses to an empty square in a
  panel with an expanded sidebar. Truss sized the toolbar's responsive steps
  from the window until v1.13.1, so a 1280px window that leaves the bar around
  810px fired none of them, and since every other control is floored at its own
  content width the entire shortfall landed on the Filter field. The fix is
  upstream, in Truss, where the steps now follow the bar's own width; what
  changes here is the floor, now `^1.13.1`, so that upgrading this package
  carries the fix rather than leaving it to whenever the host next resolves
  Truss. Nothing in this package's own markup, styles or script changed.

## [1.0.0] - 2026-09-18

### Added

- **The database schema page.** A live ER diagram of the database the panel runs
  on, as a native Filament page at `/database-schema`, built from
  `albertoarena/laravel-truss`. Structure only: tables, columns, indexes and
  keys, and never any row data.
- **Access that matches the Truss dashboard**, in the same order: `truss.enabled`
  first, then `local` open, then the `viewTruss` gate. A Filament page does not
  pass through Truss's middleware, so the page asks the same questions itself. A
  viewer who may see the Truss dashboard may see this page, and one who may not,
  may not.
- **The panel's own theme.** The diagram follows the panel between light and
  dark with no reload, takes its colours from the panel's CSS custom properties,
  and uses the panel's own controls in the toolbar, so a custom panel colour or
  radius is picked up without configuration.
- **The hidden tables Truss hides.** The footer reads `8 of 16 tables` rather
  than presenting a filtered diagram as the whole schema, and where
  `truss.reveal_excluded` allows it, a **Show hidden tables** toggle draws them
  muted. Both switches are Truss's, and this package adds none of its own.
- **A focus link from a resource.** `ViewInSchemaAction` opens the diagram with a
  table already focused, and `HasViewInSchemaAction` opts a resource in with one
  line. The button removes itself when the panel has no such page, when the
  viewer may not see it, or when Truss excludes the table.
- **A link to the project in the page header**, on by default and removable with
  `FilamentTrussPlugin::make()->documentationLink(false)`.

### Requires

- PHP 8.2 or newer, Laravel 12 or newer, Filament 5, and
  `albertoarena/laravel-truss` 1.13 or newer. Filament 4 is deliberately not
  supported.
