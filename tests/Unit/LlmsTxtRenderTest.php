<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Unit;

use Asignua\FilamentSeoFiles\Data\LlmsLink;
use Asignua\FilamentSeoFiles\Data\LlmsSection;
use Asignua\FilamentSeoFiles\Support\LlmsTxt;
use Asignua\FilamentSeoFiles\Tests\TestCase;

/**
 * Rendering of llms.txt (https://llmstxt.org/). The renderer knows nothing about Eloquent:
 * it takes ready sections, so it is tested without a database.
 */
class LlmsTxtRenderTest extends TestCase
{
    /**
     * @return list<LlmsSection>
     */
    private function sections(): array
    {
        return [
            new LlmsSection('Opportunities', [
                new LlmsLink('All grants', 'https://example.com/opp', 'The catalogue of opportunities'),
            ]),
        ];
    }

    public function test_renders_title_summary_and_sections_in_spec_order(): void
    {
        $txt = LlmsTxt::render('Grant Market', 'A portal of grant opportunities', $this->sections());

        $this->assertSame(
            "# Grant Market\n"
            ."\n"
            ."> A portal of grant opportunities\n"
            ."\n"
            ."## Opportunities\n"
            ."\n"
            ."- [All grants](https://example.com/opp): The catalogue of opportunities\n",
            $txt,
        );
    }

    public function test_summary_is_collapsed_to_a_single_line(): void
    {
        // A blockquote is one line by the spec; a multi-line Textarea must not break the structure.
        $txt = LlmsTxt::render('T', "First line\r\nsecond\n\nthird", []);

        $this->assertStringContainsString("> First line second third\n", $txt);
    }

    public function test_omits_blockquote_when_summary_is_empty(): void
    {
        $txt = LlmsTxt::render('T', '   ', []);

        $this->assertStringNotContainsString('>', $txt);
        $this->assertSame("# T\n", $txt);
    }

    public function test_item_without_description_renders_without_colon(): void
    {
        $sections = [new LlmsSection('S', [new LlmsLink('A', 'https://example.com/a')])];

        $txt = LlmsTxt::render('T', null, $sections);

        $this->assertStringContainsString("- [A](https://example.com/a)\n", $txt);
        $this->assertStringNotContainsString('):', $txt);
    }

    public function test_skips_sections_without_items(): void
    {
        $sections = [
            new LlmsSection('Empty', []),
            new LlmsSection('Full', [new LlmsLink('A', 'https://example.com/a')]),
        ];

        $txt = LlmsTxt::render('T', null, $sections);

        $this->assertStringNotContainsString('Empty', $txt);
        $this->assertStringContainsString('## Full', $txt);
    }

    public function test_escapes_markdown_link_syntax_in_titles_and_descriptions(): void
    {
        // Brackets in a page title must not break the Markdown link.
        $sections = [new LlmsSection('S', [
            new LlmsLink('Grants [2026] (new)', 'https://example.com/a', 'Text [with] brackets'),
        ])];

        $txt = LlmsTxt::render('T', null, $sections);

        $this->assertStringContainsString('- [Grants \[2026\] (new)](https://example.com/a): Text \[with\] brackets', $txt);
    }

    public function test_parentheses_and_spaces_in_a_url_do_not_break_the_link(): void
    {
        // Wiki-style slugs carry parentheses; `)` would end the link destination early.
        $sections = [new LlmsSection('S', [
            new LlmsLink('Mercury', 'https://example.com/wiki/Mercury_(planet)', null),
        ])];

        $txt = LlmsTxt::render('T', null, $sections);

        $this->assertStringContainsString('- [Mercury](https://example.com/wiki/Mercury_%28planet%29)', $txt);
    }

    public function test_collapses_newlines_inside_item_fields(): void
    {
        // A list item is exactly one line, otherwise the document falls apart.
        $sections = [new LlmsSection('S', [
            new LlmsLink("Title\nwith a break", 'https://example.com/a', "Text\nwith a break"),
        ])];

        $txt = LlmsTxt::render('T', null, $sections);

        $this->assertStringContainsString('- [Title with a break](https://example.com/a): Text with a break', $txt);
    }

    public function test_ends_with_exactly_one_newline(): void
    {
        $txt = LlmsTxt::render('Grant Market', 'Text', $this->sections());

        $this->assertStringEndsWith("\n", $txt);
        $this->assertStringEndsNotWith("\n\n", $txt);
    }

    public function test_title_only_document_is_valid(): void
    {
        $this->assertSame("# T\n", LlmsTxt::render('T', null, []));
    }
}
