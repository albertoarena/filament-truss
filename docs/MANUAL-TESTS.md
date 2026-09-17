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
```

**The diff panel needs a schema with a history**, not a command. There is no
`truss:baseline`: Truss records the pre-migration schema as the baseline
whenever migrations finish, so a database built by a single migration has
nothing to compare against and the panel stays hidden. Run one more migration,
however small, and the panel reports what it did.

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
      scroll horizontally. **It should not scroll vertically either**: the box
      ends where the page's own bottom padding begins. Its height is measured
      rather than assumed, so check it again on a panel whose heading is a
      different height (a breadcrumb, a wrapped subheading, a compact theme).
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
- [ ] Export: the server-backed formats are **offered, not greyed out**, and
      each downloads. Greyed out means the page is not declaring the export
      endpoint, so Truss believes it has no server. These are generated in the
      application, unlike PNG and SVG, which are drawn from the DOM and work
      either way.

      **They live in two menus, which is easy to misread as a missing feature.**
      The toolbar's export button offers PNG, SVG, Data dictionary (Markdown)
      and DBML. **JSON and CSV are in the table popover**, with Copy JSON and
      Download Markdown, reached by clicking a table's name. Check both.
- [ ] Diff: run a migration that changes something, reload, and the diff button
      appears with the change listed, the table marked in the diagram, and the
      panel headed "Changes since last migration". Every migration re-baselines,
      so the panel describes the most recent one rather than accumulating.
- [ ] Health: the findings panel lists `truss:doctor` findings with a count on the
      button, and maximises.
- [ ] A schema above `truss.large_schema.warn_above` shows the warning banner.
- [ ] The connections control stays hidden. One connection is rendered here on
      purpose: the payload is embedded once, so a switcher could not switch.

## 3a. Hidden tables, which are Truss's to hide and to reveal

This whole feature belongs to Truss (v1.13.0). What is being checked here is that
the panel presents it, since the container is reproduced and an element missing
from it fails silently in the browser rather than loudly in the suite.

- [ ] The footer reads **`8 of 16 tables`** and not `8 tables`, on a panel whose
      database has more tables than `truss.excluded_tables` leaves drawable. This
      is the whole point: a filtered diagram must not present itself as the whole
      schema.
- [ ] Filter or focus to narrow the view: the footer follows (`2 of 8`), and
      still shows **two numbers, never three**.
- [ ] A database with nothing excluded reads a plain `16 tables`.
- [ ] **`truss.reveal_excluded` true** (the default in `local`): the **Show
      hidden tables** checkbox is in the more-controls group, after Laravel
      types. Tick it and the hidden tables are drawn **muted**; untick it and
      they go away again.
- [ ] Revealed tables are muted enough to read as guests in **both** light and
      dark, against the panel's own palette. They carry no change marks and no
      health badges by design, because the diff and the doctor ran on the
      filtered set, so muted is what says "not checked" rather than "checked and
      clean". If they look like ordinary tables, that is the bug.
- [ ] With the toggle on, a revealed table appears in the **focus picker** and in
      the filter, and behaves like any other table while it is on screen.
- [ ] Known and not ours: **revealing does not re-fit the view.** Truss keeps the
      current pan and zoom, so on a fitted diagram the newly revealed tables can
      land outside the viewport and the change reads as "nothing happened" until
      Fit is pressed. The footer moving from `8 of 16` to `16 tables` is what
      confirms it worked. Do not report this as a plugin bug.
- [ ] **`truss.reveal_excluded` false** (the default outside `local`): the
      checkbox is **not shown**, and the footer still reads `8 of 16`. The label
      is in the DOM carrying `hidden`, exactly as it is on Truss's own dashboard,
      so "not shown" is the check and the payload below is the guarantee.
- [ ] With it false, view source and confirm the hidden tables are **not in the
      payload at all**, not merely undrawn. A count left the server; names did
      not.
- [ ] There is no way to reveal them from the panel: no plugin option, no page
      action, and **no query parameter**. Try `?show_excluded=1` and confirm it
      does nothing. Revealing is the operator's decision in Truss config, which
      is what makes excluding a table to keep it off a shared panel worth
      relying on.

## 3b. The focus deep link, which is a link and not a feature

Needs a panel with at least one resource, so the demo panel rather than a scratch
one. Nothing of ours runs in the browser here: the button builds a URL and Truss
applies `focus` on load, which is exactly why the failure mode is a silent
no-op rather than an error.

- [ ] A resource using `HasViewInSchemaAction` shows the **View in schema**
      button where the panel put it, and following it lands on the diagram with
      **that table focused**, at the configured depth, not on the whole diagram.
- [ ] The focus picker and the footer agree with the URL: the picker shows the
      table selected, the footer reads the narrowed count (`2 of 8`).
- [ ] Reload the focused URL: it comes back focused. It is an address, so it
      bookmarks and it shares.
- [ ] Clearing focus on the page leaves the URL Truss leaves it, and this package
      does not fight it.
- [ ] **A viewer who fails the access rule sees no button at all**, not a
      disabled one. Check with `truss.enabled` false, which is the fastest way to
      make the page inaccessible without touching a gate.
- [ ] **A resource whose table Truss excludes shows no button.** Add its table to
      `truss.excluded_tables`, reload, and confirm the button is gone rather than
      present and inert. Confirm by hand what it protects against: visit
      `?focus=<excluded table>` directly and watch it do nothing.
- [ ] On a second panel **without** the plugin registered, a resource using the
      trait still renders: no button, and no error.

## 4. The theme, which is the panel's

- [ ] Toggle the panel between light and dark: the diagram follows immediately,
      with no reload and no flash of the other theme.
- [ ] Truss's own theme button (`◐`) is not visible.
- [ ] **The toolbar controls read as Filament's, not as Truss's.** Put this page
      beside a resource list and compare the Filter box with the table's Search
      box: same typeface, same rounding, same ring and shadow, same height. In
      both modes.
- [ ] **The toolbar sits on the same colour as a table header**: white in light,
      `--gray-900` in dark, over the page's own grey. Measured rather than
      judged, since `--gray-50` and white are one step apart and the eye forgives
      it while a screenshot beside a resource does not.
- [ ] **The utility buttons read as one set.** Export, health and legend at the
      same size and the same stroke, with no character standing in for an icon.
      Narrow the window until the more-controls button appears and check it too,
      since it is the one that is hidden at desk width.
- [ ] **They carry no box**, like the filter and column buttons in a resource
      table header, and unlike the Filter and Depth fields beside them, which
      keep theirs. Hovering darkens the icon and never fills it.
- [ ] **Open one**: it fills with the panel primary and stays filled while the
      pointer is over it, which is how a person tells which panel is open when
      the legend and the health panel overlay the canvas. Tab to one: the focus
      ring is now the only other chrome it has.
- [ ] The **health count badge is whole** with the box gone. It rides on the
      button's corner and has been clipped before, against the container's own
      `overflow: hidden` edge.
- [ ] Focus a control: a solid 2px ring in the panel's primary, no border, and
      the shade steps down in dark rather than glaring.
- [ ] The two checkboxes are Filament's own, which is to say rounded with a ring
      when empty and filled with the panel primary when ticked, never the blue
      the operating system paints.
- [ ] **The diagram is still monospaced.** Only the toolbar was restyled, and a
      blanket font rule would take the canvas with it, where columns line up
      because they are monospaced.
- [ ] Change the panel's primary and reload: the focus ring and the ticked
      checkbox follow it, because both read Filament's own properties.
- [ ] The toolbar has room above and below the controls, and **the health
      badge is whole**. The bar is a flex child and was shrinking under the
      diagram, which cropped the badge against the container's own clipped
      edge, so a short bar is the symptom to watch for if it returns.
- [ ] Focus a table: its fill is **white over the panel's grey**, like a Filament
      card, and never Truss's pale cyan. The primary border is what says it is
      focused.
- [ ] Open the export menu and hover an item: the highlight is a panel grey, not
      a pale blue. An unavailable export does **not** light up on hover.
- [ ] The heading carries a subheading saying the diagram is read live **by
      Laravel Truss** and is structure only, and a **Documentation** link sits
      opposite it, opening in a new tab. The subheading is the only place the
      page names the package, so a panel that has translated it should still be
      naming something.
- [ ] Register the plugin with `->documentationLink(false)`: the link is gone and
      the subheading stays.
- [ ] **Dark operating system, light panel: the diagram is light.** This is the
      whole point of the bridge. Without it Truss falls back to the machine's
      preference and disagrees with the panel.
- [ ] Load the page directly in dark mode (not toggled into it). The diagram is
      dark from the first paint rather than starting light.
- [ ] **Interface text is the panel's, schema text is monospaced.** Open the
      legend: the word "Legend" and the descriptions read in the panel typeface,
      while `PK` and `FK` stay monospaced, because they are the diagram's own
      notation. Same line in the export menu (interface), the footer counts
      (interface), the focus picker's list (table names, monospaced) and the
      canvas (monospaced).
- [ ] **The panel's typeface survives this page.** Open a resource list and the
      schema page side by side and compare the sidebar, the topbar and the page
      heading. `truss.css` styles `body`, font included, and its rule is
      unlayered where Filament's is in a Tailwind layer, so it wins by default
      and the whole page silently drops to `system-ui`. **Measure it rather than
      look**: `getComputedStyle(document.body).fontFamily` on both pages. Inter
      and system-ui pass a glance.
- [ ] **The page around the diagram is the panel's own background in dark**, not
      a white slab. This sheet repaints `body`, because Truss's unlayered rule
      beats Filament's layered one and there is nothing to fall back to. It is
      invisible in light, where the canvas is white anyway, so it has to be
      checked in dark.
- [ ] **The panel's own layout options**, one at a time. There is no compact
      modifier in Filament 5; what exists and actually stresses this page is:

      - `->topNavigation()`: no sidebar, and the header sits somewhere else. The
        box must still end where the page ends, which is the measured height
        doing its job under a layout it has never seen.
      - `->maxContentWidth(Width::Full)`: the container gets the whole window.
      - `->sidebarCollapsibleOnDesktop()`: collapse and expand it. The width
        changes, the top does not, and nothing may overflow sideways.
      - `->font('Poppins')`: the heading, the toolbar labels, the inputs and the
        footer follow the panel. **The canvas and the legend keys must not**,
        because they are notation.
- [ ] A custom panel theme (`php artisan make:filament-theme`, registered with
      `->viteTheme()`, then `npm run build`): same question, and the honest one,
      since this is what most real panels run. The compiled theme replaces
      Filament's own stylesheet, so this checks that the diagram is reading
      properties rather than a stylesheet it expected to be there.
- [ ] **Change the panel's greys** (`->colors(['gray' => Color::Slate])`) and
      reload: the diagram background, the entity fill, the hairlines, the grid
      and the entity text all move with them.

      **Overriding `--color-gray-*` in the theme's own CSS does not**, and that
      is correct rather than a gap: Filament emits its `--gray-*` from
      `colors()`, so a Tailwind-level override moves neither the panel's chrome
      nor ours. Whatever changes Filament's palette changes the diagram, and
      whatever does not, does not.
- [ ] **Change the panel's primary colour** (`->colors(['primary' => Color::Teal])`)
      and reload. **What follows it**: the rule under the toolbar, the focused
      table's border, the input focus ring, the checkbox accent, the zoom
      controls and slider, the legend keys, the open state on the utility
      buttons, and the diff button when there are changes. They are read from
      Filament's `--primary-*`, so no change here should be needed for a colour
      this package has never heard of.

      **What does not follow it, and should not**: the PK and FK badges, which
      are entity text and take the grey scale, and the health button, which takes
      danger or warning because severity is not an accent.
- [ ] Nothing in the diagram is still wearing Truss's blueprint navy. A single
      element that is means a token our palette does not cover, which the
      reflection test should have caught, so say which one. **The banners are the
      place to look**, because they were the last three: info, warning and error
      were hard-coded hexes upstream rather than theme knobs, and a large-schema
      banner in `#e5eefb` is the loudest thing on the page.
