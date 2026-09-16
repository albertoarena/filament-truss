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

/**
 * The declarations of every rule whose selector mentions this fragment, joined.
 *
 * The exact match above is right for a block asserted as a whole. The toolbar
 * chrome is deliberately several rules (light, dark, focus, and one per control
 * type), so these read the group rather than one member of it.
 */
function rulesFor(fragment) {
  return [...withoutComments.matchAll(/([^{}]+)\{([^}]*)\}/g)]
    .filter(([, selector]) => selector.includes(fragment))
    .map(([, , declarations]) => declarations)
    .join('\n');
}

/** Every rule in the sheet as a [selector, declarations] pair. */
function rules() {
  return [...withoutComments.matchAll(/([^{}]+)\{([^}]*)\}/g)].map(
    ([, selector, declarations]) => [selector.trim(), declarations]
  );
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

  it('hands the panel its typeface back, for the same reason as the background', () => {
    // The same trap as the background, and only half of it was seen the first
    // time. `truss.css` styles `body` for a page it owns, and that includes the
    // font. The rule is unlayered, Filament's is inside a Tailwind layer, and an
    // unlayered rule wins however weak its selector.
    //
    // So the whole page rendered in `system-ui` on this page alone: heading,
    // subheading, sidebar, topbar, and Filament's own search input. Measured
    // rather than noticed, because system-ui and Inter are close enough to
    // survive a glance.
    expect(rule('body')).toMatch(/font-family:\s*var\(--default-font-family\)/);
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

describe('the toolbar, wearing the panel\'s own controls', () => {
  // Truss styles its toolbar for a dashboard it owns: monospace, a 2px radius
  // and a hairline border. Filament's inputs are its own typeface, rounded to
  // `--radius-lg`, and carry a ring and a shadow instead of a border. The
  // palette above already gives these their colours, so this is what is left,
  // and next to a real Filament search field it is the last thing that still
  // reads as a visitor in the panel.
  //
  // The rules hang off `ft-controls`, a class this package adds in its own
  // Blade, rather than off Truss's `.truss-toolbar`. The markup is ours to
  // write, so an upstream rename should cost us the Blade and not this sheet
  // as well.
  const chrome = rulesFor('.ft-controls');

  it('wears the panel typeface rather than the diagram monospace', () => {
    expect(chrome).toContain('var(--default-font-family)');
  });

  it('rounds to the panel radius rather than Truss\'s 2px', () => {
    // A custom property, not 0.5rem written out, so a panel that changes its
    // radius takes the toolbar with it.
    expect(chrome).toMatch(/border-radius:\s*var\(--radius-lg\)/);
  });

  it('trades the hairline border for Filament\'s ring and shadow', () => {
    // Filament draws no border at all: the outline is a 1px ring of the darkest
    // grey at 10 percent, over a soft shadow. Measured from a rendered input in
    // a panel rather than guessed at.
    expect(chrome).toMatch(/border:\s*0/);
    expect(chrome).toMatch(/box-shadow:[^;]*var\(--gray-950\)/);
  });

  it('rings in the panel primary on focus, in both modes', () => {
    // Truss rings in `--bp-ink` at 22 percent and keeps a border. Filament
    // replaces the grey ring with a solid 2px primary one, and steps the shade
    // down in dark so it does not glare.
    expect(rulesFor('.ft-controls input:focus')).toContain('var(--primary-600)');
    expect(rulesFor(':root.dark #truss-app.truss-embed .ft-controls input:focus')).toContain(
      'var(--primary-500)'
    );
  });

  it('gives the checkboxes the panel accent', () => {
    // The two toggles are native checkboxes, and a native checkbox painted by
    // the operating system is the loudest wrong colour on the page.
    expect(chrome).toMatch(/accent-color:\s*var\(--primary-/);
  });

  it('answers for dark with Filament\'s own class, not the bridged attribute', () => {
    // The palette uses `data-theme`, because it is Truss's own switch. This is
    // Filament's chrome, so it follows Filament's class and is right on the
    // first paint, before the bridge script has run.
    expect(rulesFor(':root.dark #truss-app.truss-embed .ft-controls')).not.toBe('');
  });

  it('leaves the diagram in its monospace', () => {
    // Truss reads a schema in the canvas and column names line up because they
    // are monospaced. Only the toolbar is being restyled here, and a blanket
    // font rule would quietly take the diagram with it.
    const typography = rules()
      .filter(([, declarations]) => declarations.includes('font-family'))
      // `body` is the one exception, and it is the opposite of a font choice:
      // it hands the page back to whatever typeface Filament set, after
      // `truss.css` took it.
      .filter(([selector]) => selector !== 'body');

    expect(typography).not.toEqual([]);
    expect(typography.every(([selector]) => selector.includes('.ft-controls'))).toBe(true);
  });
});

describe('the toolbar, given room to stand in', () => {
  // The bar itself, not the controls in it, so the assertions below cannot pass
  // on the padding of an input.
  const controls = rule('#truss-app.truss-embed .ft-controls');

  it('refuses to be squeezed by the diagram below it', () => {
    // The container is a column flexbox, so the toolbar is a flex child and
    // shrinks: Truss asks for 54px and it was rendering at 43. That is what
    // made it look cramped once the controls grew to Filament's 36px, and it
    // is also what clipped the health badge, which rides 4px above its button
    // and so ended up over the container's own `overflow: hidden` edge.
    expect(controls).toMatch(/flex:\s*none/);
  });

  it('pads above and below, now the controls are Filament sized', () => {
    expect(controls).toMatch(/padding:[^;]*rem/);
  });
});

describe('the panel palette, on the states Truss paints blue', () => {
  const light = rule('.truss-embed');
  const dark = rule(':root[data-theme="dark"] .truss-embed');

  it('fills a focused table like a Filament card, not in cyan', () => {
    // `--bp-focus-bg` is Truss's pale cyan and it fills the focused table's
    // name band, which read as a different design system sitting inside the
    // panel. White over the panel's grey is what a Filament card does, and the
    // primary border already carries the focus signal on its own.
    expect(light).toMatch(/--bp-focus-bg:\s*var\(--color-white\)/);
    expect(dark).toMatch(/--bp-focus-bg:\s*var\(--gray-/);
  });

  it('hovers a menu item in the panel grey rather than Truss\'s blue', () => {
    // Truss paints the export menu's hover and the focus combobox's active
    // option from `--bp-info-bg`, which is a pale blue. Overridden here rather
    // than by remapping the token, because that token also carries the meaning
    // "info" in the banners and the health panel, and a hover state is not a
    // severity.
    //
    // `--gray-100` and not Filament's own `--gray-50`: Filament hovers over a
    // white dropdown, and Truss's menu panel is already `--gray-50`, so that
    // value would be a hover with nothing to show. Found by hovering one.
    expect(rulesFor('.truss-menu button')).toMatch(/background:\s*var\(--gray-100\)/);

    // The dim on an export this page cannot perform is weaker than these
    // selectors, so a disabled item would otherwise light up on hover.
    expect(selectors().filter((s) => s.includes('.truss-menu')).join()).toContain('aria-disabled');
  });
});
