<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Sources;

use Asignua\FilamentSeoFiles\Contracts\LlmsFullSource;
use Asignua\FilamentSeoFiles\Contracts\LlmsIndexSource;
use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\Data\LlmsDocument;
use Asignua\FilamentSeoFiles\Data\LlmsLink;
use Asignua\FilamentSeoFiles\Data\LlmsSection;
use Asignua\FilamentSeoFiles\Data\SitemapEntry;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\SitemapLocation;
use Closure;
use DateTimeInterface;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Feeds sitemap.xml, llms.txt and llms-full.txt from an Eloquent model:
 *
 *     SeoFiles::source(ModelSource::make(Post::class)
 *         ->query(fn (Builder $query) => $query->where('published', true))
 *         ->url(fn (Post $post, string $locale): ?string => route('posts.show', $post))
 *         ->title(fn (Post $post, string $locale): string => $post->title)
 *         ->description(fn (Post $post, string $locale): ?string => $post->excerpt)
 *         ->body(fn (Post $post, string $locale): string => $post->content)
 *         ->section('Blog'));
 *
 * A model without a URL in a language is skipped for that language. Records are read in
 * chunks in primary-key order, so a large table is never loaded whole.
 *
 * @template TModel of Model
 */
class ModelSource implements LlmsFullSource, LlmsIndexSource, SitemapSource
{
    public const int DESCRIPTION_LIMIT = 200;

    /** @var class-string<TModel> */
    private string $model;

    /** @var (Closure(Builder<TModel>): mixed)|null */
    private ?Closure $query = null;

    /** @var (Closure(TModel, string): ?string)|null */
    private ?Closure $url = null;

    /** @var (Closure(TModel, string): ?string)|null */
    private ?Closure $title = null;

    /** @var (Closure(TModel, string): ?string)|null */
    private ?Closure $description = null;

    /** @var (Closure(TModel, string): ?string)|null */
    private ?Closure $body = null;

    /** @var (Closure(TModel): ?DateTimeInterface)|null */
    private ?Closure $lastModified = null;

    private ?string $section = null;

    private bool $markdownBody = false;

    /** @var list<string>|null */
    private ?array $locales = null;

    private int $chunk = 200;

    private ?int $limit = null;

    private ?int $indexLimit = null;

    private bool $indexLimitSet = false;

    /**
     * @param class-string<TModel> $model
     */
    final public function __construct(string $model)
    {
        if (!is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException(sprintf('%s is not an Eloquent model.', $model));
        }

        $this->model = $model;
    }

    /**
     * @param class-string<TModel> $model
     *
     * @return static<TModel>
     */
    public static function make(string $model): static
    {
        return new static($model);
    }

