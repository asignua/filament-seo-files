<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Illuminate\Support\Facades\File;

/**
 * Writes a file through a temporary file and a rename, so a reader (a crawler, the llms
 * route, a second writer) never sees a half-written file: `File::put` truncates the target
 * first and fills it afterwards.
 */
final class AtomicFile
{
    public static function put(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));

        $temporary = $path.'.'.uniqid('tmp', true);

        File::put($temporary, $contents);

        if (!@rename($temporary, $path)) {
            File::delete($temporary);
            // The rename failed (another filesystem, a Windows lock): fall back to a plain
            // write rather than losing the content.
            File::put($path, $contents);
        }
    }
}
