<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\LlmsFullTxtFile;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class LlmsFilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
    }

    /**
     * @return array<string, array{class-string<LlmsFullTxtFile|LlmsTxtFile>}>
     */
    public static function files(): array
    {
        return ['llms.txt' => [LlmsTxtFile::class], 'llms-full.txt' => [LlmsFullTxtFile::class]];
    }

    #[DataProvider('files')]
    public function test_the_unprefixed_language_is_a_file_in_the_web_root(string $class): void
    {
        $suffix = $class === LlmsTxtFile::class ? 'llms.txt' : 'llms-full.txt';

        $this->assertSame(public_path($suffix), (new $class)->path('en'));
    }

    public function test_a_prefixed_language_lives_outside_the_directory_that_would_shadow_its_route(): void
    {
        $this->assertStringContainsString('/.llms/uk', (new LlmsTxtFile)->path('uk'));
        $this->assertSame(public_path('.llms/uk.txt'), (new LlmsTxtFile)->path('uk'));
        $this->assertSame(public_path('.llms/uk-full.txt'), (new LlmsFullTxtFile)->path('uk'));
        $this->assertStringNotContainsString(public_path('uk/'), (new LlmsTxtFile)->path('uk'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badLocales(): array
    {
        return [
            'parent directory' => ['../../etc/passwd'],
            'forward slash' => ['uk/../x'],
            'backslash' => ['uk\\..\\x'],
            'upper case' => ['UK'],
            'empty' => [''],
            'a dot' => ['uk.'],
        ];
    }

    #[DataProvider('badLocales')]
    public function test_the_llms_txt_path_rejects_a_locale_escaping_the_public_directory(string $locale): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LlmsTxtFile)->path($locale);
    }

    #[DataProvider('badLocales')]
    public function test_the_llms_full_path_rejects_the_same_locales(string $locale): void
    {
        // The full file's writer used to have no such guard.
        $this->expectException(InvalidArgumentException::class);

        (new LlmsFullTxtFile)->path($locale);
    }

    public function test_it_accepts_regional_locales(): void
    {
        SeoFiles::locales(default: 'en', all: ['en', 'pt_BR', 'zh-Hans'], unprefixed: 'en');

        $this->assertSame(public_path('.llms/pt_BR.txt'), (new LlmsTxtFile)->path('pt_BR'));
        $this->assertSame(public_path('.llms/zh-Hans-full.txt'), (new LlmsFullTxtFile)->path('zh-Hans'));
    }

    public function test_when_every_language_is_prefixed_none_is_served_from_the_root(): void
    {
        SeoFiles::locales(default: 'en', all: ['en', 'uk'], unprefixed: null);

        $this->assertSame(public_path('.llms/en.txt'), (new LlmsTxtFile)->path('en'));
    }

    public function test_paths_are_configurable(): void
    {
        config([
            'filament-seo-files.llms.path' => $this->publicPath.'/ai/index.txt',
            'filament-seo-files.llms.full_path' => $this->publicPath.'/ai/full.txt',
            'filament-seo-files.llms.directory' => $this->publicPath.'/ai/locales/',
        ]);

        $this->assertSame($this->publicPath.'/ai/index.txt', (new LlmsTxtFile)->path('en'));
        $this->assertSame($this->publicPath.'/ai/full.txt', (new LlmsFullTxtFile)->path('en'));
        $this->assertSame($this->publicPath.'/ai/locales/uk.txt', (new LlmsTxtFile)->path('uk'));
        $this->assertSame($this->publicPath.'/ai/locales/uk-full.txt', (new LlmsFullTxtFile)->path('uk'));
    }

    public function test_read_falls_back_to_the_generated_template_when_the_file_is_absent(): void
    {
        $this->registerPosts();
        $this->makePost('a', 'Alpha');

        $file = new LlmsTxtFile;

        $this->assertFileDoesNotExist($file->path('en'));
        $this->assertSame($file->template('en'), $file->read('en'));
    }

    public function test_read_returns_the_stored_file(): void
    {
        $file = new LlmsTxtFile;
        $file->write('en', "# Edited by hand\n\n\n");

        $this->assertSame("# Edited by hand\n", $file->read('en'));
    }

    public function test_the_template_lists_source_links_with_absolute_urls(): void
    {
        $this->registerPosts()->section('Blog');
        $this->makePost('a', 'Alpha', 'Альфа');

        $template = (new LlmsTxtFile)->template('uk');

        $this->assertStringStartsWith("# Site\n", $template);
        $this->assertStringContainsString("## Blog\n\n- [Альфа](https://site.test/uk/posts/a)", $template);
    }

    public function test_the_blockquote_comes_from_the_description_resolver(): void
    {
        SeoFiles::descriptionUsing(fn (string $locale): ?string => "A site\nabout things [".$locale.']');

        $this->assertStringContainsString("\n> A site about things [en]\n", (new LlmsTxtFile)->template('en'));
    }

    public function test_the_optional_section_points_at_the_sitemap(): void
    {
        $template = (new LlmsTxtFile)->template('en');

        $this->assertStringContainsString('## Optional', $template);
        $this->assertStringContainsString('- [sitemap.xml](https://site.test/sitemap.xml): '.__('filament-seo-files::seo-files.llms.sitemap_description'), $template);
    }

    public function test_the_optional_description_is_translated_into_the_files_language(): void
    {
        $this->assertStringContainsString(
            __('filament-seo-files::seo-files.llms.sitemap_description', [], 'uk'),
            (new LlmsTxtFile)->template('uk'),
        );
    }

    public function test_the_optional_link_follows_a_split_sitemap_index(): void
    {
        // An index is still served at sitemap.xml, which is what agents are pointed at.
        config(['filament-seo-files.sitemap.split' => 'always']);

        $this->assertStringContainsString('(https://site.test/sitemap.xml)', (new LlmsTxtFile)->template('en'));
    }

    public function test_write_does_not_create_a_public_locale_directory(): void
    {
        (new LlmsTxtFile)->write('uk', '# Site');
        (new LlmsFullTxtFile)->write('uk', '# Site');

        $this->assertDirectoryDoesNotExist(public_path('uk'));
        $this->assertFileExists(public_path('.llms/uk.txt'));
        $this->assertFileExists(public_path('.llms/uk-full.txt'));
    }

    public function test_the_full_template_has_the_preamble_and_every_document(): void
    {
        $this->registerPosts();
        $this->makePost('a', 'Alpha', null, ['body_en' => '<p>Body of <b>alpha</b></p>']);
        $this->makePost('b', 'Beta', null, ['body_en' => '<p>Body of beta</p>']);

        $document = (new LlmsFullTxtFile)->template('en');

        $this->assertStringStartsWith("# Site\n\n---\n\n# Alpha\nSource: https://site.test/posts/a\n\nBody of **alpha**", $document);
        $this->assertStringContainsString("\n\n---\n\n# Beta\nSource: https://site.test/posts/b", $document);
        $this->assertSame(2, substr_count($document, "\n---\n"));
    }

    public function test_full_read_and_write(): void
    {
        $file = new LlmsFullTxtFile;
        $this->assertSame($file->template('en'), $file->read('en'));

        $file->write('en', "# Hand made\n");

        $this->assertSame("# Hand made\n", File::get(public_path('llms-full.txt')));
        $this->assertSame("# Hand made\n", $file->read('en'));
    }
}
