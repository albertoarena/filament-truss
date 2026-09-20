// @vitest-environment happy-dom
//
// The listing cover is composed in HTML and then photographed, so the half that
// can go wrong without a browser is the markup: a missing pill, a tagline that
// swallows a `<`, a title painted a colour that only exists in one theme.
//
// That last one is not hypothetical. `art/README.md` records it: the first
// listing shot hard-coded `--gray-950` for the heading and the dark image came
// back with an invisible title. The template is a pure function so that failure
// mode is a test rather than a re-shoot.
import { describe, expect, it } from 'vitest';

import { coverHtml } from '../../art/cover-template.mjs';

const COVER = {
  eyebrow: 'Filament plugin',
  title: 'Filament',
  accent: 'Truss',
  tagline: 'A live ER diagram of your real database, as a native page in your panel.',
  pills: ['Live ER diagram', 'Schema changes', 'Structure health'],
  shot: 'data:image/png;base64,AAAA',
  caption: 'Database schema',
  install: 'composer require albertoarena/filament-truss',
};

function render(overrides = {}) {
  const html = coverHtml({ ...COVER, ...overrides });
  const doc = new DOMParser().parseFromString(html, 'text/html');

  return { html, doc };
}

describe('the listing cover template', () => {
  it('carries every piece of copy it was given', () => {
    const { doc } = render();
    const text = doc.body.textContent;

    for (const piece of [COVER.eyebrow, COVER.title, COVER.accent, COVER.tagline, COVER.caption, COVER.install, ...COVER.pills]) {
      expect(text).toContain(piece);
    }
  });

  it('sets the install line in a monospace face', () => {
    // It is a command, and a command set in the same face as the prose reads
    // as a claim about the package rather than something to type.
    const { doc } = render();

    expect(doc.querySelector('code')?.textContent).toBe(COVER.install);
  });

  it('shows the shot it was given, and only that one', () => {
    const { doc } = render();
    const images = [...doc.querySelectorAll('img')];

    expect(images).toHaveLength(1);
    expect(images[0].getAttribute('src')).toBe(COVER.shot);
  });

  it('escapes copy rather than pasting it into the markup', () => {
    // A tagline is prose and prose contains punctuation. `&` and `<` are the
    // two that turn a caption into broken markup, silently, in an image.
    const { doc } = render({ tagline: 'Structure & <b>never</b> row data' });

    expect(doc.querySelectorAll('b')).toHaveLength(0);
    expect(doc.body.textContent).toContain('Structure & <b>never</b> row data');
  });

  it('asks for the exact canvas the listing wants', () => {
    const { doc } = render({ width: 1600, height: 900 });
    const stage = doc.querySelector('[data-stage]');

    expect(stage.style.width).toBe('1600px');
    expect(stage.style.height).toBe('900px');
  });

  it('paints both themes from the same tokens', () => {
    // Every colour the page uses is a custom property, declared once per theme.
    // A literal colour anywhere else is a value one theme will get wrong, which
    // is exactly how the invisible dark title happened.
    const { html } = render();
    const declarations = html
      .replace(/:root\[data-theme="dark"\]\s*\{[^}]*\}/g, '')
      .replace(/:root\s*\{[^}]*\}/g, '');

    expect(declarations).not.toMatch(/#[0-9a-f]{3,8}\b/i);
    expect(declarations).not.toMatch(/\b(rgb|rgba|hsl|hsla)\(/i);
  });

  it('declares the same token set for dark as for light', () => {
    const { html } = render();
    const tokens = (block) => [...block.matchAll(/(--[a-z0-9-]+)\s*:/g)].map((m) => m[1]).sort();

    const light = html.match(/:root\s*\{([^}]*)\}/)[1];
    const dark = html.match(/:root\[data-theme="dark"\]\s*\{([^}]*)\}/)[1];

    expect(tokens(dark)).toEqual(tokens(light));
  });

  it('marks the document with the theme it was asked for', () => {
    expect(render({ dark: true }).doc.documentElement.getAttribute('data-theme')).toBe('dark');
    expect(render({ dark: false }).doc.documentElement.getAttribute('data-theme')).toBeNull();
  });

  it('reaches for nothing over the network', () => {
    // The page is photographed by a headless browser that may have no route
    // out, and a webfont that fails to arrive is a cover set in Times.
    const { html } = render();

    expect(html).not.toMatch(/https?:\/\//);
  });
});
