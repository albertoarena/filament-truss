# Decisions

One short entry per significant choice: context, decision, trade-off. Add new
entries at the bottom as the project evolves.

Entries dated 2026-09 predate any code. They were settled while sketching the
package and are recorded here so they are not re-litigated by whoever starts
building.

## A separate package, in a separate repository

**Context:** the integration could have been a guarded feature inside
`albertoarena/laravel-truss`, switched on when Filament is detected.
**Decision:** a second package in a second repository, `albertoarena/filament-truss`.
**Trade-off:** a second package must be kept in step with every Truss release,
and it splits the install numbers, so a Filament install and a Truss install are
different figures and must never be added together. In exchange, **Truss keeps no
Filament dependency at all** and the vast majority of its users, who do not run
Filament, carry nothing for a feature they will never open.

## The name is `filament-truss`, not `filament-truss-erd`

**Context:** putting the category word in the package name makes it findable by a
Packagist search for "filament erd".
**Decision:** `albertoarena/filament-truss`. The category word goes where it is
actually read: the plugin directory listing, the repository description, the
topics, the Packagist description and the first line of the README.
**Trade-off:** gives up a Packagist search that the evidence says is barely run,
in exchange for a name that carries the project it belongs to. A directory
listing has its own title and description, so nothing is lost where discovery
actually happens. The `filament-` prefix is community convention rather than a
rule, and Filament documents no naming or branding policy for third-party
plugins; that is worth re-checking before the name is registered publicly, since
Truss has its own trademark file and the courtesy runs both ways.

## A native Filament page, not an iframe and not a restyled embed

**Context:** three options, at very different costs. Iframe the Truss dashboard;
mount Truss's frontend in a Filament page and restyle it; or build the page from
Filament's own components.
**Decision:** the third. A native page, built from panel components, styled by
panel tokens, carrying none of Truss's own CSS.
**Trade-off:** the most work of the three, and the only one that can ever link a
table to its Filament resource. An iframe is an hour of work and will look wrong,
break the panel's dark mode, lose the sidebar and read as a bolted-on page. **If
a frame were good enough, Truss already ships a dashboard and a link would do.**

## Depend on `Truss::payload()`, never on Truss internals

**Context:** the dashboard payload (the filtered snapshot plus the diff, the
findings and the unavailability flags) was originally assembled inside Truss's
own schema controller. Reaching it meant either an HTTP request from inside the
application to itself, or constructing `DoctorReport`, `SchemaDiffer` and
`BaselineStore` by hand, none of which are public API.
**Decision:** depend on the facade. Truss v1.12.0 added
`Truss::payload(?string $connection = null)` for exactly this, and it is this
package's entire data dependency.
**Trade-off:** ties the minimum Truss version to v1.12.0. That is cheap, and the
alternative is an integration built on internals, which would make every Truss
refactor a silent breaking change for our users and would re-price the standing
cost of maintaining a second package.

## Hand the payload to the frontend in the page, not over HTTP

**Context:** inside a panel, the application already holds the array. Fetching it
from the application's own URL is a round trip for something already in memory,
and it requires the schema endpoint to be routed and reachable.
**Decision:** render the payload into a
`<script type="application/json" data-truss-payload>` block, which Truss v1.12.0
reads instead of fetching.
**Trade-off:** the page carries the schema in its HTML, so the response is
larger. In exchange there is no second request, no endpoint dependency, and the
filter and focus still run client side. Structure only, as ever.

## Target Filament 5, and never Filament 4

**Context:** Filament 5 requires Livewire 4, so an application on Filament 4 with
Livewire 3 is two majors away, not one.
**Decision:** Filament 5 and above.
**Trade-off:** excludes applications that have not upgraded. Supporting 4 would
be cheap now and wrong later, and the audience this package is being built to
reach is on 5.

## Authorization is parity with the Truss route, not a gate check

**Context:** a Filament page never passes through Truss's `Authorize` middleware,
and that middleware does more than run a gate: it refuses when `truss.enabled` is
off, and it leaves `local` open unconditionally.
**Decision:** `canAccess()` returns the same answer the Truss dashboard route
would give the same user, in the same order, on top of whatever the panel
requires.
**Trade-off:** slightly more work than calling the gate, and it does not drift if
the middleware grows a fourth condition. Checking only the gate would be both too
permissive, rendering the schema in an application where Truss is switched off,
and too strict, hiding the page in `local` where the dashboard is open. **It is
also functionally required**: Truss's stylesheet, JavaScript and Mermaid are
served from gated routes, so a viewer who fails the gate gets a page whose assets
404 and a blank frame.

