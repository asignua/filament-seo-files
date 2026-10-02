<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Http\Controllers;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The llms.txt of a prefixed language at `/{locale}/llms.txt`.
 *
 * Why a route and not a static file: a real `public/{locale}/llms.txt` would shadow the
 * site's `/{locale}/` home page ({@see \Asignua\FilamentSeoFiles\Support\LlmsPaths}). The
 * unprefixed language needs none of this: its `public/llms.txt` is a file in the web root.
 *
 * `read()` serves the stored file or, when it has not been generated yet, a freshly built
 * template — so the URL works even before the first `seo-files:llms` run.
 */
class LlmsController
{
    public function __invoke(LlmsTxtFile $file, string $locale): Response
    {
        // The route already limits `locale` to the prefixed languages; this guards a direct call.
        if (!in_array($locale, SeoFiles::allLocales(), true)) {
            throw new NotFoundHttpException;
        }

        return response($file->read($locale), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
