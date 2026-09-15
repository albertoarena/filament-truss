import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

const css = readFileSync(
  fileURLToPath(new URL('../../resources/dist/filament-truss.css', import.meta.url)),
  'utf8'
);

const withoutComments = css.replace(/\/\*[\s\S]*?\*\//g, '');

/**
 * The declarations of the rule with exactly this selector.
 *
 * Exact rather than a substring match, because the palette rules below are
 * scoped to the same element as the layout rules and a loose match would happily
 * assert against the wrong block.
 */
function rule(selector) {
  const match = [...withoutComments.matchAll(/([^{}]+)\{([^}]*)\}/g)].find(
    ([, found]) => found.trim().replace(/\s+/g, ' ') === selector
  );

  return match ? match[2] : '';
}

/** Every selector in the sheet, one entry per rule. */
function selectors() {
  return [...withoutComments.matchAll(/([^{}]+)\{[^}]*\}/g)].map(([, selector]) =>
    selector.trim()
  );
}

describe('the containment stylesheet', () => {
  it('hands the page background back to the panel', () => {
    // Truss styles `body` because it renders a page it owns. Inside a panel that
    // paints the blueprint grid across the whole admin, sidebar included.
    //
    // **Repainting it is not optional, and cancelling it is not enough.**
    // Filament's own `.fi-body` background lives inside a Tailwind layer, and an
    // unlayered rule beats a layered one whatever its specificity, so Truss's
    // plain `body` rule was already winning before this sheet existed. Anything
    // here that merely removes it leaves the page with no background at all:
    // invisible in light, where the canvas is white anyway, and a white slab
    // around the diagram in dark.
    expect(rule('body')).toMatch(/background\s*:\s*var\(--gray-50\)/);
    expect(rule(':root.dark body')).toMatch(/background\s*:\s*var\(--gray-950\)/);
  });

  it('follows the panel class for that, not the mirrored attribute', () => {
    // The palette below keys off `data-theme`, which is Truss's state and is set
    // by the bridge script. The page background is Filament's own business, so it
    // keys off Filament's own class: correct on first paint, with no dependency
    // on our JavaScript having run.
    expect(selectors()).toContain(':root.dark body');
  });

  it('repaints the grid where the grid belongs', () => {
    // Taking it off `body` is only half the job: the diagram should still sit on
    // the grid, so the same gradients are rescoped to the container.
    const embed = rule('#truss-app.truss-embed');

    expect(embed).toContain('linear-gradient');
    expect(embed).toContain('var(--bp-grid)');
  });

  it('gives the diagram a height of its own', () => {
    // Truss's viewport fills the window on a page it owns. Here it has to fit a
    // panel that already spent space on its own chrome, so the container is the
    // thing that owns the height.
    const embed = rule('#truss-app.truss-embed');

    expect(embed).toMatch(/display\s*:\s*flex/);
    expect(embed).toMatch(/flex-direction\s*:\s*column/);
    expect(embed).toMatch(/height\s*:/);
    expect(embed).toMatch(/min-height\s*:/);
    expect(embed).toMatch(/overflow\s*:\s*hidden/);
  });

  it('lets the viewport shrink inside that column', () => {
    // `min-height: 0` is the load-bearing half. A flex child defaults to
    // min-height auto, so a tall diagram pushes the container past the height
    // set above instead of scrolling inside it.
    const viewport = rule('#truss-app.truss-embed #truss-viewport');

    expect(viewport).toMatch(/flex\s*:\s*1 1 auto/);
    expect(viewport).toMatch(/min-height\s*:\s*0/);
  });

  it('hides the theme button, because the panel owns the theme', () => {
    // Hidden rather than removed from the markup: Truss reaches for this element
    // without checking whether it is there.
    expect(rule('#truss-app.truss-embed #truss-theme-btn')).toMatch(/display\s*:\s*none/);
  });

  it('leaves the palette to the rules below', () => {
    // Layout and colour are separate jobs in this sheet. The layout rules exist
    // so the diagram fits a panel; the palette rules exist so it looks like one.
    const embed = rule('#truss-app.truss-embed');

    expect(embed).not.toContain('--bp-entity-bg');
  });

  it('changes nothing on a panel page that has no diagram', () => {
    // This sheet is served to one page, but Truss's ids are global and a panel
    // may own elements of its own. Everything except the `body` reset stays
    // scoped to the container, so nothing here can reach a page without one.
    const unscoped = selectors().filter(
      (selector) => ! /(^|\s)body$/.test(selector) && ! selector.includes('.truss-embed')
    );

    expect(unscoped).toEqual([]);
  });
});

describe('the panel palette', () => {
  // Truss repaints the Mermaid output from these variables with `!important`
  // rules of its own, which is why a diagram follows a theme change with no
  // re-render. Redefining them on the container is therefore the whole
  // mechanism: no JavaScript, no second renderer, no palette to keep in step.
  const light = rule('.truss-embed');
  const dark = rule(':root[data-theme="dark"] .truss-embed');

  it('takes its colours from the panel instead of matching them', () => {
    // Filament emits its own palette as custom properties, generated from the
    // panel's colour configuration, so a custom panel colour arrives here for
    // free and a theme that does not exist yet still works.
    expect(light).toContain('var(--primary-');
    expect(light).toContain('var(--gray-');
  });

  it('gives the accent to the panel primary rather than to a grey', () => {
    // The accent is what a person reads as "this is a Filament page": headings,
    // primary key badges, the focus ring.
    expect(light).toMatch(/--bp-ink:\s*var\(--primary-/);
    expect(dark).toMatch(/--bp-ink:\s*var\(--primary-/);
  });

  it('answers for dark as well as light', () => {
    // The bridge sets data-theme from Filament's class, so this is the block
    // that actually runs when the panel is dark.
    expect(dark).toContain('var(--gray-');
  });

  it('names no colour of its own', () => {
    // A hex literal here would be a palette this package invented, which is the
    // thing that is wrong by the next Filament release.
    expect(light).not.toMatch(/#[0-9a-f]{3,8}\b/i);
    expect(dark).not.toMatch(/#[0-9a-f]{3,8}\b/i);
  });
});
