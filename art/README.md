# Art

Images for the README, the [filamentphp.com plugin listing](https://filamentphp.com/plugins)
and anywhere else the package is shown. **None of it ships**: `/art` is
`export-ignore` in `.gitattributes`, so the Composer archive carries no
screenshots.

Everything here is a screenshot of the **demo panel**, which is a real panel on a
real database. Two rules follow from what this package promises, and neither is
optional:

- **Structure only, in the pictures as well.** No row data in any shot, which
  means no resource tables and no record pages in frame. The schema page shows
  none, so this is about what else is captured beside it.
- **Nothing identifying a real application.** A demo database, a demo brand, and
  a panel branded after its own domain rather than after this plugin: a panel
  named for the plugin reads as a toy, one named for a bookshop reads as an
  application that happens to have the page installed.

## filamentphp/

Prepared for the plugin listing form, which gives a light theme as the hint.
**They are a composition, not a screenshot**, and that is the difference between
the first set and this one: the listing shows them in a grid beside two hundred
others, at about a third of a card, and a cropped page with a small heading
loses that grid before anyone reads the name. So the image carries the name,
what it does, the three things it does, and the one command that installs it,
with the real page beside it as the proof.

| File | Form field | Size | Notes |
| --- | --- | --- | --- |
| `image-light.jpg` | **Image** | 3200x1800 (16:9) | What was submitted, 20/09/2026. Also the README's light variant. |
| `image-dark.jpg` | none | 3200x1800 (16:9) | Dark variant, held as the backup, and the README's dark one. |
| `thumbnail-light.jpg` | none | 2560x1440 (16:9) | See below. |
| `thumbnail-dark.jpg` | none | 2560x1440 (16:9) | See below. |

Spec: image at least 2560x1440, 16:9, JPEG.

**The Thumbnail field is left empty, and the two thumbnails are not submitted.**
The form takes one image and says plainly that a thumbnail should only be
uploaded when it differs from it, offering a tighter crop for the plugins list
as the example, and that the main image is used automatically otherwise. Ours
does not differ: it is the same composition at a lower device scale factor, by
design, because a thumbnail composed separately is a thumbnail that drifts.

So they are kept for anywhere that wants the cover at half the weight, and a
thumbnail is worth submitting only once it is composed for a card rather than
scaled down to one. At list size the wordmark and the shape of the window carry;
the tagline, the pills and the install line do not, and a real thumbnail would
drop them and bring the diagram closer.

The README links `image-light.jpg` and `image-dark.jpg` from `raw.githubusercontent.com`
on `main`, so replacing a file replaces what the README shows, with no change to
the README itself.

## frames/

`page-light.png` and `page-dark.png`: the photograph of the real page, before
the cover is composed around it. Kept because the two halves fail for different
reasons and at very different speeds. The page needs the demo panel running and
takes seconds per shot; the composition is a stylesheet and gets iterated on
twenty times in a row. `ART_REUSE=1` recomposes against these and never opens the
panel.

They are also the honest record of what the cover is a picture of. Nothing is
drawn by hand into the window: if a table looks like that in the image, it looks
like that in the page.

## How they are made

`cover.mjs`, against a running demo panel. **The images are of a user interface
that keeps changing, and a screenshot nobody can reproduce is one that quietly
goes stale**, so the shot is a script rather than a description of how it was
once taken.

```sh
PLAYWRIGHT_MODULE=/path/to/node_modules/@playwright/test/index.mjs \
  node art/cover.mjs art/filamentphp art/frames

# Composition only, against the frames already in art/frames
ART_REUSE=1 PLAYWRIGHT_MODULE=... node art/cover.mjs art/filamentphp art/frames
```

Playwright is not a dependency of this package and is resolved at run time, so
point `PLAYWRIGHT_MODULE` at one that is already installed somewhere. `ART_BASE`
overrides the panel URL, which defaults to `http://localhost:8000`.

The composition itself is `cover-template.mjs`, a pure function returning a
complete HTML document, and it is the half with tests
(`tests/js/cover-template.test.js`). That split is deliberate: a missing pill or
a title painted a colour that exists in only one theme is a defect that does not
need a browser to catch, and catching it in a test beats catching it in a
re-shoot.

What the script does, and why each part is there:

- **All four files come from one composition**, at two device scale factors over
  a 1600x900 viewport. The thumbnail is not a second, smaller composition: a
  1280x720 viewport leaves the diagram less room, Truss fits it smaller, and the
  thumbnail comes back with unreadable tables and a lot of empty grid.
- **The page is captured at the size the cover displays it, and not larger.**
  This is the whole fix for what the first set got wrong. Legibility on the cover
  is the diagram's own zoom multiplied by however much the photograph is then
  scaled down to fit the window, so a 1360-wide capture dropped into a
  1088-wide window turned a 64 percent diagram into 51 percent, and nothing in
  the tables could be read.
- **Removes the sidebar, the top bar and the page heading**, which is the listing
  reviewer's own note on the sibling package: the shot should be the feature and
  not a panel. Truss's toolbar stays, because the toolbar is the feature.
- **Re-measures the container height and re-fits the diagram.** The container
  sizes itself as `100vh` minus the panel chrome it expects above it, and that
  chrome has just been removed, so without this the diagram keeps a band of
  empty page above it and loses the same off its own bottom edge.
- **Opens the diagram out past the fit, by a measured amount rather than a
  guessed percentage.** A diagram that stops short of all four edges reads as a
  picture of a diagram; one that runs off them reads as a window onto a schema
  bigger than the frame, which is what a real one is.
- **Sends the whole overflow to the bottom edge.** A diagram cut at the bottom
  reads as one that carries on past the frame. The same cut at the top lands on a
  table's title row, and a table with its name sliced off reads as a rendering
  fault.
- **Every colour in the composition is a custom property, declared once per
  theme.** A first pass hard-coded `--gray-950` for the heading and the dark
  image came back with an invisible title. A literal colour in a rule is a value
  one of the two themes will get wrong, and the test next door now fails on one.

## shoot.mjs

The plain, uncomposed shot: the page, chrome cropped, with the package name and
tagline in place of the page heading. It is not what the listing shows any more,
and it is kept for the places that want the page and nothing else, documentation
among them.
