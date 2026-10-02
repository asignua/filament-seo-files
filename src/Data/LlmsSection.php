<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Data;

/**
 * An `## H2` section of `llms.txt` with its links.
 */
final readonly class LlmsSection
{
    /**
     * @param list<LlmsLink> $links
     */
    public function __construct(
        public string $title,
        public array $links,
    ) {}
}
