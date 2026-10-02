<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Data;

/**
 * One page of `llms-full.txt`: its heading, source URL and Markdown body.
 */
final readonly class LlmsDocument
{
    public function __construct(
        public string $title,
        public string $url,
        public string $markdown,
    ) {}
}
