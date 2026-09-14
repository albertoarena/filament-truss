<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Pages\SchemaPage;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    app()->detectEnvironment(fn () => 'local');

    Schema::create('authors', function ($table) {
        $table->id();
        $table->string('name');
    });
});

function diagramHtml(): string
{
    return view('filament-truss::diagram', (new SchemaPage)->getViewData())->render();
}

it('loads the containment stylesheet after Truss own sheet', function () {
    // Order is the whole mechanism. Truss styles `body` with its blueprint grid,
    // which is correct for a page it owns and wrong inside a panel, and the only
    // way to take that back is to be later in the cascade.
    $html = diagramHtml();

    $truss = strpos($html, 'truss/assets/truss.css');
    $ours = strpos($html, 'filament-truss.css');

    expect($truss)->not->toBeFalse()
        ->and($ours)->not->toBeFalse()
        ->and($ours)->toBeGreaterThan($truss);
});

it('loads the dark-mode bridge', function () {
    // Filament toggles a class on <html>; Truss reads a data attribute. Without
    // something joining them the panel and the diagram disagree about dark mode.
    expect(diagramHtml())->toContain('filament-truss.js');
});
