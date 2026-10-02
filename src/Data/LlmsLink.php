<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Data;

/**
 * One `- [title](url): description` line of `llms.txt`.
 */
final readonly class LlmsLink
{
    public function __construct(
        public string $title,
        public string $url,
        public ?string $description = null,
    ) {}
}