- [ ] **The health button carries its severity**: the icon is the panel's danger
      or warning colour when the doctor found something, not grey beside a red
      count.
- [ ] Text on entity rows and in the toolbar is comfortably readable in both
      modes. The greys are mapped by step, and steps are a judgement that only a
      pair of eyes settles.
- [ ] Set a colour in `truss.theme` config and reload: `/truss` changes, the panel
      page does not. That is deliberate, not a bug.

## 5. The promise, checked rather than assumed

- [ ] View source: the `data-truss-payload` block carries tables, columns,
      indexes and foreign keys, and **no row data**. Search it for a value you
      know is in a table.
- [ ] The network tab shows **no request for a schema endpoint**. The payload is
      in the page, so there is nothing to ask for.
- [ ] A table with a column default or comment containing `</script>` does not
      break the page. Truss reads comments as structure, so this is not
      hypothetical. **Needs MySQL or Postgres**: SQLite has no column comments,
      so a SQLite panel can only exercise the default half of this.
- [ ] Nothing on the page writes: no action, no form, no link that changes the
      database.

## 6. When Truss cannot fully answer

- [ ] No baseline recorded: the page still renders and says so, rather than
      failing.
- [ ] Cache store unreachable: the page still renders, with the warning banner
      Truss shows for it. Reachable with `CACHE_STORE=file` and the cache
      directory made unwritable, as long as sessions live somewhere else. The
      diagram must be **complete**, since the snapshot was read live rather than
      partially: it is a notice about speed and never about the schema.
