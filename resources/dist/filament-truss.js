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
})();
