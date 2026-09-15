<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Http\Asset;

it('versions the asset URL by the bytes it serves', function () {
    // The bug this prevents is quiet and lasts a day: the URL was fixed, the
    // response said cache for hours, so a host who upgraded the package kept
    // being served the previous stylesheet and script against the new markup.
    // Nothing errors in that state. It just looks wrong.
    expect(Asset::url('filament-truss.css'))->toMatch('/\?v=[a-z0-9]+$/');
});

it('changes that version when the file changes', function () {
    $path = __DIR__.'/../../resources/dist/filament-truss.css';
    $before = Asset::url('filament-truss.css');

    touch($path, time() + 60);
    clearstatcache(true, $path);

    expect(Asset::url('filament-truss.css'))->not->toBe($before);
})->after(function () {
    // Leave the working tree as it was found, or the next run of the suite is
    // comparing against a timestamp this test invented.
    touch(__DIR__.'/../../resources/dist/filament-truss.css');
});

it('keeps the route it versions', function () {
    expect(Asset::url('filament-truss.js'))
        ->toStartWith(route('filament-truss.asset', 'filament-truss.js'));
});
