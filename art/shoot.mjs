// Shoots the listing art from a running demo panel.
//
// Committed because the images are of a user interface that keeps changing, and
// a screenshot nobody can reproduce is one that quietly goes stale. Re-run it
// after a change to the page and the art follows.
//
// Playwright rather than a screenshot by hand, for one reason: the listing wants
// 3200x1800, which no screen here has. A 2x device scale factor over a 1600x900
// viewport gets there exactly.
//
// Two rules the images have to keep, both from art/README.md:
//   - structure only, in the pictures as well: no resource tables, no records
//   - nothing identifying a real application
//
// This package has no JavaScript dependencies of its own beyond its test
// tooling, and Playwright is not one of them, so the module is resolved at run
// time and can be pointed at wherever one is already installed.
//
//   ART_BASE=http://localhost:8000 \
//   PLAYWRIGHT_MODULE=/path/to/node_modules/@playwright/test/index.mjs \
//   node art/shoot.mjs art/filamentphp
//
// The two thumbnails are then scaled from the images rather than shot again:
//
//   magick image-light.jpg -resize 2560x1440 -quality 92 thumbnail-light.jpg
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? '@playwright/test');

const OUT = process.argv[2] ?? 'art/filamentphp';
const BASE = process.env.ART_BASE ?? 'http://localhost:8000';

const TITLE = 'Filament Truss';
const TAGLINE = 'A live ER diagram of your real database, as a native page in your Filament panel. Structure only, never row data.';

/** Strip the panel chrome and put our own heading where the page heading was. */
async function compose(page, { title, tagline }) {
  await page.evaluate(
    ({ title, tagline }) => {
      // The listing reviewer's note on the sibling package: crop out the
      // sidebar and the top bar, so the shot is the feature and not a panel.
      for (const selector of ['.fi-sidebar', '.fi-topbar', '.fi-sidebar-close-overlay']) {
        document.querySelector(selector)?.remove();
      }

      const main = document.querySelector('.fi-main-ctn') || document.querySelector('.fi-main');
      if (main) {
        main.style.margin = '0';
        main.style.padding = '0';
        main.style.maxWidth = 'none';
      }

      const body = document.querySelector('.fi-body');
      if (body) {
        body.style.paddingInlineStart = '0';
      }

      // Our own heading in place of the page's, so the image carries the name
      // and what it does rather than a page title nobody has context for.
      const header = document.querySelector('.fi-header');
      if (header) {
        // Filament's own heading classes rather than inline colours. The first
        // pass hard-coded `--gray-950`, which is near-black in both themes, so
        // the dark shot came back with an invisible title. These classes carry
        // the panel's light and dark values, and the real page's type scale
        // with them.
        header.innerHTML = `
          <div style="display:flex;flex-direction:column">
            <h1 class="fi-header-heading" style="margin:0">${title}</h1>
            <p class="fi-header-subheading" style="margin:0">${tagline}</p>
          </div>
        `;
        header.style.padding = '0';
        header.style.marginBottom = '1.5rem';
      }

      // Dark reads the heading colours from the same properties, so nothing
      // here is light-specific beyond what the panel itself decides.
      const page = document.querySelector('.fi-page');
      if (page) {
        page.style.padding = '2.75rem 3rem 0';
      }

      // The container sizes itself as `100vh` minus the panel chrome it expects
      // to sit under. The chrome has just been removed, so it has to be told
      // again or the diagram keeps a band of empty page above it and loses the
      // same amount off its own bottom edge.
      const app = document.getElementById('truss-app');
      if (app) {
        const top = app.getBoundingClientRect().top;
        app.style.height = `calc(100vh - ${Math.round(top)}px - 2.5rem)`;
      }
    },
    { title, tagline }
  );
}

async function shoot({ width, height, dark, file }) {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width, height },
    deviceScaleFactor: 2,
    colorScheme: dark ? 'dark' : 'light',
  });
  const page = await context.newPage();

  // The demo signs itself in and lands on the schema page.
  await page.goto(`${BASE}/demo-login`, { waitUntil: 'networkidle' });

  if (dark) {
    await page.addInitScript(() => localStorage.setItem('theme', 'dark'));
  }

  await page.goto(`${BASE}/admin/database-schema`, { waitUntil: 'networkidle' });

  if (dark) {
    await page.evaluate(() => {
      document.documentElement.classList.add('dark');
      document.documentElement.setAttribute('data-theme', 'dark');
    });
  }

  // Mermaid renders after load; wait for a table to exist rather than guessing.
  await page.waitForFunction(() => !!document.querySelector('#truss-canvas svg'), { timeout: 20000 });
  await page.waitForTimeout(1500);

  await compose(page, { title: TITLE, tagline: TAGLINE });

  // Re-fit after the chrome came off, so the diagram uses the space it gained.
  // The control is `[data-fit]`, not an id: Truss's zoom bar is the one part of
  // the toolbar it wires by attribute.
  await page.evaluate(() => document.querySelector('#truss-app [data-fit]')?.click());
  await page.waitForTimeout(1500);

  await page.screenshot({ path: file, type: 'jpeg', quality: 92 });

  const bg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
  console.log(`${file}  ${width * 2}x${height * 2}  body ${bg}`);

  await browser.close();
}

// Only the two full images are shot. The thumbnails are the same frame scaled
// to 2560x1440, not a second capture at a smaller viewport: a 1280x720 viewport
// leaves the diagram less room, so Truss fits it at 32 percent instead of 48 and
// the thumbnail comes back with unreadable tables and a lot of empty grid. The
// composition should not change between the two sizes.
await shoot({ width: 1600, height: 900, dark: false, file: `${OUT}/image-light.jpg` });
await shoot({ width: 1600, height: 900, dark: true, file: `${OUT}/image-dark.jpg` });
