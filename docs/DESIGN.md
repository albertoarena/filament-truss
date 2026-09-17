# Design

## Purpose

Show a Filament user the structure of the database their panel sits on, as a page
of that panel rather than as a separate application they have to go and find.

The audience is specific and worth naming: a Filament user is usually running an
admin panel over a database they did not design. That is the same person Truss
was built for, met somewhere they already are.

## What this package is not

**It is not a renderer.** Mermaid draws the diagram, and Mermaid comes from Truss
either way. Nothing here reimplements graph layout.

**It is not a second source of schema truth.** Truss reads the database; this
package asks Truss. If the two ever disagree, this one is wrong.

**It is not an iframe around the Truss dashboard.** See `DECISIONS.md`.

## Where the data comes from

Truss v1.12.0 added `Truss::payload(?string $connection = null)`, which returns
the array the Truss dashboard runs on, in process, with no HTTP request:

- the snapshot, with config exclusions already applied
- `excluded.count`, how many tables those exclusions removed, always present and
  never the names (v1.13.0)
- the structural diff against the recorded baseline
- the `truss:doctor` findings
- flags saying the cache store or the baseline disk could not be read

That method is the entire data dependency of this package. **We are inside the
application already, so there is no reason to make an HTTP request to
ourselves**, and `Truss::snapshot()` is the wrong tool because it is built for
exports and carries none of the diff or the findings.

## How the diagram is drawn

Truss's frontend can take its schema from the page instead of fetching it. When
the app container carries a `<script type="application/json" data-truss-payload>`
block, no schema endpoint is requested and none needs to exist.

So the flow is:

1. The Filament page calls `Truss::payload()`.
2. It renders that array into the payload block.
3. Truss's own JavaScript reads it and draws, with the filter, the focus picker
   and the type labels all still running client side.

**The seam is payload to pipeline, not chrome to canvas**, and that distinction
decides how much work this package is. Truss's selection pipeline reduces
exclusions, the text filter and the focus depth to a table subset, and turns that
subset into the Mermaid definition. **The focus picker is an input to the thing
that draws the diagram, not decoration around it.** So "rebuild the chrome
natively, keep the canvas" cuts across the code rather than along it.

**v0.1 re-drives that pipeline rather than reimplementing it.** Reimplementing
selection and definition in PHP would mean a second renderer to keep in step with
the first, for ever.

## What the exclusion list hides, and who may reveal it

Truss removes framework plumbing from the diagram by config, so a panel sitting
on 17 tables draws 8 of them. Until v1.13.0 nothing on the page said so, which
reads as Truss failing to see the other nine rather than as a setting doing its
job.

**The whole mechanism is Truss's and this package supplies only the markup.** The
footer says `8 of 17 tables` because `excluded.count` is in the payload; the
**Show hidden tables** toggle draws the hidden ones muted, and they reach the
browser at all only where `truss.reveal_excluded` allows it (on in `local`, off
elsewhere, matching `truss.enabled` and the `viewTruss` gate). There is no query
parameter, so revealing stays the operator's decision and never the viewer's.

**This package adds no control of its own**, deliberately. See `DECISIONS.md`.
What it owes the feature is the two elements the toggle needs in the reproduced
container, which is the same standing cost the rest of that container carries and
is caught by the same drift guard.

Revealed tables carry no change marks and no findings, because the diff and the
doctor have already run on the filtered set. That is why Truss draws them muted,
and it is worth knowing here: a revealed table showing nothing wrong has not been
checked, rather than checked and found clean.

## The open problem, and it is the one to solve first

**Truss's container markup is not a public contract.** Its frontend entry point
resolves 31 elements by id and 20 of them are reached without a null guard, so a
page providing only a canvas will not boot. Nine of those are guarded in some code
paths and not in others, so the required set cannot be inferred by reading, and a
partial page fails at whichever path happens to run first.

Three ways out, in ascending order of cost and of correctness:

1. **Reproduce the markup.** Cheapest, and it makes this package a copy of a
   Blade file that upstream may change without warning.
2. **Ask upstream to fail loudly**, naming missing elements at boot. Does not
   solve the problem, but turns an obscure failure into a clear one.
3. **Ask upstream for a mount function** that owns the canvas, viewport, banners
   and popover, takes a payload, and leaves the host to supply controls. This is
   the honest seam and it is what this package actually wants.

**This package is the first real consumer**, which is exactly why option 3 should
be designed from what is built here rather than in advance. Until then, expect
v0.1 to live with option 1 and to pin a Truss version.

## Authorization

A Filament page does not pass through Truss's middleware, so none of what that
middleware does happens unless this package does it.

**The rule: `canAccess()` returns the same answer the Truss dashboard route would
give the same user**, in the same order, on top of whatever the panel itself
requires:

1. Deny unless `config('truss.enabled')`.
2. Then, **only when the environment is not `local`**, deny unless the
   `viewTruss` gate allows the user.

Two bugs that rule prevents:

- A page ignoring `truss.enabled` renders the schema in an application where the
  operator switched Truss off, which is the kill switch failing where a user
  would most assume it holds.
