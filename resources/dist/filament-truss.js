/*
 * Joins Filament's dark mode to Truss's.
 *
 * The two express the same state differently and neither knows about the other.
 * Filament adds a `dark` class to <html>; Truss reads `data-theme="dark"` on the
 * same element, falling back to the operating system preference when it is
 * absent. Left alone, the panel follows its own toggle and the diagram follows
 * the machine, so a light panel can hold a dark diagram.
 *
 * Nothing is re-rendered here, and nothing needs to be: Truss initialises
 * Mermaid with a neutral base theme and paints the entity, row and line colours
 * from CSS variables, so changing the attribute is enough for the diagram to
 * follow. That is a property of Truss rather than of Mermaid, which normally
 * takes its theme variables at render time.
 *
 * Filament is the authority while this page is open. Truss's own theme button is
 * hidden by the stylesheet, and the observer below is the other half of that
 * decision: it puts the attribute back if anything else moves it.
 *
 * A classic script rather than a module, deliberately. A module is deferred to
 * the end of parsing, which would leave a window where Truss's stylesheet is
 * applied with no attribute to read, painting from the machine's preference
 * instead of the panel's. This runs where it sits, before the container below it
 * is parsed.
 *
 * Unit tested in tests/js/theme-bridge.test.js, which runs this file the way the
 * browser does.
 */
(function () {
    const root = document.documentElement;

    function sync() {
        const wanted = root.classList.contains('dark') ? 'dark' : 'light';

        // Only when it differs. Writing unconditionally would retrigger the
        // observer that called this, and the two would chase each other for as
        // long as the page is open.
        if (root.getAttribute('data-theme') !== wanted) {
            root.setAttribute('data-theme', wanted);
        }
    }

    sync();

    new MutationObserver(sync).observe(root, {
        attributes: true,
        attributeFilter: ['class', 'data-theme'],
    });

    /*
     * The second job: tell the stylesheet how much page is above the diagram.
     *
     * The container asks for the viewport height minus whatever the panel spent
     * on chrome above it, and that number cannot be written in CSS. It is a
     * topbar this package does not own, plus a heading whose height depends on
     * a subheading that wraps, in a theme that may not exist yet. It was a
     * literal `12rem` until a subheading was added and the box began hanging
     * below the fold by exactly the height of the line that had been added.
     *
     * So the number is measured and published as `--ft-top`, and the stylesheet
     * does the arithmetic. The `12rem` survives as the fallback in that
     * `calc()`, so a page where this never runs is no worse off than before.
     *
     * Unit tested in tests/js/fit-height.test.js.
     */
    function fit() {
        const app = document.getElementById('truss-app');

        if (!app) {
            return;
        }

        // From the document rather than from the viewport: a page restored
        // mid-scroll reports a negative `top`, and the container would be given
        // a height larger than the screen.
        const value = Math.round(app.getBoundingClientRect().top + window.scrollY) + 'px';

        // Only when it differs, for the same reason the theme sync above checks
        // first: this writes a property that changes the element's height, and
        // the observer below is watching the document for changes of that kind.
        if (app.style.getPropertyValue('--ft-top') !== value) {
            app.style.setProperty('--ft-top', value);
        }
    }

    function watch() {
        fit();

        window.addEventListener('resize', fit);

        // A window resize is not the only thing that moves the container. A
        // subheading wrapping to a second line, a sidebar collapsing, a font
        // arriving late: none of them fire a window event, and all of them
        // change where the diagram starts.
        if (typeof ResizeObserver === 'function' && document.body) {
            new ResizeObserver(fit).observe(document.body);
        }
    }

    // This file runs where it sits, above the container, so on first parse
    // there is nothing to measure yet.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watch, { once: true });
    } else {
        watch();
    }
})();