- [ ] SQLite fallback in play: the banner says the schema was replayed and column
      types may be approximate. **Running on SQLite is not enough**: `fallback` is
      false on an ordinary SQLite connection. Truss falls back only when the
      configured connection is **unreachable**, which it then replaces by
      replaying the migrations on in-memory SQLite.

      **Not reachable in a panel whose authentication uses that same
      connection**, which is most of them: breaking the connection logs you out
      before the page renders. It needs a panel whose users and sessions live
      elsewhere, so treat it as upstream's to cover unless such a panel is at
      hand, and say so rather than ticking it.

## Recording a pass

Note the Truss version, the Filament version, the panel theme, the browser, and
anything skipped. **A checklist with no record of which version it passed against
says nothing the next time Truss is upgraded.**

## Passes recorded

**16/09/2026, partial.** Truss v1.13.0, Filament 5, Laravel 13, the default panel
theme in light and dark, Chrome, against the demo panel (16 tables, 8 drawable).

Passed: section 3a in full, plus the table popover and both export menus offering
every format ungreyed, the theme following the panel instantly in both directions
with `data-theme` mirrored onto Filament's `dark` class, and a clean console
across a full load, a reveal, a filter and a focus. Muting is `opacity: 0.55` on
the node group, so it is palette independent and holds in both themes by
construction rather than by luck.

