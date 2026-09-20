# Security Policy

## Supported versions

The latest released `1.x` line receives security fixes.

## Reporting a vulnerability

Please do not open a public issue for security problems. Use either channel
below, with a description of the issue and a way to reproduce it:

- **[Report a vulnerability](https://github.com/albertoarena/filament-truss/security/advisories/new)**
  on GitHub, which is private, keeps the report attached to this repository and
  gives you the advisory thread to follow the fix in. Preferred.
- Email the maintainer at hello@albertoarena.it, if you would rather not go
  through GitHub.

You can expect an acknowledgement within a few days, and a fix or mitigation plan
once the report is confirmed.

## Scope

This package puts a Truss ER diagram on a page inside a Filament panel. It
exposes **database structure only**: tables, columns, indexes and foreign keys,
never row contents. It is read-only, and living inside an admin panel gives it
no write path.

Two properties are worth knowing before judging whether something is a
vulnerability here.

**The structure payload is embedded in the rendered page**, rather than fetched
from an endpoint the browser calls afterwards. There is no second request to
gate, so whoever can render the page has the whole payload. The check that
decides whether the page renders is therefore the entire boundary, and not one
layer of several.

**That check is parity with Truss, not a gate call.** A Filament page never
passes through Truss's own middleware, so this package asks the same questions
in the same order: `truss.enabled` first, then `local` open, then the `viewTruss`
gate. Being allowed into the panel is not being allowed to read the database
structure, and this package does not treat it as such.

In scope:

- Row data, or anything derived from row contents, reaching the page. Column
  defaults and table comments are structure and are in scope by design; actual
  rows are the line.
- A viewer seeing the page when the rule above says they should not: Truss
  disabled, or the `viewTruss` gate refusing them outside `local`.
- The **View in schema** action revealing something to a viewer who fails that
  same check, or naming a table that `truss.excluded_tables` keeps out of the
  diagram. The action removes itself rather than disabling itself precisely so
  it carries no information to someone who cannot use it.
- A way to draw an excluded table without `truss.reveal_excluded` allowing it.
  Excluding a table is how an operator keeps it off a shared panel, and there is
  deliberately no query parameter and no plugin option that puts it back.
- The asset route serving anything other than this package's two static files.
  It matches an allow-list of exact names and never builds a path from input, so
  a traversal would be a defect in that claim rather than a missing defence.

Not in scope:

- `local` being open to anyone who reaches the panel. Truss leaves its dashboard
  open in `local` and this page matches it. Being stricter here would be a
  difference from Truss rather than a hardening of it.
- An application that sets `TRUSS_ENABLED=true` outside `local` and defines no
  `viewTruss` gate of its own, or defines one that admits everyone. Both
  switches are Truss's and belong to the operator.
- Row data visible elsewhere in the panel. This page renders none, and what a
  resource or a record page shows is the application's own concern.

Issues in [Laravel Truss](https://github.com/albertoarena/laravel-truss) itself,
which is where the schema reading, the diff and the diagram come from, belong to
that repository's security policy. Mention them here as well if they surface
through this page, so the page can be checked alongside the fix.
