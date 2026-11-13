<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Data;

/**
 * An `## H2` section of `llms.txt`: optional Markdown prose followed by its links.
 *
 * `body` is free Markdown (paragraphs, lists) shown under the heading before the links —
 * llmstxt.org allows prose but no nested headings, so a line starting with `#` is escaped
 * by the renderer. A section without both body and links is skipped.
 */
final readonly class LlmsSection
{
    /**
     * @param list<LlmsLink> $links
     */
    public function __construct(
        public string $title,
        public array $links,
        public ?string $body = null,
    ) {}
}