**17/09/2026, machine-checked only, and it does not count as a pass.** Truss
v1.13.0, Filament 5, Laravel 13, against the demo panel with the focus button on
two resources. Fetched as a signed-in user and read as HTML rather than looked
at: the button renders on both list pages, its URL is
`/admin/database-schema?focus=<table>`, and adding that table to
`truss.excluded_tables` removes the button from that resource while leaving the
other one's in place.

That is the guard working end to end in a real panel, which is worth knowing, and
it is not section 3b. **Nothing above was seen.** Whether the link actually lands
on a focused diagram, whether the picker and the footer agree with it, and
whether it survives a reload are all still open.

**17/09/2026, a driven pass.** Truss v1.13.0, Filament 5, Laravel 13, Chrome,
against the demo panel (16 tables, 8 drawable, SQLite). Driven from the browser
by script and read back from the DOM and from computed styles, so everything
below is measured rather than seen, except where it says otherwise.

Passed: **section 1** asset loading (all nine of `truss.css`, `truss.js`,
`filament-truss.css`, `mermaid.min.js`, `mermaid-definition.js` and the four
fonts at 200) and the guest redirect to the panel login; **`truss.enabled`
false** answers 403 and drops the navigation item. **Section 2** in full bar the
judgement calls: navigation label, containment (no Truss stylesheet loads on
another panel page at all, and that page keeps Filament's own background and
Inter), no horizontal scroll, the footer count and time, and the narrow-viewport
more-controls button at 606px opening Focus, Depth, Laravel types and Show hidden
tables. **Section 3**: filter (8 to 2 tables, address follows), the focus
combobox by keyboard, depth 1 to 2 to 3 and back (4, 6, 6, 4 tables), Laravel
types both ways, the table popover with its menu and Escape closing it, the
legend, the health panel with seven findings and its maximise, and the
large-schema banner with `warn_above` temporarily lowered to 5. **Section 3b**
in full except the eye: the button, the focused landing, picker and footer
agreeing with the address, and a reload coming back focused. **Section 5**: the
payload carries structure and no row data (five known values from the seeded data
searched for and absent), and no schema endpoint is requested. **Section 6**: no
baseline recorded, and the page renders with the diff button simply hidden.

