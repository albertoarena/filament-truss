<?php

declare(strict_types=1);

use AlbertoArena\Truss\Theme\ThemeStylesheet;

/**
 * The palette drift guard, and the twin of the markup one next door.
 *
 * Truss publishes eight semantic theme knobs (accent, surface, muted and so on)
 * and says plainly that the `--bp-*` tokens behind them are private. This
 * package cannot use the knobs: they are config, read once on the server, and a
 * panel picks its theme in the browser. So it drives the private tokens instead,
 * which is a deliberate and bounded cost of the same kind as reproducing the
 * container markup.
 *
 * Reading the private map here is the price of that choice being safe. A token
 * renamed upstream would otherwise leave the diagram quietly half-painted in
 * Truss's colours, on the upgrade, with nothing failing.
 */
function paletteTokens(): array
{
    $knobs = (new ReflectionClassConstant(ThemeStylesheet::class, 'KNOBS'))->getValue();

    return array_values(array_unique(array_merge(...array_values($knobs))));
}

function paletteBlock(string $selector): string
{
    $css = file_get_contents(__DIR__.'/../../resources/dist/filament-truss.css');

    // Anchored to the start of a line, or `.truss-embed` would also match inside
    // `#truss-app.truss-embed`, which is the layout rule and carries no palette.
    preg_match('/^'.preg_quote($selector, '/').'\s*\{([^}]*)\}/m', $css, $matches);

    return $matches[1] ?? '';
}

it('knows which tokens a Truss theme paints', function () {
    // If this fails, the reflection above found nothing and every other
    // assertion in this file would pass by vacuum.
    expect(paletteTokens())->not->toBeEmpty()
        ->and(paletteTokens())->toContain('entity-bg');
});

it('paints every token a configured Truss theme would paint', function () {
    // Anything but the full set is a diagram wearing two palettes at once.
    $missing = array_values(array_filter(
        paletteTokens(),
        fn (string $token): bool => ! str_contains(paletteBlock('.truss-embed'), '--bp-'.$token.':')
    ));

    expect($missing)->toBe([]);
});

it('paints the same set again for dark', function () {
    $missing = array_values(array_filter(
        paletteTokens(),
        fn (string $token): bool => ! str_contains(
            paletteBlock(':root[data-theme="dark"] .truss-embed'),
            '--bp-'.$token.':'
        )
    ));

    expect($missing)->toBe([]);
});
