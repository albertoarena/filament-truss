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

**Read the panel's own CSS custom properties and map them into Mermaid's theme
variables at render time.** Every theme then works, including ones that do not
exist yet, and dark mode follows the panel instead of being detected separately.

**Dark mode is the first thing that will look broken**, and the mechanism matters
more than the prediction: Filament toggles dark mode client side with no page
load, while Mermaid takes its theme variables at initialisation and definition
time rather than through the cascade. **So the diagram has to be re-initialised
and re-rendered on the toggle**, not merely restyled by it.

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
diagram in a frame and a plugin. **Not in v0.1**: the first release is the
diagram, the focus picker and the panel's own styling, because those have to be
right before anything else is worth adding.

## Scope by version

**v0.1**: the diagram, the focus picker, and the panel's theme. Dogfoodable
against a scratch Filament 5 panel pointed at a real schema, because an ER
diagram needs a real schema and not real resources.

**v0.2**: resource linking, including the unmapped-table finding. This one needs
a panel that genuinely has resources.

**Later, undecided**: the structural findings as a page of their own. Truss
already produces them and `Truss::payload()` already carries them, so the cost is
presentation rather than analysis.
