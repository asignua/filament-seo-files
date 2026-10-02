<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Commands\Concerns;

trait DisplaysRelativePaths
{
    /**
     * A path under `public/` as `public/…` for the console output; any other path as is.
     */
    private function relative(string $path): string
    {
        $public = public_path().'/';

        return str_starts_with($path, $public) ? 'public/'.substr($path, strlen($public)) : $path;
    }
}
