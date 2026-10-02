<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Contracts;

use Asignua\FilamentSeoFiles\Data\LlmsDocument;

/**
 * A provider of full-text documents for `llms-full.txt`.
 */
interface LlmsFullSource
{
    /**
     * @return iterable<LlmsDocument>
     */
    public function llmsDocuments(string $locale): iterable;
}
