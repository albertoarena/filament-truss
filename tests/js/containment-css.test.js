import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

const css = readFileSync(
  fileURLToPath(new URL('../../resources/dist/filament-truss.css', import.meta.url)),
  'utf8'
);

const withoutComments = css.replace(/\/\*[\s\S]*?\*\//g, '');

/** The declarations of the first rule whose selector contains the needle. */
function rule(needle) {
  const match = [...withoutComments.matchAll(/([^{}]+)\{([^}]*)\}/g)].find(
    ([, selector]) => selector.includes(needle)
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
    expect(rule('body')).toMatch(/background\s*:\s*none/);
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
    const viewport = rule('#truss-viewport');

    expect(viewport).toMatch(/flex\s*:\s*1 1 auto/);
    expect(viewport).toMatch(/min-height\s*:\s*0/);
  });

  it('hides the theme button, because the panel owns the theme', () => {
    // Hidden rather than removed from the markup: Truss reaches for this element
    // without checking whether it is there.
    expect(rule('#truss-theme-btn')).toMatch(/display\s*:\s*none/);
  });

  it('changes nothing on a panel page that has no diagram', () => {
    // This sheet is served to one page, but Truss's ids are global and a panel
    // may own elements of its own. Everything except the `body` reset stays
    // scoped to the container, so nothing here can reach a page without one.
    const unscoped = selectors().filter(
      (selector) => selector !== 'body' && ! selector.includes('.truss-embed')
    );

    expect(unscoped).toEqual([]);
  });
});
