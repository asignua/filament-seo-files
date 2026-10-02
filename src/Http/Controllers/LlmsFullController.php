<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Http\Controllers;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\LlmsFullTxtFile;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The full llms-full.txt of a prefixed language at `/{locale}/llms-full.txt`.
 *
 * A mirror of {@see LlmsController}: a route rather than a static file, because the
 * directory `public/{locale}/` would shadow the language's home page.
 */
class LlmsFullController
{
    public function __invoke(LlmsFullTxtFile $file, string $locale): Response
    {
        if (!in_array($locale, SeoFiles::allLocales(), true)) {
            throw new NotFoundHttpException;
        }

        return response($file->read($locale), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
