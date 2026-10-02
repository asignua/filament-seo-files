<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Unit;

use Asignua\FilamentSeoFiles\Data\LlmsDocument;
use Asignua\FilamentSeoFiles\Support\LlmsFull;
use Asignua\FilamentSeoFiles\Tests\TestCase;

/**
 * Rendering of llms-full.txt: the site preamble plus entries (the full content) separated
 * by a thematic break. Tested without a database, like LlmsTxt.
 */
class LlmsFullRenderTest extends TestCase
{
    public function test_entry_has_h1_title_and_source_line(): void
    {
        $e = LlmsFull::entry('About us', 'https://x.test/en/about', 'The **body** text.');

        $this->assertStringContainsString("# About us\n", $e);
        $this->assertStringContainsString('Source: https://x.test/en/about', $e);
        $this->assertStringContainsString('The **body** text.', $e);
    }

    public function test_a_line_break_in_the_title_does_not_break_the_entry_header(): void
    {
        $e = LlmsFull::entry("Two\nlines", 'https://x.test/e', '');

        $this->assertSame("# Two lines\nSource: https://x.test/e", $e);
    }

    public function test_entry_with_empty_body_is_just_heading_and_source(): void
    {
        $e = LlmsFull::entry('Empty', 'https://x.test/e', '   ');

        $this->assertSame("# Empty\nSource: https://x.test/e", $e);
    }

    public function test_entry_for_a_document_renders_like_an_entry(): void
    {
        $this->assertSame(
            LlmsFull::entry('A', 'https://x.test/a', 'aa'),
            LlmsFull::entryFor(new LlmsDocument('A', 'https://x.test/a', 'aa')),
        );
    }

    public function test_document_joins_preamble_and_entries_with_hr(): void
    {
        $doc = LlmsFull::document('Grant Market', 'Description', [
            LlmsFull::entry('A', 'https://x.test/a', 'aa'),
            LlmsFull::entry('B', 'https://x.test/b', 'bb'),
        ]);

        $this->assertStringStartsWith("# Grant Market\n\n> Description", $doc);
        // A separator after the preamble + one between the two entries = 2 occurrences.
        $this->assertSame(2, substr_count($doc, "\n\n---\n\n"));
    }

    public function test_document_without_description_omits_blockquote(): void
    {
        $doc = LlmsFull::document('Site', null, [LlmsFull::entry('A', 'u', 'b')]);

        $preamble = explode("\n\n---\n\n", $doc)[0];
        $this->assertSame('# Site', $preamble);
    }
}
