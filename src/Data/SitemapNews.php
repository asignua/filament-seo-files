<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Data;

use DateTimeInterface;

/**
 * The Google News metadata of one page (`<news:news>` in `sitemap-news.xml`).
 */
final readonly class SitemapNews
{
    /**
     * @param string            $title       `<news:title>`: the headline as it appears on the page.
     * @param DateTimeInterface $publishedAt `<news:publication_date>`: when the article was FIRST published.
     * @param string|null       $publication `<news:name>`; the site name (`SeoFiles::siteName()`) when null.
     * @param string|null       $language    `<news:language>` (ISO 639; the locale of the page when null).
     */
    public function __construct(
        public string $title,
        public DateTimeInterface $publishedAt,
        public ?string $publication = null,
        public ?string $language = null,
    ) {}
}
