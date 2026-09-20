// Shoots the composed listing cover from a running demo panel.
//
// Two stages, and the split is the point:
//
//   1. Photograph the real page. Panel chrome off, Truss's own toolbar kept,
//      the diagram opened out past its fit so the columns can be read rather
//      than fitted down to nothing.
//   2. Compose that photograph into the cover: wordmark, tagline, feature
//      pills, tilted window. The markup for that is `cover-template.mjs`,
//      which is a pure function with tests, because the composition is the
//      half that can be wrong without a browser.
//
// `shoot.mjs` next door is the plain, uncomposed variant: the page, cropped,
// with a heading. This one is what the plugin listing shows, where the image is
// competing with two hundred others in a grid.
//
// Playwright is not a dependency of this package and is resolved at run time,
// so point PLAYWRIGHT_MODULE at one that is already installed somewhere.
//
//   ART_BASE=http://localhost:8000 \
//   PLAYWRIGHT_MODULE=/path/to/node_modules/@playwright/test/index.mjs \
//   node art/cover.mjs art/filamentphp art/frames
//
// ART_REUSE=1 skips stage one and recomposes against the frames already in
// art/frames, which is how a change to the wordmark avoids re-photographing a
// database.
import { readFile, writeFile } from 'node:fs/promises';

import { coverHtml } from './cover-template.mjs';

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? '@playwright/test');

const OUT = process.argv[2] ?? 'art/filamentphp';
const FRAMES = process.argv[3] ?? 'art/frames';
const BASE = process.env.ART_BASE ?? 'http://localhost:8000';

// One composition, photographed twice. No screen here is 3200x1800, so the size
// comes from the device scale factor over a 1600x900 viewport.
//
// The thumbnail is the same frame at a lower factor rather than a second, smaller
// composition, and that is the lesson from the previous set: a smaller viewport
// gives the diagram less room, Truss fits it smaller, and the thumbnail comes
// back with unreadable tables. The composition must not change between the two.
const WIDTH = 1600;
const HEIGHT = 900;
const SIZES = [
  { name: 'image', scale: 2 },     // 3200x1800
  { name: 'thumbnail', scale: 1.6 }, // 2560x1440
];

const COVER = {
  eyebrow: 'Filament plugin',
  title: 'Filament',
  accent: 'Truss',
  tagline: 'A live ER diagram of your real database, as a native page in your panel. Structure only, never row data.',
  pills: ['Live ER diagram', 'Focus and depth', 'Structure health'],
  caption: 'Database schema',
  install: 'composer require albertoarena/filament-truss',
};

// The shot is captured at the size the cover displays it, and not larger. This
// is the whole fix for what the old image got wrong. Legibility on the cover is
// the diagram's zoom multiplied by however much the photograph is then scaled
// down to fit the frame, so a 1360-wide capture dropped into a 1088-wide frame
// turned a 64 percent diagram into 51 percent, and nothing in the tables could
// be read. Capture at the frame's own size and that second term is 1.
const SHOT = { width: 1100, height: 700 };

// How much of the viewport the diagram should span after the fit, as a multiple.
// Slightly over 1 on purpose: a diagram that stops short of all four edges reads
// as a picture of a diagram, one that runs off them reads as a window onto a
// schema bigger than the frame, which is what a real one is.
const BLEED = 1.32;

