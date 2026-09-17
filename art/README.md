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

Prepared for the plugin listing form, which asks for two images and gives a
light theme as the hint. They show the diagram with the plugin name and a short
tagline as text, and crop out the panel sidebar and top bar.

| File | Form field | Size | Notes |
| --- | --- | --- | --- |
| `image-light.jpg` | **Image** | 3200x1800 (16:9) | Light background, the recommended one. Also the README's light variant. |
| `image-dark.jpg` | **Image**, backup | 3200x1800 (16:9) | Dark variant, and the README's dark one. |
| `thumbnail-light.jpg` | **Thumbnail** | 2560x1440 (16:9) | Light background. |
| `thumbnail-dark.jpg` | **Thumbnail** | 2560x1440 (16:9) | Dark variant. |

Spec: image at least 2560x1440, thumbnail at least 1280x720, 16:9, JPEG.

The README links `image-light.jpg` and `image-dark.jpg` from `raw.githubusercontent.com`
on `main`, so replacing a file replaces what the README shows, with no change to
the README itself.

## How they are made

`shoot.mjs`, against a running demo panel. **The images are of a user interface
that keeps changing, and a screenshot nobody can reproduce is one that quietly
goes stale**, so the shot is a script rather than a description of how it was
once taken.

```sh
PLAYWRIGHT_MODULE=/path/to/node_modules/@playwright/test/index.mjs \
  node art/shoot.mjs art/filamentphp

cd art/filamentphp
for t in light dark; do
  magick image-$t.jpg -resize 2560x1440 -quality 92 thumbnail-$t.jpg
done
```

Playwright is not a dependency of this package and is resolved at run time, so
point `PLAYWRIGHT_MODULE` at one that is already installed somewhere. `ART_BASE`
overrides the panel URL, which defaults to `http://localhost:8000`.

What the script does, and why each part is there:

- **3200x1800 from a 1600x900 viewport at a 2x device scale factor.** No screen
  here is that size.
- **Removes the sidebar and the top bar**, which is the listing reviewer's own
  note on the sibling package: the shot should be the feature and not a panel.
- **Replaces the page heading** with the package name and a one-line tagline,
  using Filament's own `fi-header-heading` classes rather than inline colours.
  A first pass hard-coded `--gray-950` and the dark shot came back with an
  invisible title.
- **Re-measures the container height and re-fits the diagram.** The container
  sizes itself as `100vh` minus the panel chrome it expects above it, and that
  chrome has just been removed, so without this the diagram keeps a band of
  empty page above it and loses the same off its own bottom edge.
- **The thumbnails are scaled from the images, not shot again.** A 1280x720
  viewport leaves the diagram less room, so it fits at 32 percent rather than
  48, and the thumbnail comes back with unreadable tables and a lot of empty
  grid. The composition should not change between the two sizes.
