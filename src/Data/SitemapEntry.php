<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Data;

use DateTimeInterface;

/**
 * One `<url>` of the sitemap.
 */
final readonly class SitemapEntry
{
    /**
     * @param string                 $url          Absolute URL in the default language; becomes `<loc>` and `x-default`.
     * @param array<string, string>  $alternates   `locale => absolute URL` of every language version worth listing
     *                                             as `hreflang` (include the default language itself).
     * @param DateTimeInterface|null $lastModified `<lastmod>`; the generation time is used when null.
     */
    public function __construct(
        public string $url,
        public array $alternates = [],
        public ?DateTimeInterface $lastModified = null,
    ) {}
}
