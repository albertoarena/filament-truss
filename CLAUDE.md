# CLAUDE.md: instructions for Claude Code

## Project Overview

**Package:** `albertoarena/filament-truss`
**Type:** Filament plugin (Laravel Composer package)
**Purpose:** A live ER diagram of the real database as a native page inside a
Filament panel, built on `albertoarena/laravel-truss`. Structure only, no data is
ever exposed.
**License:** MIT
**Status:** In development. Nothing is released; there is no `v0.1.0` yet.

## Stack

- PHP 8.2+ (matching Truss, which matches Laravel 12)
- Laravel 12+
- Filament 5+ (**never target Filament 4**, see `docs/DECISIONS.md`)
- `albertoarena/laravel-truss` v1.12.0+ for the schema, the diff and the findings
- Pest for testing
- Mermaid for diagram rendering, which comes from Truss rather than from here

## Commands

- `composer test`: run the Pest suite
- `composer lint` / `composer lint:fix`: Laravel Pint
- `npm test`: run the Vitest suite over the two client-side files (`npm install`
  first; there is no build step, so the tests read what the browser is served)

Rendering and interaction in a real panel cannot be covered by either suite,
because neither has a panel to render into. `docs/MANUAL-TESTS.md` is the
checklist that stands in for a browser test, and it is a release gate.

## Conventions (always true)

- **TDD is mandatory, and it is an ordering rule, not just a coverage rule.** For
  every change (feature, fix, refactor), in this order: (1) write the test,
  (2) run it and watch it fail for the right reason (red), (3) write the
  implementation to make it pass (green). Writing production code before its test
  is a violation *even if* a passing test is added right afterwards: the test
  must exist and fail first. Never commit implementation without a corresponding
  test. No page, component, class, or function is written before the test that
  drives it. PHP uses Pest. Any client-side code added here is tested the way
  Truss tests its own: unit tests for pure logic, a browser test for rendering
  and interaction.
- **No data exposed, ever.** Only table, column, index, and foreign key
  structure. Never row contents. This is the core promise of Truss and this
  package inherits it as a hard constraint, not a config default. The boundary is
  the `CREATE TABLE` definition vs. table rows: column defaults count as
  structure and are in scope. **Living inside an admin panel does not relax
  this**, and this package never gains a write path because of where it renders.
- **Authorization parity is the rule, not "check the gate".** The page is visible
  exactly when the Truss dashboard would be visible to the same user, in the same
  order: `truss.enabled`, then `viewTruss`, with `local` open. Checking only the
  gate is both too permissive (when Truss is off) and too strict (in `local`).
  It is also functionally required, because Truss's asset routes are gated too:
  a viewer who fails the gate gets a page whose assets 404.
- **Depend on the Truss facade, never on its internals.** `Truss::payload()` is
  public API and carries a compatibility promise. `DoctorReport`, `SchemaDiffer`,
  `BaselineStore` and `DashboardPayload` do not. An integration built on
  internals makes every Truss refactor a breaking change for our users.
- **Never send Truss a pull request from this repository's work.** If something
  is missing upstream, raise it there and let a session working in that repo do
  it. Upstream changes have their own conventions and their own reviewer.
- **Truss stays Filament-free.** Nothing in this package may require a change
  that gives Truss a Filament dependency. That separation is the whole reason
  there are two packages.
- **Git commits:** `type: short subject` (max 50 chars), then a body paragraph
  explaining what and why, not how. Never include "Generated with Claude Code" or
  "Co-Authored-By: Claude". Use a heredoc for multi-line commit messages. The
  same rule covers every outward-facing artefact, not only commits: pull request
  titles and bodies, issue comments, release notes and any prose.
- **No em dashes or en dashes** anywhere: code comments, docs, commit messages.
  Use commas, colons, parentheses or separate sentences.
- **Docs stay in sync.** Any change to commands, config, or user-facing behaviour
  must be reflected in `README.md` and `docs/` in the same change. The public
  docs site is a separate repo, `albertoarena/laravel-truss-docs` (published at
  trussphp.com); **this package gets its own section there, and that section must
  be maintained alongside this repository**, not left to drift. The site reads
  from the latest **release**, so an update to it lands when the next release
  ships and the site rebuilds. Docs-site work is done in that repository, with
  its conventions and its own checks.

## Pointers

- Architecture and how the page gets its data: `docs/DESIGN.md`
- Decision log, including what was rejected and why: `docs/DECISIONS.md`
- What has to be checked by hand, and why: `docs/MANUAL-TESTS.md`

This file should stay short enough to read in under a minute. If you are about to
add detail, it probably belongs in `docs/` with a pointer added here.
