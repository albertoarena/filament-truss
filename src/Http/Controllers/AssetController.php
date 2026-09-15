<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Serves this package's two static files from inside the package.
 *
 * No publish step, and no `filament:assets` to forget. Filament's asset manager
 * is the idiomatic route, but it copies files into `public/` on a command, so a
 * host who upgrades and does not re-run it gets a page that is subtly wrong
 * rather than obviously broken. These two files are small and change only when
 * the package does, so serving them directly is cheaper than a ritual.
 *
 * **An allow-list of exact names, not a path.** Nothing here concatenates user
 * input into a filesystem path, so traversal is not defended against, it cannot
 * be expressed.
 */
class AssetController
{
    private const FILES = [
        'filament-truss.css' => 'text/css',
        'filament-truss.js' => 'text/javascript',
    ];

    public function __invoke(string $file): SymfonyResponse
    {
        abort_unless(array_key_exists($file, self::FILES), 404);

        $path = __DIR__.'/../../../resources/dist/'.$file;

        abort_unless(is_file($path), 404);

        return new Response((string) file_get_contents($path), 200, [
            'Content-Type' => self::FILES[$file].'; charset=UTF-8',
            // A year, and immutable, because the page asks for these through
            // {@see Asset}, which puts the file's modification time in the query.
            // The URL therefore changes whenever the bytes do, and that is what
            // makes a long cache safe: an upgrade is a different URL rather than
            // a stale copy of the old one.
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
