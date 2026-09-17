<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The diagram markup, rendered on its own.
 *
 * The partial is deliberately separable from the Filament page that includes
 * it: the page contributes panel chrome and nothing else, so rendering it here
 * would mean booting Livewire and a layout to assert on markup neither of them
 * produces. The page's own job, deciding who may see it, is tested next door.
 */
function renderDiagram(): string
{
    return view('filament-truss::diagram', (new SchemaPage)->getViewData())->render();
}

beforeEach(function () {
    app()->detectEnvironment(fn () => 'local');

    Schema::create('authors', function ($table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('books', function ($table) {
        $table->id();
        $table->foreignId('author_id')->constrained();
        $table->string('title');
    });
});

it('embeds the payload in the page rather than making the app fetch from itself', function () {
    $html = renderDiagram();

    expect($html)->toContain('data-truss-payload')
        ->and($html)->toContain('"authors"')
        ->and($html)->toContain('"books"');
});

it('needs no schema endpoint, because it never asks for one', function () {
    // The point of embedding. A page carrying an endpoint it does not use would
    // still work, but it would tie this page to a route that only exists while
    // Truss's own dashboard is enabled.
    expect(renderDiagram())->not->toContain('data-schema-endpoint');
});

it('offers the exports Truss generates, because there is a server here', function () {
    // The page embeds its payload, so it asks for no schema. That is not a
    // reason to drop the export route as well: Markdown, DBML, JSON and CSV are
    // generated in the application, the route is right there behind the same
    // authorization this page matches, and without the attribute Truss decides
    // it has no server and greys those four out. PNG and SVG are drawn from the
    // DOM and were never affected, which is what makes the gap easy to miss.
    $html = renderDiagram();

    expect($html)->toContain('data-export-endpoint')
        ->and($html)->toContain('/truss/export/__format__');
});

it('hands on the exclusion count, which the footer reads to say how much is shown', function () {
    // Truss v1.13.0 reports how many tables `excluded_tables` removed, and its
    // footer turns that into "8 of 17 tables" rather than presenting a filtered
    // diagram as the whole schema. The count is arithmetic done upstream and the
    // payload is handed on whole, so there is nothing here to get right and
    // exactly one way to get it wrong: trimming the payload to make the page
    // smaller. A count, never the names.
    config()->set('truss.excluded_tables', ['books']);
    config()->set('truss.reveal_excluded', false);

    $html = renderDiagram();

    expect($html)->toContain('"excluded":{"count":1}')
        ->and($html)->toContain('"authors"')
        ->and($html)->not->toContain('"books"');
});

it('sends the hidden tables only where Truss config allows it', function () {
    // The other half of the same switch, and the reason the toggle in the
    // toolbar is Truss's rather than ours. `reveal_excluded` is the operator's
    // decision, made in Truss config, and this page neither asks nor overrides:
    // it renders what the facade returns. Set explicitly in both tests because
    // the default is resolved from `APP_ENV` when config loads, so leaving it
    // ambient makes the pair pass or fail on where they are run.
    config()->set('truss.excluded_tables', ['books']);
    config()->set('truss.reveal_excluded', true);

    $html = renderDiagram();

    // Marked, so Truss's frontend holds it back until the viewer asks. Still
    // structure: a name, its columns and its keys, and no rows.
    expect($html)->toContain('"excluded":true')
        ->and($html)->toContain('"books"');
});

it('borrows the panel\'s own checkbox rather than imitating one', function () {
    // The one control the stylesheet cannot convincingly fake. A native
    // checkbox is painted by the operating system, and `accent-color` reaches
    // only the checked fill, so an unchecked box stays whatever grey the OS
    // fancies next to Filament's own rounded, ringed one.
    //
    // Filament styles `input[type=checkbox].fi-checkbox-input` outright, with
    // `appearance: none` and rules for the checked, focus, disabled and dark
    // states. It needs no wrapper and no markup of Filament's around it, which
    // is what makes this worth borrowing where the text inputs are not.
    //
    // Asserted over every checkbox rather than a count, so a control added here
    // later cannot quietly ship unstyled.
    preg_match_all('/<input[^>]*type="checkbox"[^>]*>/', renderDiagram(), $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $checkbox) {
        expect($checkbox)->toContain('fi-checkbox-input');
    }
});

it('embeds structure and nothing else', function () {
    DB::table('authors')->insert(['name' => 'Ada Lovelace']);

    // The one promise this package inherits and must never break.
    expect(renderDiagram())->not->toContain('Ada Lovelace');
});

it('loads the Truss frontend from Truss, rather than shipping a copy', function () {
    $html = renderDiagram();

    expect($html)->toContain('truss.js')
        ->and($html)->toContain('truss.css')
        ->and($html)->toContain('mermaid.min.js');
});

it('provides every element the Truss frontend reaches for', function () {
    // The drift guard, and the reason it exists: Truss's container markup is
    // not a public contract. Its frontend resolves elements by id and reaches
    // most of them without a null guard, so an id added upstream becomes a
    // broken page here with no other warning. This fails on the upgrade that
    // introduces one, which is the only cheap moment to find out.
    $upstream = file_get_contents(
        __DIR__.'/../../vendor/albertoarena/laravel-truss/resources/views/index.blade.php'
    );

    preg_match_all('/id="(truss-[a-z-]+)"/', $upstream, $matches);
    $expected = array_unique($matches[1]);

    expect($expected)->not->toBeEmpty();

    $html = renderDiagram();

    $missing = array_values(array_filter(
        $expected,
        fn (string $id): bool => ! str_contains($html, 'id="'.$id.'"')
    ));

    expect($missing)->toBe([]);
});

it('is included by the page that hosts it', function () {
    // The partial above is only worth testing if the page actually shows it.
    $view = file_get_contents(__DIR__.'/../../resources/views/pages/schema.blade.php');

    expect($view)->toContain('filament-truss::diagram');
});

/**
 * The toolbar's glyphs, which are ours and not Truss's.
 *
 * The buttons themselves belong to Truss, which finds them by id and would
 * break if one went missing, but what is drawn inside them is written here. The
 * drift guard above checks ids, so swapping the contents cannot reach it.
 */
function utilButton(string $id): string
{
    preg_match(
        '/<button[^>]*id="'.preg_quote($id, '/').'"[^>]*>(.*?)<\/button>/s',
        renderDiagram(),
        $matches
    );

    return $matches[1] ?? '';
}

it('draws the toolbar buttons with icons rather than with typography', function () {
    // `⋯` and `▤` were characters, sized by font-size and drawn by whatever the
    // platform had to hand. Beside a Filament icon button they read as text,
    // because that is what they were.
    foreach (['truss-more-btn', 'truss-legend-btn', 'truss-export-btn'] as $id) {
        expect(utilButton($id))->toContain('<svg');
    }

    $html = renderDiagram();

    expect($html)->not->toContain('⋯')
        ->and($html)->not->toContain('▤');
});

it('takes those three from the icon set the panel already ships', function () {
    // Heroicons, through Filament's own component, so a panel is drawing them
    // from the same set as every other icon on the page. `data-slot="icon"` is
    // what the shipped files carry, and a hand-drawn path would not.
    foreach (['truss-more-btn', 'truss-legend-btn', 'truss-export-btn'] as $id) {
        expect(utilButton($id))->toContain('data-slot="icon"');
    }
});

it('draws the two it keeps at the same weight as the rest', function () {
    // Diff and health stay, because no icon set has a glyph for "what changed
    // since the last migration" and a refresh arrow would say something untrue,
    // and because Truss's health icon is a heart with a pulse trace where
    // Heroicons has a plain heart. What they cannot keep is Truss's stroke: 2 on
    // a 24 grid beside Heroicons' 1.5 is what makes a mixed set look mixed.
    $svgs = [];
    preg_match_all('/<svg[^>]*>/', renderDiagram(), $svgs);

    $toolbar = array_filter($svgs[0], fn (string $tag): bool => str_contains($tag, 'viewBox="0 0 24 24"'));

    expect($toolbar)->not->toBeEmpty();

    foreach ($toolbar as $tag) {
        expect($tag)->toContain('stroke-width="1.5"');
    }
});

it('keeps the class the health pulse hangs off', function () {
    // Truss animates `.truss-health-icon` when the doctor reports a warning or
    // an error, with a reduced-motion opt-out. Restyling the icon and dropping
    // the class would stop the badge pulsing, and nothing would say so.
    expect(utilButton('truss-health-btn'))->toContain('truss-health-icon');
});