- A page checking only the gate is stricter in `local` than the dashboard is, so
  against the shipped email allow-list a local developer finds the page simply
  absent. Not a security bug, and exactly the sort reported as one.

**It is also functionally required.** Truss serves its stylesheet, its JavaScript
and Mermaid from gated routes, so a viewer who fails the gate gets a page that
renders with 404ing assets and a blank frame.

**Being allowed into the panel is not the same question.** A user who may open
the admin is not automatically a user who may read the schema, and that
distinction is the whole reason the gate exists.

## Theming

**"It works" is not the goal. Looking native is.** Filament ships several
first-party themes plus a compact modifier, and panels carry custom themes
besides, so matching a palette by hand is a moving target that is wrong by the
next release.

**Read the panel's own CSS custom properties and map them into Truss's theme
variables.** Every theme then works, including ones that do not exist yet, and
dark mode follows the panel instead of being detected separately.

**Built, and it turned out to be pure CSS.** Filament emits its scales
(`--primary-50` through `--primary-950`, the same for `--gray-*`) as custom
properties in the page, generated from the panel's own colour configuration.
Truss repaints the Mermaid output from its own variables, with `!important` rules
that beat the fills Mermaid writes as attributes. So redefining Truss's variables
on the container re-skins chrome and diagram together, in both modes, with no
JavaScript and no re-render. A panel with a custom primary colour arrives themed
for free.

**The cost is the same one the markup carries.** Truss's public contract is eight
semantic knobs (`accent`, `surface`, `muted` and so on) and it says the `--bp-*`
tokens behind them are private. Those knobs are config, read on the server, and a
panel picks its theme in the browser, so they cannot answer this question and the
private tokens are driven instead. `tests/Theme/PaletteTokensTest.php` reads the
knob map out of Truss by reflection and fails when our mapping stops covering it,
which is what keeps a rename from quietly leaving the diagram half-painted.

**A theme configured in `truss.theme` is not applied here**, deliberately. It
themes the standalone dashboard, which is a page of its own; inside a panel the
panel is the authority, which is the whole point of this section.

**Dark mode was predicted to be the first thing that broke, and the prediction
was wrong in a useful way.** An earlier version of this section said the diagram
would have to be re-initialised and re-rendered on a toggle, because Mermaid
takes its theme variables at render time. **True of Mermaid, false of Truss:** it
initialises with `theme: 'base'` and paints entity, row and line colours from CSS
variables, deliberately, so that light and dark need no re-render.

What was actually missing was smaller. Filament adds a `dark` class to `<html>`
and Truss reads `data-theme` on the same element, so the two simply never met. A
few lines mirror one onto the other, and Truss's own theme button is hidden so
the panel is the only control. Done, and verified in a panel.

Note that this is *not* Truss's existing theming machinery, which maps static
config values to a server-rendered stylesheet. Reading computed properties off
the host panel in the browser is a different mechanism, and only that one
survives a panel whose theme changes at runtime.

## The feature only this integration can have

**Map each table to the Filament resource that manages it.** The panel knows its
own resources, each resource names a model, and a model names a table, so the
join is available in memory and costs nothing.

Two things fall out, and the second is the more interesting:

- **Click a table, open its resource.** The obvious half.
- **Show the tables that have no resource at all**, which in a Filament
  application is a real finding: the part of the database the admin panel cannot
  see.

**Nobody can copy this without a panel.** It is the difference between the same
diagram in a frame and a plugin.

**The half that costs nothing is in v0.1.** Truss reads `focus` from the query
string and applies it on load, so a button that opens
`database-schema?focus=books` is a link rather than a feature: no JavaScript of
ours, no addition to the payload, nothing new to keep in step with the renderer.
That is `ViewInSchemaAction`, and `HasViewInSchemaAction` opts a resource in with
one line by asking it for `getModel()` and taking the table from there.

Three questions decide whether the button appears, and each is a bug report if
dropped:

- **Is the page on this panel?** A resource can be registered on two panels where
  only one of them has the plugin, and building the URL there would throw.
- **May this viewer see it?** The page's own rule, which is Truss's rule. Being
  allowed to list Books is not being allowed to read the database structure.
- **Will the diagram draw the table?** Truss ignores `?focus=` for an excluded
  table, silently and correctly, so a button pointing at one lands on an
  unfocused diagram and reads as broken.

The last is answered from Truss's exclusion config rather than from a payload:
the question is asked once per button and `Truss::payload()` reads the whole
schema to answer it. Any no removes the button rather than disabling it, because
there is nothing the viewer could do about any of the three.

**The half that needs the panel's own knowledge waits for v0.2**: reading every
registered resource to report which tables no resource manages. That is a
finding rather than a link, and it needs a panel that genuinely has resources.

## Scope by version

**v0.1**: the diagram, the focus picker, the panel's theme, and the focus deep
link from a resource. Dogfoodable against a scratch Filament 5 panel pointed at a
real schema, because an ER diagram needs a real schema and not real resources.

**v0.2**: the rest of resource linking, including the unmapped-table finding.
This one needs a panel that genuinely has resources.

**Later, undecided**: the structural findings as a page of their own. Truss
already produces them and `Truss::payload()` already carries them, so the cost is
presentation rather than analysis.
