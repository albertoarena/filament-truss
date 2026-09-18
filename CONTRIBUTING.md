# Contributing

Thanks for considering a contribution.

**The package is released and on Packagist**, so `main` is what people have
already installed. It is a protected branch: work lands through a pull request,
every check has to pass, and [`CHANGELOG.md`](CHANGELOG.md) records what each
release changed.

An issue is still the best place to start anything larger than a fix. A question
costs nothing, and it is cheaper than a pull request that has to be turned down.

## Workflow

1. Fork the repository and create a branch from `main`.
2. Write a failing test first, then make it pass. Every change is test driven,
   and that is an ordering rule: the test must exist and fail before the code
   that satisfies it. **This covers the stylesheet and the script as much as the
   PHP**: they are behaviour, they are shipped, and Vitest tests them.
3. Keep the public API stable. Anything in `src/` not marked `@internal` is a
   published contract.
4. Keep `README.md` and `docs/` in sync whenever you change behaviour, config or
   commands. The user guide lives at trussphp.com, built from a separate
   repository (`albertoarena/laravel-truss-docs`), and the section covering this
   package is maintained there in step. You are not expected to update that site
   in your pull request; say in the description if something there will need to
   change.
5. Run the checks below before opening a pull request.

## Checks

```bash
composer test   # Pest
composer lint   # Laravel Pint, code style
npm test        # Vitest, the client-side files
```

`npm ci` first for that last one, which installs the locked tree rather than
resolving a new one. There is no build step: the files under
`resources/dist` are what the browser is served, and the Vitest suite reads them
from disk for that reason.

Rendering and interaction in a real panel are not covered by either suite, since
neither has a Filament panel to render into. They are covered by the checklist in
[`docs/MANUAL-TESTS.md`](docs/MANUAL-TESTS.md), which is worth a pass before any
release.

## Conventions

- Strict types in every PHP file.
- **Structure only.** This package renders table, column, index and foreign key
  structure, never row data. Living inside an admin panel does not relax it, and
  the package has no write path.
- **Authorization parity.** The page is visible exactly when the Truss dashboard
  would be visible to the same user: the `truss.enabled` switch, then the
  `viewTruss` gate, with `local` open as Truss leaves it. Being allowed into the
  panel is a different question from being allowed to read the schema.
- **Depend on the `Truss` facade, never on Truss internals.** `Truss::payload()`
  is public API. The classes behind it are not, and building on them would make
  every upstream refactor a breaking change for our users.
- **Do not send Laravel Truss a pull request from work done here.** If something
  is missing upstream, raise it there as an issue and let it be done in that
  repository, which has its own conventions and its own reviewer.
- One behaviour per test, with descriptive names.
- Commit messages as `type: short subject`, then a body explaining what and why,
  not how.
- Do not use em dashes or en dashes in prose.

## Reporting bugs

Open an issue with a minimal reproduction. A failing test is the most helpful
form a report can take.

Two things worth including, because they are the first questions that will be
asked: your Filament version, and your `albertoarena/laravel-truss` version.

If the diagram renders but looks wrong, say which panel theme you are using and
whether the panel was in dark mode.

For security issues, please email **hello@albertoarena.it** instead of opening a
public issue.
