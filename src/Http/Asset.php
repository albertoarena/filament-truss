<?php

declare(strict_types=1);

namespace AlbertoArena\FilamentTruss\Http;

/**
 * The URL the page asks for one of this package's two static files.
 *
 * **A version query, and it is not decoration.** These files are served from a
 * route whose URL is the same in every version of the package, and the response
 * says to cache them. Without something in the URL that moves when the bytes
 * move, a host who upgrades gets the previous stylesheet and script against the
 * new markup for as long as the cache lives. Nothing errors in that state; the
 * page is simply wrong, which is the worst way for it to be wrong.
 *
 * The file's modification time is the version. Composer writes it on install and
 * on upgrade, so it changes exactly when the file does, with no constant to
 * remember to bump and no build step. It also covers editing the file in place,
 * which is what a development panel does through a path repository.
 */
class Asset
{
    public static function url(string $file): string
    {
        $path = __DIR__.'/../../resources/dist/'.$file;

        $version = is_file($path) ? (string) filemtime($path) : '0';

        return route('filament-truss.asset', $file).'?v='.base_convert($version, 10, 36);
    }
}
