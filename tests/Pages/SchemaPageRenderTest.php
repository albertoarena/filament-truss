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
