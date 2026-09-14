# Manual tests

## Why this file exists

Two suites cover this package. Pest covers what PHP decides: who may see the
page, what goes into the payload, and whether the container still provides every
element Truss's frontend reaches for. Vitest covers the two client-side files it
ships, read from disk exactly as the browser is served them.

**Neither can render the page.** A Filament page needs a panel, and a panel needs
a host application, which this repository does not have. So rendering and
interaction are checked by hand, against this list, and this list is the release
gate: **a pass belongs in the pull request or release notes, not in someone's
memory.**

It is also the spec for the browser test that should eventually replace it. When
a host panel exists, these become Playwright specs and this file shrinks to
whatever cannot be automated (real engines, real themes, a real eye).

## Setting up

A scratch Filament 5 panel pointed at a development database with a real schema.
An ER diagram needs real tables, not real resources, so the panel does not need
any. Worth having in the database: a table with a foreign key, a pivot with a
composite primary key, a table with a column default, and at least 30 tables so
the large-schema path is reachable.

```bash
composer require albertoarena/filament-truss
php artisan truss:baseline   # so the diff panel has something to compare against
```

Register the plugin on the panel, then confirm the versions you tested with:

```bash
composer show albertoarena/laravel-truss filament/filament | grep -E "^(name|versions)"
```

**Re-run section 2 and section 4 whenever Truss is upgraded.** Those are the two
that depend on markup this package reproduces rather than owns.

## 1. Access, which is parity and not a gate check

- [ ] `truss.enabled` false: the navigation item is absent, and the URL returns
      403 rather than rendering.
- [ ] `local` environment, no `viewTruss` gate defined: the page is visible. This
      is the case a plain gate check gets wrong, and it reads as a bug report.
- [ ] Non-local environment, gate denies: navigation item absent, URL 403.
- [ ] Non-local environment, gate allows: page visible.
- [ ] Guest: redirected to the panel's login, not shown the page.
- [ ] A user who may open the panel but fails the gate does not get the page.
      Panel access and schema access are different questions.
- [ ] With the page open, the network tab shows `truss.css`, `truss.js`,
      `mermaid.min.js` and the font all at 200. **A 404 here means the viewer
      passed this package's check and failed Truss's**, which is the failure the
      parity rule exists to prevent.

## 2. The page in the panel

- [ ] The navigation item reads as the feature rather than the package, with the
      circle-stack icon.
- [ ] The diagram is contained: the blueprint grid is inside the bordered box
      only, not behind the sidebar, the topbar or the whole admin.
- [ ] The box has a sensible height on a laptop viewport, and the page does not
      scroll horizontally.
- [ ] A tall diagram scrolls inside the viewport rather than stretching the box
      past the height it was given.
- [ ] The footer shows a table count and a last-updated time.
- [ ] Narrow the window: the more-controls (`⋯`) button appears and opens the
      secondary controls.
- [ ] Another page of the panel is unaffected. The stylesheet resets `body`, so
      this is the one check that it stays scoped.

## 3. The pipeline, which is Truss's and is only re-driven here

- [ ] Filter by a table name: the diagram redraws to the matching subset.
- [ ] Focus combobox: type a table, pick it with the keyboard, and the diagram
      reduces to that table and its neighbours.
- [ ] Depth: raising and lowering it changes how much of the neighbourhood is
      drawn.
- [ ] Laravel types checkbox: column types switch between the database spelling
      and the Laravel one.
- [ ] Zoom: scroll to zoom, drag to pan, the range and percentage keep up, and
      Fit brings the whole diagram back into view.
- [ ] Click a table: the popover opens with that table's columns, keys and
      indexes, and closes again.
- [ ] Legend opens and closes.
- [ ] Export: PNG and SVG both download and both open.
- [ ] Diff: change a column in the database without re-baselining, reload, and
      the diff button appears with the change listed.
- [ ] Health: the findings panel lists `truss:doctor` findings with a count on the
      button, and maximises.
- [ ] A schema above `truss.large_schema.warn_above` shows the warning banner.
- [ ] The connections control stays hidden. One connection is rendered here on
      purpose: the payload is embedded once, so a switcher could not switch.

## 4. The theme, which is the panel's

- [ ] Toggle the panel between light and dark: the diagram follows immediately,
      with no reload and no flash of the other theme.
- [ ] Truss's own theme button (`◐`) is not visible.
- [ ] **Dark operating system, light panel: the diagram is light.** This is the
      whole point of the bridge. Without it Truss falls back to the machine's
      preference and disagrees with the panel.
- [ ] Load the page directly in dark mode (not toggled into it). The diagram is
      dark from the first paint rather than starting light.
- [ ] A first-party theme that is not the default, plus the compact modifier: the
      diagram still looks like part of the panel.
- [ ] A custom panel theme: same question, and the honest one, since this is what
      most real panels run.

## 5. The promise, checked rather than assumed

- [ ] View source: the `data-truss-payload` block carries tables, columns,
      indexes and foreign keys, and **no row data**. Search it for a value you
      know is in a table.
- [ ] The network tab shows **no request for a schema endpoint**. The payload is
      in the page, so there is nothing to ask for.
- [ ] A table with a column default or comment containing `</script>` does not
      break the page. Truss reads comments as structure, so this is not
      hypothetical.
- [ ] Nothing on the page writes: no action, no form, no link that changes the
      database.

## 6. When Truss cannot fully answer

- [ ] No baseline recorded: the page still renders and says so, rather than
      failing.
- [ ] Cache store unreachable: the page still renders, with the flag Truss sets
      for it.
- [ ] SQLite fallback in play: the footer flag shows.

## Recording a pass

Note the Truss version, the Filament version, the panel theme, the browser, and
anything skipped. **A checklist with no record of which version it passed against
says nothing the next time Truss is upgraded.**