    /**
     * Narrow down the records (published only, …). Mutate the builder or return it.
     *
     * @param Closure(Builder<TModel>): mixed $callback
     */
    public function query(Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    /**
     * The URL of a record in a language: absolute, or a path starting with `/` (the base
     * URL is prepended). Return null to skip the record in that language.
     *
     * @param Closure(TModel, string): ?string $callback
     */
    public function url(Closure $callback): static
    {
        $this->url = $callback;

        return $this;
    }

    /**
     * @param Closure(TModel, string): ?string $callback
     */
    public function title(Closure $callback): static
    {
        $this->title = $callback;

        return $this;
    }

    /**
     * The short description for llms.txt; tags are stripped and the text is cut to
     * `filament-seo-files.llms.description_limit` (200) characters.
     *
     * @param Closure(TModel, string): ?string $callback
     */
    public function description(Closure $callback): static
    {
        $this->description = $callback;

        return $this;
    }

    /**
     * The page body for llms-full.txt: HTML (converted to Markdown) or, with
     * {@see markdown()}, Markdown as is.
     *
     * @param Closure(TModel, string): ?string $callback
     */
    public function body(Closure $callback): static
    {
        $this->body = $callback;

        return $this;
    }

    /**
     * The body closure already returns Markdown: pass it through unconverted.
     */
    public function markdown(bool $markdown = true): static
    {
        $this->markdownBody = $markdown;

        return $this;
    }

    /**
     * `<lastmod>` of a record; default `updated_at`.
     *
     * @param Closure(TModel): ?DateTimeInterface $callback
     */
    public function lastModified(Closure $callback): static
    {
        $this->lastModified = $callback;

        return $this;
    }

    /**
     * The `## H2` title of the llms.txt section; default: the plural headline of the
     * model's class name.
     */
    public function section(string $title): static
    {
        $this->section = $title;

        return $this;
    }

    /**
     * Limit the source to some languages (default: all languages of the site).
     *
     * @param list<string> $locales
     */
    public function locales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * Records read per query.
     */
    public function chunk(int $size): static
    {
        $this->chunk = max(1, $size);

        return $this;
    }

    /**
     * At most this many records in llms-full.txt, which carries whole page bodies
     * (the sitemap is not limited; the llms.txt index has {@see indexLimit()}).
     */
    public function limit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * At most this many records in the llms.txt index, NEWEST first (by primary key):
     * llms.txt is a short curated index, the complete list is what sitemap.xml is for.
     * Default: `filament-seo-files.llms.index_limit` (100); `null` lists every record.
     */
    public function indexLimit(?int $limit): static
    {
        $this->indexLimit = $limit;
        $this->indexLimitSet = true;

        return $this;
    }

    public function sitemapEntries(): iterable
    {
        $default = SeoFiles::defaultLocale();

        foreach ($this->records() as $record) {
            $alternates = [];

            foreach ($this->localesToServe() as $locale) {
                $url = $this->urlFor($record, $locale);

                if ($url !== null) {
                    $alternates[$locale] = $url;
                }
            }

            if ($alternates === []) {
                continue;
            }

            // <loc> is the default language, or the first one that has a URL.
            $loc = $alternates[$default] ?? $alternates[array_key_first($alternates)];

            yield new SitemapEntry($loc, $alternates, $this->lastModifiedOf($record));
        }
    }

    public function llmsSections(string $locale): iterable
    {
        $links = [];
        $limit = $this->resolvedIndexLimit();

        foreach ($this->records(newestFirst: true) as $record) {
            if ($limit !== null && count($links) >= $limit) {
                break;
            }

            $url = $this->urlFor($record, $locale);

            if ($url === null) {
                continue;
            }

            $links[] = new LlmsLink($this->titleFor($record, $locale), $url, $this->descriptionFor($record, $locale));
        }

        if ($links === []) {
            return [];
        }

        return [new LlmsSection($this->section ?? Str::headline(Str::plural(class_basename($this->model))), $links)];
    }

    public function llmsDocuments(string $locale): iterable
    {
        $count = 0;

        foreach ($this->records() as $record) {
            if ($this->limit !== null && $count >= $this->limit) {
                return;
            }

            $url = $this->urlFor($record, $locale);

            if ($url === null) {
                continue;
            }

            $count++;

            yield new LlmsDocument($this->titleFor($record, $locale), $url, $this->markdownFor($record, $locale));
        }
    }

    private function resolvedIndexLimit(): ?int
    {
        if ($this->indexLimitSet) {
            return $this->indexLimit === null ? null : max(0, $this->indexLimit);
        }

        $configured = config('filament-seo-files.llms.index_limit', 100);

        return $configured === null ? null : max(0, (int) $configured);
    }

    /**
     * The records in primary-key order (descending with `$newestFirst`), one chunk per query.
     *
     * @return Generator<int, TModel>
     */
    private function records(bool $newestFirst = false): Generator
    {
        $instance = new $this->model;
        $key = $instance->getKeyName();
        $lastId = null;

        /** @var Builder<TModel> $base */
        $base = $this->model::query();

        if ($this->query !== null) {
            $returned = ($this->query)($base);
            $base = $returned instanceof Builder ? $returned : $base;
        }

        do {
            // Keyset pagination by the primary key: it re-orders the query, so the records
            // come in key order whatever ordering the `query()` callback asked for.
            $page = (clone $base)->reorder();
            /** @var Collection<int, TModel> $records */
            $records = ($newestFirst
                ? $page->forPageBeforeId($this->chunk, $lastId, $key)
                : $page->forPageAfterId($this->chunk, $lastId, $key))->get();

            foreach ($records as $record) {
                yield $record;
            }

            $last = $records->last();
            $lastId = $last !== null ? $last->getKey() : $lastId;
        } while ($records->count() === $this->chunk);
    }

    /**
     * @return list<string>
     */
    private function localesToServe(): array
    {
        return $this->locales ?? SeoFiles::allLocales();
    }

    /**
     * @param TModel $record
     */
    private function urlFor(Model $record, string $locale): ?string
    {
        if ($this->url === null) {
            throw new InvalidArgumentException(sprintf('ModelSource for %s needs a url() callback.', $this->model));
        }

        if ($this->locales !== null && !in_array($locale, $this->locales, true)) {
            return null;
        }

        $url = trim((string) ($this->url)($record, $locale));

        if ($url === '') {
            return null;
        }

        if (SitemapLocation::isAbsolute($url)) {
            return $url;
        }

        return SeoFiles::baseUrl().'/'.ltrim($url, '/');
    }

    /**
     * @param TModel $record
     */
    private function titleFor(Model $record, string $locale): string
    {
        if ($this->title !== null) {
            return (string) ($this->title)($record, $locale);
        }

        return (string) ($record->getAttribute('title') ?? $record->getAttribute('name') ?? '');
    }

    /**
     * @param TModel $record
     */
    private function descriptionFor(Model $record, string $locale): ?string
    {
        if ($this->description === null) {
            return null;
        }

        $text = trim(strip_tags((string) ($this->description)($record, $locale)));

        return $text === ''
            ? null
            : Str::limit($text, (int) config('filament-seo-files.llms.description_limit', self::DESCRIPTION_LIMIT), '');
    }

    /**
     * @param TModel $record
     */
    private function markdownFor(Model $record, string $locale): string
    {
        if ($this->body === null) {
            return '';
        }

        $body = trim((string) ($this->body)($record, $locale));

        if ($body === '' || $this->markdownBody) {
            return $body;
        }

        return trim((new HtmlConverter([
            'strip_tags' => true,
            'remove_nodes' => 'script style',
            'header_style' => 'atx',
        ]))->convert($body));
    }

    /**
     * @param TModel $record
     */
    private function lastModifiedOf(Model $record): ?DateTimeInterface
    {
        if ($this->lastModified !== null) {
            return ($this->lastModified)($record);
        }

        $updated = $record->getAttribute('updated_at');

        return $updated instanceof DateTimeInterface ? $updated : null;
    }
}
