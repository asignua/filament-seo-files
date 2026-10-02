<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Data\LlmsDocument;
use Asignua\FilamentSeoFiles\Data\SitemapEntry;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Sources\ModelSource;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Workbench\App\Models\Post;

class ModelSourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
    }

    public function test_it_rejects_a_class_that_is_not_a_model(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ModelSource::make(\stdClass::class);
    }

    public function test_it_needs_a_url_callback(): void
    {
        $this->makePost('a', 'A');

        $this->expectException(InvalidArgumentException::class);

        iterator_to_array(ModelSource::make(Post::class)->sitemapEntries());
    }

    public function test_the_default_last_modified_is_updated_at(): void
    {
        $post = $this->makePost('a', 'A');
        $post->forceFill(['updated_at' => Carbon::parse('2026-03-04 05:06:07')])->saveQuietly();

        $entries = iterator_to_array($this->registerPosts()->sitemapEntries(), false);

        $this->assertInstanceOf(SitemapEntry::class, $entries[0]);
        $this->assertSame('2026-03-04', $entries[0]->lastModified?->format('Y-m-d'));
    }

    public function test_last_modified_can_be_overridden(): void
    {
        $this->makePost('a', 'A');
        $source = $this->registerPosts()->lastModified(fn (Post $post): Carbon => Carbon::parse('2020-01-02'));

        $entries = iterator_to_array($source->sitemapEntries(), false);

        $this->assertSame('2020-01-02', $entries[0]->lastModified?->format('Y-m-d'));
    }

    public function test_records_come_in_primary_key_order_across_chunks(): void
    {
        foreach (['e', 'd', 'c', 'b', 'a'] as $slug) {
            $this->makePost($slug, strtoupper($slug));
        }

        // The query callback asks for another order; keyset pagination wins, otherwise
        // chunks would skip or repeat records.
        $source = $this->registerPosts()->query(fn ($query) => $query->orderBy('slug'))->chunk(2);

        $locs = array_map(fn (SitemapEntry $entry): string => $entry->url, iterator_to_array($source->sitemapEntries(), false));

        $this->assertSame([
            'https://site.test/posts/e',
            'https://site.test/posts/d',
            'https://site.test/posts/c',
            'https://site.test/posts/b',
            'https://site.test/posts/a',
        ], $locs);
    }

    public function test_the_section_title_defaults_to_the_plural_of_the_class(): void
    {
        $this->makePost('a', 'A');

        $sections = iterator_to_array($this->registerPosts()->llmsSections('en'), false);

        $this->assertSame('Posts', $sections[0]->title);
        $this->assertSame('A', $sections[0]->links[0]->title);
        $this->assertSame('https://site.test/posts/a', $sections[0]->links[0]->url);
    }

    public function test_the_llms_index_lists_at_most_the_index_limit_newest_first(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $slug) {
            $this->makePost($slug, strtoupper($slug));
        }

        $titles = fn (ModelSource $source): array => array_map(
            fn ($link): string => $link->title,
            iterator_to_array($source->llmsSections('en'), false)[0]->links,
        );

        // llms.txt is a short index: the complete list is sitemap.xml's job.
        $this->assertSame(['D', 'C'], $titles($this->registerPosts()->indexLimit(2)->chunk(1)));

        $plain = fn (): ModelSource => ModelSource::make(Post::class)
            ->url(fn (Post $post): string => '/posts/'.$post->slug)
            ->title(fn (Post $post): string => (string) $post->title_en);

        config(['filament-seo-files.llms.index_limit' => 3]);
        $this->assertSame(['D', 'C', 'B'], $titles($plain()));

        $this->assertSame(['D', 'C', 'B', 'A'], $titles($plain()->indexLimit(null)));
    }

    public function test_the_section_title_can_be_set(): void
    {
        $this->makePost('a', 'A');

        $sections = iterator_to_array($this->registerPosts()->section('Blog')->llmsSections('en'), false);

        $this->assertSame('Blog', $sections[0]->title);
    }

    public function test_a_source_without_records_gives_no_section(): void
    {
        $this->assertSame([], iterator_to_array($this->registerPosts()->llmsSections('en'), false));
    }

    public function test_a_record_without_a_url_in_a_language_is_left_out_of_that_language(): void
    {
        $this->makePost('both', 'Both', 'Обидві');
        $this->makePost('english', 'English');

        $source = $this->registerPosts();

        $uk = iterator_to_array($source->llmsSections('uk'), false);
        $en = iterator_to_array($source->llmsSections('en'), false);

        $this->assertCount(1, $uk[0]->links);
        $this->assertCount(2, $en[0]->links);
    }

    public function test_the_description_is_stripped_of_tags_and_cut_to_200_characters(): void
    {
        $this->makePost('a', 'A', null, ['excerpt_en' => '<p>'.str_repeat('word ', 80).'</p>']);

        $links = iterator_to_array($this->registerPosts()->llmsSections('en'), false)[0]->links;

        $this->assertNotNull($links[0]->description);
        // Cut at 200 characters; Str::limit trims a trailing space.
        $this->assertLessThanOrEqual(200, mb_strlen($links[0]->description));
        $this->assertGreaterThan(190, mb_strlen($links[0]->description));
        $this->assertStringNotContainsString('<p>', $links[0]->description);
    }

    public function test_the_description_limit_is_configurable(): void
    {
        config(['filament-seo-files.llms.description_limit' => 10]);
        $this->makePost('a', 'A', null, ['excerpt_en' => 'Short and then some more words']);

        $links = iterator_to_array($this->registerPosts()->llmsSections('en'), false)[0]->links;

        $this->assertLessThanOrEqual(10, mb_strlen((string) $links[0]->description));
        $this->assertStringStartsWith('Short and', (string) $links[0]->description);
    }

    public function test_a_blank_description_is_null(): void
    {
        $this->makePost('a', 'A', null, ['excerpt_en' => '<br>  ']);

        $links = iterator_to_array($this->registerPosts()->llmsSections('en'), false)[0]->links;

        $this->assertNull($links[0]->description);
    }

    public function test_an_html_body_is_converted_to_markdown(): void
    {
        $this->makePost('a', 'A', null, ['body_en' => '<h2>Heading</h2><p>Some <strong>bold</strong> text.</p><script>alert(1)</script>']);

        $documents = iterator_to_array($this->registerPosts()->llmsDocuments('en'), false);

        $this->assertInstanceOf(LlmsDocument::class, $documents[0]);
        $this->assertStringContainsString('## Heading', $documents[0]->markdown);
        $this->assertStringContainsString('Some **bold** text.', $documents[0]->markdown);
        $this->assertStringNotContainsString('alert', $documents[0]->markdown);
        $this->assertStringNotContainsString('<p>', $documents[0]->markdown);
    }

    public function test_a_markdown_body_passes_through(): void
    {
        $this->makePost('a', 'A', null, ['body_en' => "## Heading\n\n<b>raw</b> *kept*"]);

        $documents = iterator_to_array($this->registerPosts()->markdown()->llmsDocuments('en'), false);

        $this->assertSame("## Heading\n\n<b>raw</b> *kept*", $documents[0]->markdown);
    }

    public function test_no_body_callback_gives_an_empty_body(): void
    {
        $this->makePost('a', 'A');
        $source = ModelSource::make(Post::class)
            ->url(fn (Post $post, string $locale): string => SeoFiles::localizedUrl($locale, 'posts/'.$post->slug));

        $documents = iterator_to_array($source->llmsDocuments('en'), false);

        $this->assertSame('', $documents[0]->markdown);
    }

    public function test_the_full_file_can_be_limited(): void
    {
        foreach (['a', 'b', 'c'] as $slug) {
            $this->makePost($slug, strtoupper($slug));
        }

        $documents = iterator_to_array($this->registerPosts()->limit(2)->llmsDocuments('en'), false);

        $this->assertCount(2, $documents);
    }

    public function test_locales_can_be_restricted(): void
    {
        $this->makePost('a', 'A', 'А');

        $entries = iterator_to_array($this->registerPosts()->locales(['en'])->sitemapEntries(), false);

        $this->assertSame(['en'], array_keys($entries[0]->alternates));
    }

    public function test_a_source_with_the_title_defaulting_to_the_title_or_name_attribute(): void
    {
        $this->makePost('a', 'A');
        $source = ModelSource::make(Post::class)
            ->url(fn (Post $post, string $locale): string => '/p/'.$post->slug);

        // `title` does not exist on the workbench Post (it has title_en): the fallback is empty.
        $links = iterator_to_array($source->llmsSections('en'), false)[0]->links;

        $this->assertSame('', $links[0]->title);
    }
}
