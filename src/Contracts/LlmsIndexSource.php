<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Contracts;

use Asignua\FilamentSeoFiles\Data\LlmsSection;

/**
 * A provider of sections for the short `llms.txt` index.
 */
interface LlmsIndexSource
{
    /**
     * @return iterable<LlmsSection>
     */
    public function llmsSections(string $locale): iterable;
}
