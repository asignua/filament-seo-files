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
     * @param string                                      $url          Absolute URL in the default language; becomes `<loc>` and `x-default`.
     * @param array<string, string>                       $alternates   `locale => absolute URL` of every language version worth listing
     *                                                                  as `hreflang` (include the default language itself).
     * @param DateTimeInterface|null                      $lastModified `<lastmod>`; the generation time is used when null.
     * @param iterable<string>                            $images       Absolute image URLs (or paths from the site root) of the page, for the
     *                                                                  image sitemap. Emitted only with `sitemap.images.enabled`.
     * @param array<string, SitemapNews>|SitemapNews|null $news         One news item, or `locale => item`, for the news
     *                                                                  sitemap. Emitted only with `sitemap.news.enabled`.
     */
    public function __construct(
        public string $url,
        public array $alternates = [],
        public ?DateTimeInterface $lastModified = null,
        public iterable $images = [],
        public SitemapNews|array|null $news = null,
    ) {}
}