## Re-drive Truss's frontend pipeline in v0.1, do not reimplement it

**Context:** Truss's dashboard is client side end to end. Its selection pipeline
reduces exclusions, the filter and the focus depth to a table subset and turns
that into the Mermaid definition. The focus picker is an input to that pipeline,
not decoration around it.
**Decision:** v0.1 keeps Truss's JavaScript as the pipeline. Filament controls
feed it; they do not replace it.
**Trade-off:** the page depends on a frontend contract that is not yet public
(see the next entry). Reimplementing selection and definition in PHP would mean a
second renderer to keep in step with the first for ever, which is not what a v0.1
is for.

## Live with reproduced markup in v0.1, and ask upstream for a seam

**Context:** Truss's container markup is not a public contract. Its entry point
resolves 31 elements by id, 20 are reached without a null guard, and nine are
guarded inconsistently, so a page providing only a canvas will not boot and the
required set cannot be inferred by reading.
**Decision:** v0.1 reproduces what it must and pins a Truss version. The seam
worth having, a mount function owning the canvas, viewport, banners and popover
and taking a payload, is requested upstream rather than invented here.
**Trade-off:** a copy that upstream can change without warning, which is a real
cost accepted deliberately and for a bounded time. **This package is the first
real consumer of such a seam, so it should be designed from what is built here
rather than in advance**, which is how the wrong API gets shipped.

## Consume the panel's CSS custom properties, do not match a palette

**Context:** Filament ships multiple first-party themes plus a compact modifier,
and panels carry custom themes besides, so a hand-matched palette is wrong by the
next release.
**Decision:** read the panel's computed CSS custom properties in the browser and
map them into Mermaid's theme variables at render time.
**Trade-off:** a new small subsystem rather than a reuse of Truss's existing
theming, which maps static config values to a server-rendered stylesheet and
cannot follow a theme chosen at runtime.

**CORRECTED 14/09/2026, and the correction makes this cheaper than it looked.**
This entry previously said the diagram would have to be re-initialised and
re-rendered on a dark-mode toggle, because Mermaid takes its theme variables at
render time. **That is true of Mermaid and false of Truss**, which initialises
with `theme: 'base'` and paints entity, row and line colours from its own CSS
variables precisely so that light and dark need no re-render. Verified in a
panel: toggling Filament's theme switches the diagram with it, instantly, with
no reload.

**What was actually needed was smaller and duller.** Filament adds a `dark`
class to `<html>`; Truss reads `data-theme` on the same element. Neither knows
about the other, so a light panel could hold a dark diagram. A few lines mirror
one to the other, and Truss's own theme button is hidden so there is no second
control to disagree with the panel.

## Drive Truss's private theme tokens, not its public knobs

**Context:** Truss has a documented theming API, eight semantic knobs under
`truss.theme` that it turns into a stylesheet. It is the obvious thing to reach
for and it cannot do this job: the knobs are config, resolved on the server,
once, and a Filament panel decides its palette in the browser, per panel, and
changes it at runtime with a toggle. Behind those knobs are the `--bp-*` custom
properties Truss calls private.
**Decision:** map Filament's own scales onto the private tokens, scoped to the
diagram's container, in light and dark. A theme set in `truss.theme` is left to
the standalone dashboard and is not applied to the panel page.
**Trade-off:** a second dependency on something upstream does not promise, next
to the reproduced markup. It is guarded the same way, by a test that reads the
knob map out of Truss and fails when the mapping stops covering it, and it buys
the thing the knobs cannot buy: a diagram that follows the panel it is in,
including a custom primary colour and a theme that does not exist yet. **The ask
that would retire this** is knob-level custom properties in Truss, so a host can
theme from CSS rather than only from config. That belongs upstream, raised there,
in a session working in that repository.

## Keep the export endpoint, even though the schema endpoint is dropped

