// @vitest-environment happy-dom
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

// Resolved from the working directory rather than from `import.meta.url`, which
// a DOM environment rewrites to the document's URL and is then no longer a file
// path. The node-environment tests next door can use the tidier form.
const source = readFileSync(resolve(process.cwd(), 'resources/dist/filament-truss.js'), 'utf8');

/**
 * Run the shipped file against a root element and an observer of this test's own
 * making.
 *
 * Two things are injected, and both earn their keep:
 *
 * `document` is passed as an argument so the name is shadowed inside the file,
 * which gives each test its own root instead of a shared <html> carrying every
 * previous test's observer. The browser gets this file as a classic script rather
 * than a module, so there is nothing to import: it works on
 * `document.documentElement` as soon as it is parsed, which is the point (the
 * attribute is set before the diagram's container is parsed, so there is no
 * wrong-theme flash).
 *
 * `MutationObserver` is a stub that hands the callback back to the test instead
 * of firing on its own. **That is deliberate and not a shortcut**: this bridge
 * writes an attribute it also watches, so a regression in its write guard is an
 * unbounded loop. Left to a real observer, that loop starves the event loop, the
 * test timeout never gets a turn, and the regression reads as a hung CI job
 * rather than a failure. Driving the callback by hand makes the same bug a
 * one-line assertion.
 *
 * @returns {{ notify: () => void, watching: { target: unknown, options: object } }}
 */
function bridge(root) {
  const registrations = [];

  function MutationObserverStub(callback) {
    this.observe = (target, options) => registrations.push({ target, options, callback });
  }

  new Function('document', 'MutationObserver', source)(
    { documentElement: root },
    MutationObserverStub
  );

  expect(registrations).toHaveLength(1);

  return {
    notify: () => registrations[0].callback(),
    watching: registrations[0],
  };
}

function root(className = '') {
  const element = document.createElement('html');
  element.className = className;

  return element;
}

describe('the dark mode bridge', () => {
  it('publishes Filament dark class as the attribute Truss reads', () => {
    const html = root('dark');

    bridge(html);

    expect(html.getAttribute('data-theme')).toBe('dark');
  });

  it('says light rather than leaving Truss to guess from the operating system', () => {
    // Absence is not neutral: with no attribute Truss falls back to
    // prefers-color-scheme, so a light panel on a dark machine would hold a dark
    // diagram. The panel is the authority, so it has to say so out loud.
    const html = root();

    bridge(html);

    expect(html.getAttribute('data-theme')).toBe('light');
  });

  it('watches the class it reads and the attribute it writes, on the root', () => {
    // The class is what Filament toggles. The attribute is watched too so that
    // anything else moving it is put back, which is the other half of hiding
    // Truss's own theme button.
    const html = root();

    const { watching } = bridge(html);

    expect(watching.target).toBe(html);
    expect(watching.options.attributes).toBe(true);
    expect(watching.options.attributeFilter).toContain('class');
    expect(watching.options.attributeFilter).toContain('data-theme');
  });

  it('follows the panel when the theme is toggled', () => {
    const html = root();
    const { notify } = bridge(html);

    html.classList.add('dark');
    notify();

    expect(html.getAttribute('data-theme')).toBe('dark');

    html.classList.remove('dark');
    notify();

    expect(html.getAttribute('data-theme')).toBe('light');
  });

  it('corrects the attribute when something else moves it', () => {
    // Truss ships a theme button of its own. The stylesheet hides it, and this
    // is the same decision from the other side: while this page is open, the
    // panel is the only thing that decides.
    const html = root();
    const { notify } = bridge(html);

    html.setAttribute('data-theme', 'dark');
    notify();

    expect(html.getAttribute('data-theme')).toBe('light');
  });

  it('writes only when the value changes, so it cannot chase its own writes', () => {
    const html = root();

    // Theme writes only: `classList.add` is a `setAttribute` call under the
    // hood, and counting the class change the test itself made would say nothing
    // about the bridge.
    const writes = [];
    const setAttribute = html.setAttribute.bind(html);
    html.setAttribute = (name, value) => {
      if (name === 'data-theme') {
        writes.push(value);
      }

      setAttribute(name, value);
    };

    const { notify } = bridge(html);
    expect(writes).toEqual(['light']);

    // The write above is itself a mutation of a watched attribute, so the
    // observer hears about it. Nothing may come of it: a bridge that wrote
    // unconditionally here would be woken by its own write, for as long as the
    // page stayed open.
    notify();
    notify();

    expect(writes).toEqual(['light']);
  });
});
