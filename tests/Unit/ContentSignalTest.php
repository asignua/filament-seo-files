<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Unit;

use Asignua\FilamentSeoFiles\Support\ContentSignal;
use PHPUnit\Framework\TestCase;

class ContentSignalTest extends TestCase
{
    private const DEFAULT = "User-agent: *\nContent-Signal: search=yes, ai-input=yes, ai-train=no\nAllow: /\n\nSitemap: https://example.com/sitemap.xml\n";

    public function test_it_parses_the_default_template(): void
    {
        $this->assertSame(['search' => true, 'ai-input' => true, 'ai-train' => false], ContentSignal::parse(self::DEFAULT));
    }

    public function test_a_missing_line_or_group_gives_nulls(): void
    {
        $nulls = ['search' => null, 'ai-input' => null, 'ai-train' => null];

        $this->assertSame($nulls, ContentSignal::parse("User-agent: *\nAllow: /\n"));
        $this->assertSame($nulls, ContentSignal::parse("User-agent: Googlebot\nContent-Signal: search=yes\n"));
        $this->assertSame($nulls, ContentSignal::parse(''));
    }

    public function test_only_the_star_group_counts(): void
    {
        $robots = "User-agent: Googlebot\nContent-Signal: search=no\nDisallow:\n\nUser-agent: *\nContent-Signal: search=yes\n\nUser-agent: Bing\nContent-Signal: ai-train=yes\n";

        $this->assertSame(['search' => true, 'ai-input' => null, 'ai-train' => null], ContentSignal::parse($robots));
    }

    public function test_consecutive_user_agents_share_a_group_and_comments_do_not_end_it(): void
    {
        $robots = "User-agent: Bing\n# note\nUser-agent: *\n\n# more\nContent-Signal: ai-train=no\n";

        $this->assertSame(['search' => null, 'ai-input' => null, 'ai-train' => false], ContentSignal::parse($robots));
    }

    public function test_names_values_and_spacing_are_lenient(): void
    {
        $robots = "user-AGENT : *\r\ncontent-signal :  Search = YES ,AI-Input=No , ai-train=maybe # why\r\n";

        $this->assertSame(['search' => true, 'ai-input' => false, 'ai-train' => null], ContentSignal::parse($robots));
    }

    public function test_apply_updates_in_place_and_keeps_unknown_keys(): void
    {
        $robots = "User-agent: *\nContent-Signal: ai-train=no, future=yes, search=yes\nAllow: /\n";

        $out = ContentSignal::apply($robots, ['search' => false, 'ai-input' => true, 'ai-train' => null]);

        $this->assertSame("User-agent: *\nContent-Signal: future=yes, search=no, ai-input=yes\nAllow: /\n", $out);
    }

    public function test_apply_appends_new_keys_in_canonical_order(): void
    {
        $out = ContentSignal::apply("User-agent: *\nContent-Signal: ai-train=no\n", ['search' => true, 'ai-input' => true, 'ai-train' => false]);

        $this->assertSame("User-agent: *\nContent-Signal: ai-train=no, search=yes, ai-input=yes\n", $out);
    }

    public function test_all_null_removes_the_line(): void
    {
        $out = ContentSignal::apply(self::DEFAULT, ['search' => null, 'ai-input' => null, 'ai-train' => null]);

        $this->assertSame("User-agent: *\nAllow: /\n\nSitemap: https://example.com/sitemap.xml\n", $out);
    }

    public function test_an_unknown_key_keeps_the_line_alive(): void
    {
        $out = ContentSignal::apply("User-agent: *\nContent-Signal: future=yes, search=yes\n", ['search' => null]);

        $this->assertSame("User-agent: *\nContent-Signal: future=yes\n", $out);
    }

    public function test_a_missing_line_is_inserted_after_the_last_user_agent_of_the_star_group(): void
    {
        $robots = "User-agent: Googlebot\nDisallow: /g\n\nUser-agent: Bing\n# c\nUser-agent: *\nAllow: /\n\nUser-agent: Other\nDisallow: /\n";

        $out = ContentSignal::apply($robots, ['search' => true, 'ai-input' => null, 'ai-train' => false]);

        $this->assertSame("User-agent: Googlebot\nDisallow: /g\n\nUser-agent: Bing\n# c\nUser-agent: *\nContent-Signal: search=yes, ai-train=no\nAllow: /\n\nUser-agent: Other\nDisallow: /\n", $out);
    }

    public function test_nothing_is_inserted_when_everything_is_null(): void
    {
        $robots = "User-agent: *\nAllow: /\n";

        $this->assertSame($robots, ContentSignal::apply($robots, ['search' => null, 'ai-input' => null, 'ai-train' => null]));
    }

    public function test_without_a_star_group_one_is_prepended(): void
    {
        $out = ContentSignal::apply("User-agent: Bing\nDisallow: /\n", ['search' => true, 'ai-input' => null, 'ai-train' => null]);

        $this->assertSame("User-agent: *\nContent-Signal: search=yes\n\nUser-agent: Bing\nDisallow: /\n", $out);
        $this->assertSame("User-agent: *\nContent-Signal: ai-train=no\n", ContentSignal::apply('', ['ai-train' => false]));
    }

    public function test_crlf_is_preserved(): void
    {
        $robots = "User-agent: *\r\nContent-Signal: search=yes\r\nAllow: /\r\n";

        $this->assertSame("User-agent: *\r\nContent-Signal: search=no\r\nAllow: /\r\n", ContentSignal::apply($robots, ['search' => false]));
        $this->assertSame("User-agent: *\r\nContent-Signal: search=yes\r\nAllow: /\r\n", ContentSignal::apply("User-agent: *\r\nAllow: /\r\n", ['search' => true]));
        $this->assertSame("Allow: /\r\n", ContentSignal::apply("Allow: /\r\n", ['search' => null]));
    }

    public function test_applying_what_was_parsed_changes_nothing(): void
    {
        foreach ([self::DEFAULT, "User-agent: *\r\nContent-Signal: search=no\r\nDisallow: /x\r\n", "User-agent: *\nAllow: /\n", ''] as $robots) {
            $this->assertSame($robots, ContentSignal::apply($robots, ContentSignal::parse($robots)));
        }
    }

    public function test_a_trailing_comment_survives(): void
    {
        $out = ContentSignal::apply("User-agent: *\nContent-Signal: search=yes # keep\n", ['search' => false]);

        $this->assertSame("User-agent: *\nContent-Signal: search=no # keep\n", $out);
    }
}