**Three findings, and the checklist was wrong about three other things**, all
corrected above: the export formats live in two menus rather than one, SQLite
alone does not exercise the fallback flag, and the `</script>` comment case needs
an engine with column comments.

**Also 17/09/2026, and these two are now closed.** **The exports download and the
files are correct**, confirmed by hand rather than by script. **The diff panel**
was opened after a migration added `books.subtitle` to the demo: the button
appears, the panel reads "Changes since last migration" with
`+ column subtitle (varchar)` under a Changed badge, the table is marked in the
diagram, and the panel is in the panel's typeface while the column name stays
monospaced.

**A third correction to this file:** the setup section called for
`php artisan truss:baseline`, and no such command exists. Baselines are written
by Truss when migrations finish. Corrected above.

**Section 4 finished on 17/09/2026, including the judgements.** The panel primary
was changed to teal and everything that should follow did; the layout options
(top navigation, full content width, a collapsing sidebar) and a different
typeface were each run; a custom compiled theme was built and registered, and a
Slate grey scale set through `colors()` moved the diagram with it. **The two
judgement calls were made by Alberto and both pass**: the grey steps read as
separate rows without shouting, in light and in dark, and the 13px monospace is
comfortable in both.

**The cache flag passes** (the store made unwritable with sessions elsewhere: the
whole schema still drew, and the warning banner appeared in Filament's warning
colours).

**Section 1 finished the same day, in a production environment.** With
`APP_ENV=production`, `TRUSS_ENABLED=true` and an empty `TRUSS_ALLOWED_EMAILS`,
signed in as an admin who could use the rest of the panel: **the navigation item
was absent, every link to the page was gone, and the URL answered 403.** Adding
that admin's address to the allow list and restarting made the page visible,
with `truss.css`, `truss.js` and `mermaid.min.js` all at 200, which is the half
of the parity rule that is functional rather than cosmetic.

**Two things that pass had never been seen before.** `reveal_excluded` is false
outside `local`, so the Show hidden tables toggle was gone **and the payload
carried 8 tables rather than 16**: the hidden ones did not leave the server,
while the footer still said `8 of 16`. And the demo's own dashboard card dropped
its link to the page, because it asks the page the same question the page asks
itself.

**Two traps worth knowing for next time.** `php artisan serve` reads `.env` once
at boot, so changing the environment means restarting the server or the page
keeps answering as `local`, which reads exactly like a broken gate. And Filament
refuses the whole panel outside `local` unless the user model implements
`canAccessPanel()`, so without that every page 403s and the 403 you are looking
at is not the one you are testing.

**What is left is one item, and it cannot be ticked here**: the SQLite fallback,
for the reason given in section 6. Everything else in this file has been run.
