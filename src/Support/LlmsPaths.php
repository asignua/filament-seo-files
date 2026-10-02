<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\SeoFiles;
use InvalidArgumentException;

/**
 * Where the llms files live — shared by {@see LlmsTxtFile} and {@see LlmsFullTxtFile}.
 *
 *  - The unprefixed language → a static file in the web root (`llms.txt`), served by the
 *    web server directly. No clash: it is a FILE.
 *  - Prefixed languages → `{directory}/{locale}.txt`, served by the ROUTE
 *    `/{locale}/llms.txt`. A real `public/{locale}/llms.txt` is NOT possible: the
 *    directory `public/{locale}/` would shadow the site's `/{locale}/` home page (both
 *    `php artisan serve` and nginx `try_files $uri $uri/` would serve the directory
 *    instead of letting the request into Laravel, and `/{locale}/` would answer 404).
 *    The leading dot of `.llms` additionally stops nginx from serving the files directly
 *    (`location ~ /\.`).
 */
final class LlmsPaths
{
    /**
     * @param 'full'|'index' $kind
     */
    public static function for(string $kind, string $locale): string
    {
        // The form's select is only one layer of validation; the file writer must not trust
        // its caller: `..`, `/` or `\` in a locale would take the path out of public/.
        if (preg_match('~^[a-z]{2,3}([-_][A-Za-z]{2,4})?$~', $locale) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid locale: %s', $locale));
        }

        $suffix = $kind === 'full' ? '-full' : '';

        if ($locale === SeoFiles::unprefixedLocale()) {
            $path = config('filament-seo-files.llms.'.($kind === 'full' ? 'full_path' : 'path'));

            return (string) ($path ?? public_path('llms'.$suffix.'.txt'));
        }

        $directory = config('filament-seo-files.llms.directory') ?? public_path('.llms');

        return rtrim((string) $directory, '/').'/'.$locale.$suffix.'.txt';
    }
}
