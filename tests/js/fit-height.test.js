// @vitest-environment happy-dom
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

// Same resolution as the bridge test next door: a DOM environment rewrites
// `import.meta.url` to the document's URL, which is no longer a file path.
const source = readFileSync(resolve(process.cwd(), 'resources/dist/filament-truss.js'), 'utf8');

/**
 * Run the shipped file against a fake page, and hand back the hooks it took.
 *
 * The container's height is `100vh` minus whatever the panel put above it. That
 * number cannot be written in CSS, because it is the height of a heading this
 * package does not control, in a theme it has never seen. So the file measures
 * it and publishes it as a custom property, and the stylesheet does the
 * arithmetic.
 *
 * Everything the file touches is injected, for the same reason the bridge test
 * injects its observer: these tests are about what the file asks for and when,
 * and a real observer would answer on its own schedule.
 *
 * @param {{top?: number, scrollY?: number, readyState?: string, container?: boolean}} page
 */
function run(page = {}) {
  const { top = 228, scrollY = 0, readyState = 'complete', container = true } = page;

  const style = new Map();
  const writes = [];
  const app = {
    getBoundingClientRect: () => ({ top }),
    style: {
      setProperty: (name, value) => {
        writes.push([name, value]);
        style.set(name, value);
      },
      getPropertyValue: (name) => style.get(name) ?? '',
    },
  };

  const documentListeners = [];
  const windowListeners = [];
  const resizeTargets = [];

  const fakeDocument = {
    documentElement: { classList: { contains: () => false }, getAttribute: () => null, setAttribute: () => {} },
    readyState,
    body: {},
    getElementById: (id) => (container && id === 'truss-app' ? app : null),
    addEventListener: (type, handler, options) => documentListeners.push({ type, handler, options }),
  };

  const fakeWindow = {
    scrollY,
    addEventListener: (type, handler) => windowListeners.push({ type, handler }),
  };

  function MutationObserverStub() {
    this.observe = () => {};
  }

  function ResizeObserverStub(callback) {
    this.observe = (target) => resizeTargets.push({ target, callback });
  }

  new Function('document', 'MutationObserver', 'window', 'ResizeObserver', source)(
    fakeDocument,
    MutationObserverStub,
    fakeWindow,
    ResizeObserverStub
  );

  return {
    top: () => style.get('--ft-top'),
    writes: () => writes,
    fire: (type) => windowListeners.filter((l) => l.type === type).forEach((l) => l.handler()),
    ready: () => documentListeners.filter((l) => l.type === 'DOMContentLoaded').forEach((l) => l.handler()),
    documentListeners,
    windowListeners,
    resizeTargets,
  };
}

describe('the height the panel left over', () => {
  it('publishes where the container starts, so the stylesheet can subtract it', () => {
    // 12rem was the guess this replaced, and it was right until the page grew a
    // subheading: the container then began 228px down and kept asking for
    // 100vh minus 192, so it hung 36px below the fold on a laptop.
    expect(run({ top: 228 }).top()).toBe('228px');
  });

  it('measures from the document rather than from the viewport', () => {
    // `getBoundingClientRect` is relative to the viewport, so a page restored
    // mid-scroll would otherwise report a container that starts above the top
    // of the window and be given a height larger than the screen.
    expect(run({ top: 28, scrollY: 200 }).top()).toBe('228px');
  });

  it('writes nothing when the measurement has not moved', () => {
    // The property it writes changes the element's height, and the observer it
    // registers watches the document for changes of exactly that kind. Without
    // this guard the two answer each other for as long as the page is open.
    const page = run({ top: 228 });

    expect(page.writes()).toHaveLength(1);

    page.fire('resize');
    page.fire('resize');

    expect(page.writes()).toHaveLength(1);
    expect(page.top()).toBe('228px');
  });

  it('measures again when the window is resized', () => {
    expect(run().windowListeners.map((l) => l.type)).toContain('resize');
  });

  it('measures again when the page itself changes shape', () => {
    // A resize is not the only thing that moves the container: a subheading
    // wrapping to a second line, a sidebar collapsing, or a font arriving late
    // all do, and none of them fire a window event.
    const page = run();

    expect(page.resizeTargets).toHaveLength(1);
    expect(page.resizeTargets[0].callback).toBeTypeOf('function');
  });

  it('waits for the document when it is still parsing', () => {
    // The file is a classic script that runs where it sits, above the container,
    // so on first parse there is nothing to measure yet.
    const page = run({ readyState: 'loading' });

    expect(page.top()).toBeUndefined();
    expect(page.documentListeners.map((l) => l.type)).toContain('DOMContentLoaded');

    page.ready();

    expect(page.top()).toBe('228px');
  });

  it('does nothing at all on a page with no diagram', () => {
    // The stylesheet and this file are served to one page, but a host that
    // includes them elsewhere should get silence rather than a type error.
    expect(() => run({ container: false })).not.toThrow();
  });
});
