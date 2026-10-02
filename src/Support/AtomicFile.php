<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Writes a file through a temporary file and a rename, so a reader (a crawler, the llms
 * route, a second writer) never sees a half-written file: `File::put` truncates the target
 * first and fills it afterwards.
 *
 * The rename needs write permission on the DIRECTORY and gives the file the owner of the
 * writing process. A hardened deploy often allows neither: `public/` belongs to the deploy
 * user and only robots.txt / llms.txt are group-writable for php-fpm. So the atomic path is
 * taken only when it changes nothing but atomicity — the file is ours (or new) and the
 * temporary file can be created — and everything else falls back to an in-place write,
 * which needs nothing beyond write permission on the file itself (as in v1.0.0).
 */
final class AtomicFile
{
    public static function put(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));

        $exists = is_file($path);

        // Someone else's file: a rename would hand it to this process and could lock its
        // owner out of it. Write in place, keeping owner and mode.
        if ($exists && !self::ownedByThisProcess($path)) {
            File::put($path, $contents);

            return;
        }

        $temporary = $path.'.'.uniqid('tmp', true);

        try {
            $written = @file_put_contents($temporary, $contents);
        } catch (Throwable) {
            $written = false;
        }

        if ($written === false) {
            // The directory is not writable (or the temporary file failed for any other
            // reason): an in-place write still works when the file itself is writable.
            @unlink($temporary);
            File::put($path, $contents);

            return;
        }

        if ($exists && ($mode = @fileperms($path)) !== false) {
            // Keep the mode an administrator gave the file (e.g. group-writable 0664)
            // instead of the umask default of a freshly created temporary file.
            @chmod($temporary, $mode & 0o7777);
        }

        if (!@rename($temporary, $path)) {
            @unlink($temporary);
            // The rename failed (another filesystem, a Windows lock): fall back to a plain
            // write rather than losing the content.
            File::put($path, $contents);
        }
    }

    private static function ownedByThisProcess(string $path): bool
    {
        if (!function_exists('posix_geteuid')) {
            return true;
        }

        $owner = @fileowner($path);

        return $owner === false || $owner === posix_geteuid();
    }
}
