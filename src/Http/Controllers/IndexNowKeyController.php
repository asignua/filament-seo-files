<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Http\Controllers;

use Asignua\FilamentSeoFiles\Support\IndexNow;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The IndexNow key file: `/{key}.txt` answers with the key itself.
 */
class IndexNowKeyController
{
    public function __invoke(): Response
    {
        if (!IndexNow::enabled() || !IndexNow::validKey()) {
            throw new NotFoundHttpException;
        }

        return response(IndexNow::key(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
