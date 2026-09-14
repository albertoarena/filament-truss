<?php

declare(strict_types=1);

it('serves the stylesheet', function () {
    $this->get(route('filament-truss.asset', 'filament-truss.css'))
        ->assertOk()
        ->assertHeader('content-type', 'text/css; charset=UTF-8');
});

it('serves the script', function () {
    $this->get(route('filament-truss.asset', 'filament-truss.js'))
        ->assertOk()
        ->assertHeader('content-type', 'text/javascript; charset=UTF-8');
});

it('serves nothing that is not one of those two', function () {
    // An allow-list of exact names rather than a path check. There is no string
    // concatenation into a filesystem path here, so directory traversal is not
    // defended against, it is impossible.
    $this->get(route('filament-truss.asset', 'composer.json'))->assertNotFound();
});

it('refuses a traversal attempt without treating it as a path', function () {
    $this->get('/filament-truss/assets/'.urlencode('../../composer.json'))
        ->assertNotFound();
});

it('lets a browser cache what cannot change between deploys', function () {
    // The files ship inside the package, so their content is fixed for a given
    // installed version. Revalidating them on every panel page load would be a
    // request per page for bytes that cannot have changed.
    $response = $this->get(route('filament-truss.asset', 'filament-truss.css'));

    expect($response->headers->get('cache-control'))->toContain('max-age');
});