**Context:** the page embeds its payload, so it declares no schema endpoint. The
export endpoint was dropped along with it, which looked consistent and was not:
Truss reads the absent attribute as "this page has no server" and greys out
Markdown, DBML, JSON and CSV. PNG and SVG kept working because they are drawn
from the DOM, which is exactly what made the gap easy to miss.
**Decision:** keep `data-export-endpoint`. There is a server here, the route is
the same one the dashboard uses, and this page already answers the same
authorization question that route asks.
**Trade-off:** those four exports are a server round trip that re-reads the
schema, where the diagram itself costs none. That is what they are on the
dashboard too, and the alternative is a panel that silently offers less than the
page it is at parity with.

## Documentation splits between this README and trussphp.com

**Context:** two packages, and a documentation site that already exists for the
other one. Duplicating the guide in both places guarantees they drift; putting
everything on the site leaves Packagist and the plugin directory rendering a
README that does not say how to install the thing.
**Decision:** **the README is the source of truth for installing and configuring
the package**, because that is what Packagist and the directory render. **The
site carries the narrative, the screenshots and the guide**, in a section of its
own alongside the Laravel Truss documentation, and links back here for the
commands. That section is maintained in step with this repository rather than
written once.
**Trade-off:** a second place to keep current at every release, in a repository
with its own conventions. It follows an established pattern rather than opening a
new one, since the existing integration guides on that site are the same shape,
and it costs one sidebar entry. **The section appears once there is something
installable**: the site builds against the latest release, so publishing a guide
to an unreleased package would document something nobody can install.

## Resource linking is v0.2, not v0.1

**Context:** mapping each table to the Filament resource that manages it is the
one feature no rival can copy without a panel, and its better half is showing the
tables that have **no** resource, which is the part of the database the admin
cannot see.
**Decision:** not in v0.1. The first release is the diagram, the focus picker and
the panel's styling.
**Trade-off:** ships the less differentiated half first. Those three have to be
right before anything else is worth adding, and v0.1 can then be dogfooded
against a scratch panel pointed at a real schema, since an ER diagram needs a
real schema and not real resources. **One thing to check before the v0.2 feature
is ever screenshotted:** on a panel covering only part of its database, most
tables will come back unmapped, which is either the feature demonstrating itself
perfectly or a misleading first impression.

## The focus deep link moves into v0.1, the rest of resource linking does not

**Context:** the entry above put every part of resource linking in v0.2, written
before anything had been tried in a panel. What a browser then showed is that the
two halves cost nothing like the same. Truss parses `focus` from the query string
and applies it on load, so a button opening `database-schema?focus=books` needs
no JavaScript of ours, no addition to the payload and no second renderer. The
other half, reporting which tables no resource manages, still needs the panel's
own map of resources and a judgement about what unmapped means.
**Decision:** ship the deep link in v0.1 as `ViewInSchemaAction`, with
`HasViewInSchemaAction` opting a resource in. Leave the unmapped-table finding in
v0.2.
**Trade-off:** it widens v0.1 by a feature, against a first release with nothing
in it that a diagram in a frame does not also have. The button is the screenshot
that explains why this is a plugin rather than a link to a dashboard, and it is
small enough that leaving it out costs more than putting it in.

**Three guards, all of them learned rather than guessed.** Hide when the panel
being rendered has no such page, because a resource shared between two panels
would otherwise throw where the plugin is not registered. Hide unless the page's
own access rule allows this viewer, because listing a resource is not reading the
database structure. Hide when Truss excludes the table, because `?focus=cache` is
ignored and a button that does nothing gets reported as a bug. Removed rather
than disabled, since a viewer can do nothing about any of the three and a greyed
control invites them to try.

**The exclusion question is answered from config, not from a payload.** It is
asked once per button and `Truss::payload()` reads the whole schema, so the
merge Truss does over `excluded_tables` is repeated in one small class of ours
with its own test. The parameter name is pinned from the other side by a Vitest
case that imports `url-state.js` out of `vendor/`, so an upstream rename fails on
the upgrade instead of shipping a button that opens an unfocused diagram.

## Hiding and revealing tables is Truss's, and this package adds no control

