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