/** Photograph the page: panel chrome off, Truss's toolbar and footer kept. */
async function shootPage({ dark }) {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: SHOT,
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

  // Mermaid renders after load; wait for the diagram rather than guessing.
  await page.waitForFunction(() => !!document.querySelector('#truss-canvas svg'), { timeout: 20000 });
  await page.waitForTimeout(1500);

  await page.evaluate(() => {
    // The listing reviewer's note on the sibling package: crop out the sidebar
    // and the top bar, so the shot is the feature and not a panel. The page
    // heading goes with them, since the cover carries the name itself.
    for (const selector of ['.fi-sidebar', '.fi-topbar', '.fi-sidebar-close-overlay', '.fi-header']) {
      document.querySelector(selector)?.remove();
    }

    // Both, not either: the gutter is split between the two and removing one
    // leaves the diagram inset by the other.
    for (const selector of ['.fi-main-ctn', '.fi-main', '.fi-page']) {
      const element = document.querySelector(selector);
      if (element) {
        element.style.margin = '0';
        element.style.padding = '0';
        element.style.maxWidth = 'none';
      }
    }

    document.querySelector('.fi-body')?.style.setProperty('padding-inline-start', '0');

    // The container sizes itself as `100vh` minus the panel chrome it expects
    // above it, and that chrome has just been removed. Without this it keeps a
    // band of empty page above the diagram and loses the same off its bottom.
    const app = document.getElementById('truss-app');
    if (app) {
      app.style.height = `${window.innerHeight}px`;
      app.style.borderRadius = '0';
    }
  });

  // Re-fit after the chrome came off, so the diagram uses the space it gained.
  // The control is `[data-fit]`, not an id: Truss's zoom bar is the one part of
  // the toolbar it wires by attribute.
  await page.evaluate(() => document.querySelector('#truss-app [data-fit]')?.click());
  await page.waitForTimeout(1200);

  // Then open it out from the fit until it spans the frame. Measured rather
  // than guessed at a percentage, because the fit depends on the schema, and
  // this script should still compose something sensible against a database
  // that is not this demo's sixteen tables.
  await page.evaluate((bleed) => {
    const viewport = document.getElementById('truss-viewport').getBoundingClientRect();
    const diagram = document.querySelector('#truss-canvas svg g').getBoundingClientRect();
    if (!diagram.width || !diagram.height) return;

    // The narrower ratio: filling the width of a tall diagram would push its
    // top and bottom so far out of frame that it stops reading as a diagram.
    const grow = Math.min((viewport.width * bleed) / diagram.width, (viewport.height * bleed) / diagram.height);

    // The range input is Truss's own zoom control, so this goes through the
    // same path a scroll over the canvas does, and the zoom stays centred.
    const range = document.getElementById('truss-zoom-range');
    range.value = String(Math.min(Number(range.value) * grow, Number(range.max)));
    range.dispatchEvent(new Event('input', { bubbles: true }));
  }, BLEED);
  await page.waitForTimeout(900);

  // Send the whole overflow to the bottom edge. A diagram cut at the bottom
  // reads as one that carries on past the frame; the same cut at the top lands
  // on a table's title row, and a table with its name sliced off reads as a
  // rendering fault. Truss has no pan API, so this nudges the canvas transform
  // it has already written, and nothing touches the page afterwards but the
  // shutter.
  await page.evaluate(() => {
    const viewport = document.getElementById('truss-viewport').getBoundingClientRect();
    const canvas = document.getElementById('truss-canvas');
    const diagram = canvas.querySelector('svg g').getBoundingClientRect();

    const drop = Math.max(0, viewport.top - diagram.top) + 14;
    if (drop <= 14) return;

    canvas.style.transform = canvas.style.transform.replace(
      /translate\(([-\d.]+)px,\s*([-\d.]+)px\)/,
      (whole, x, y) => `translate(${x}px, ${Number(y) + drop}px)`
    );
  });
  await page.waitForTimeout(200);

  const zoom = await page.evaluate(() => document.getElementById('truss-zoom-pct')?.textContent);
  const buffer = await page.locator('#truss-app').screenshot({ type: 'png' });

  await browser.close();

  return { buffer, zoom };
}

/** Compose the cover around that photograph and photograph the result. */
async function shootCover({ dark, variant, shot, scale, name }) {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: WIDTH, height: HEIGHT },
    deviceScaleFactor: scale,
    colorScheme: dark ? 'dark' : 'light',
  });
  const page = await context.newPage();

  await page.setContent(coverHtml({ ...COVER, shot, dark, width: WIDTH, height: HEIGHT }), {
    waitUntil: 'load',
  });
  await page.waitForTimeout(400);

  const file = `${OUT}/${name}-${variant}.jpg`;
  await page.screenshot({ path: file, type: 'jpeg', quality: 92 });

  console.log(`  ${name.padEnd(9)} ${variant.padEnd(5)}  ${file}  ${WIDTH * scale}x${HEIGHT * scale}`);

  await browser.close();
}

/**
 * The page frame, from the panel, or from last time.
 *
 * The two halves fail for different reasons and at different speeds. Stage one
 * needs the demo running and takes seconds; stage two is a stylesheet and is
 * iterated on twenty times in a row. `ART_REUSE=1` keeps the frame and recomposes
 * around it, so a nudge to the wordmark does not re-photograph a database.
 */
async function frame({ dark, variant }) {
  const path = `${FRAMES}/page-${variant}.png`;

  if (process.env.ART_REUSE === '1') {
    console.log(`  page  ${variant.padEnd(5)}  reused from ${path}`);

    return `data:image/png;base64,${(await readFile(path)).toString('base64')}`;
  }

  const { buffer, zoom } = await shootPage({ dark });
  await writeFile(path, buffer);
  console.log(`  page  ${variant.padEnd(5)}  ${path}, diagram at ${zoom}`);

  return `data:image/png;base64,${buffer.toString('base64')}`;
}

for (const dark of [false, true]) {
  const variant = dark ? 'dark' : 'light';
  const shot = await frame({ dark, variant });

  for (const { name, scale } of SIZES) {
    await shootCover({ dark, variant, shot, name, scale });
  }
}