**Context:** a panel on a 17 table database draws 8 of them, because Truss
excludes framework plumbing by config, and nothing on the page said so. The
plugin-shaped answer was an option here, `->revealExcludedTables()`, so an
application could decide per panel. Truss v1.13.0 answered it first and answered
it differently: the payload always carries `excluded.count`, the footer reads
`8 of 17 tables`, and a **Show hidden tables** toggle draws the hidden ones muted
when `truss.reveal_excluded` lets them leave the server at all (on in local, off
elsewhere, matching `enabled` and the `viewTruss` gate, and with no query
parameter, so the decision stays the operator's and never the viewer's).
**Decision:** take that mechanism whole. This package reproduces the two elements
the toggle needs, `truss-show-excluded-field` and `truss-show-excluded`, and adds
nothing beside them: no plugin method, no config key of our own, no page action,
no override of what the application configured.
**Trade-off:** a panel cannot reveal tables `reveal_excluded` keeps on the
server, and an application that wants the toggle outside `local` says so in
Truss's config rather than in the plugin registration. That is the point and not
the price. An option here would be a second switch answering a question Truss
already answers, and the two would eventually disagree: someone who excluded a
table to keep it off a shared panel would find a plugin flag putting it back.
**It is the authorization-parity rule applied to visibility**, which is the same
argument about the same boundary, in a different place.

**What this package still owes the feature** is the markup and the manual check,
because the toggle is upstream's and the container is ours. The drift guard is
what makes that an upgrade-time failure rather than a silent one.

## Match Filament's controls by recipe, and borrow its class only for the checkbox

**Context:** the palette gives the toolbar its colours, so the controls turn dark
with the panel, but it cannot reach shape or type. Truss styles its toolbar for a
dashboard it owns: monospace at 12.5px, a 2px radius, a hairline border. Beside a
real Filament search field that was the last thing on the page still reading as a
visitor. Two ways to fix it: reproduce Filament's look from its own custom
properties, or borrow Filament's own classes and get the real thing.
**Decision:** both, split by what each is good at. The text inputs, labels and
utility buttons are restyled in our stylesheet from Filament's properties
(`--radius-lg`, `--primary-600`, `--default-font-family`, the greys), keyed to
`ft-controls`, a class this package adds in its own Blade. The two checkboxes
carry Filament's own `fi-checkbox-input`.
**Trade-off:** the recipe is an imitation and can drift as Filament's inputs
evolve, which is the price of not depending on its class names. The checkbox is
the one control where that price is not worth paying: a native checkbox is
painted by the operating system and `accent-color` reaches only the checked fill,
so an unchecked box stays the wrong grey whatever we write. Filament styles
`input[type=checkbox].fi-checkbox-input` outright, across the rest, checked,
focus, disabled and dark states, and needs no wrapper. Our `accent-color` rule
stays behind it as a fallback, so a rename upstream degrades to a primary-tinted
native control rather than to an operating-system blue.

**Two borrowings considered and rejected**, both after reading the panel's
compiled stylesheet. `fi-input-wrp` carries the input chrome, but adopting it
means wrapping every field in markup Filament expects, and the focus combobox
anchors an absolutely positioned listbox to its own container, so the wrapper
buys what the recipe already gives at the cost of the one control most likely to
break. `fi-icon-btn` carries `margin: calc(var(--spacing) * -2)`, a negative
margin that assumes the padding of a Filament container that is not there.

**Keyed to `ft-controls` rather than to `.truss-toolbar` on purpose.** The
toolbar markup is reproduced in this package, so hanging our chrome on a class of
our own means an upstream rename costs the Blade and not the stylesheet as well.

## The project link is on by default, and removable in one call

**Context:** the page carries a subheading saying where the diagram comes from
and what it will never show, and a link to the project beside it. The subheading
is for whoever is looking at the panel; the link is for the developer who
installed it.
**Decision:** both ship on. The link is a header action controlled by
`FilamentTrussPlugin::make()->documentationLink(false)`.
**Trade-off:** one more line of plugin API, and a default that puts our name in
someone else's admin. **A plugin that links to its own repository from a panel it
does not own, with no way to remove it, is a plugin with a billboard in it**, and
the panel's owner has to be able to take it down without forking the page. On by
default because it is genuinely where the person looking at this page goes next,
and because a plugin nobody can find the documentation for is worse than one that
says where it is.

**The subheading is not switchable**, deliberately. It is one sentence, it names
the promise in the place where the person who would most want to know it is
looking, and a panel that wants different words can translate it.
