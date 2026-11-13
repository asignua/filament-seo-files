<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\Data\LlmsLink;
use Asignua\FilamentSeoFiles\Data\LlmsSection;

/**
 * Renders an llms.txt document (https://llmstxt.org/): H1 → blockquote summary → H2
 * sections with `- [Title](url): description` lists.
 *
 * The class knows nothing about Eloquent or config — it takes ready sections, so it is
 * testable without a database. Gathering the data is {@see LlmsTxtFile}'s job.
 */
class LlmsTxt
{
    /**
     * @param list<LlmsSection> $sections
     */
    public static function render(string $title, ?string $summary, array $sections): string
    {
        $blocks = ['# '.self::line($title)];

        $summary = self::line($summary);

        if ($summary !== '') {
            $blocks[] = '> '.$summary;
        }

        foreach ($sections as $section) {
            $body = self::body($section->body);

            if ($section->links === [] && $body === '') {
                continue;
            }

            $parts = ['## '.self::line($section->title)];

            if ($body !== '') {
                $parts[] = $body;
            }

            if ($section->links !== []) {
                $parts[] = implode("\n", array_map(self::renderLink(...), $section->links));
            }

            $blocks[] = implode("\n\n", $parts);
        }

        return implode("\n\n", $blocks)."\n";
    }

    private static function renderLink(LlmsLink $link): string
    {
        $line = '- ['.self::escape($link->title).']('.self::url($link->url).')';
        $description = self::escape($link->description);

        return $description !== '' ? $line.': '.$description : $line;
    }

    /**
     * Section prose: line endings normalised, outer blank lines trimmed, and every line that
     * would start a heading escaped — a `#` there would end the section or open a second H1.
     */
    private static function body(?string $value): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $value), "\n");

        if (trim($text) === '') {
            return '';
        }

        return (string) preg_replace('/^[ \t]*(#)/m', '\\\\$1', $text);
    }

    /**
     * A list item is exactly one line: line breaks and repeated spaces are collapsed.
     */
    private static function line(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * A link destination: parentheses (wiki-style slugs), spaces and angle brackets would
     * end or break `[title](url)`, so they are percent-encoded — the address stays the same.
     */
    private static function url(string $value): string
    {
        return str_replace(['(', ')', ' ', '<', '>'], ['%28', '%29', '%20', '%3C', '%3E'], self::line($value));
    }

    /**
     * Square brackets in the text would break the Markdown link.
     */
    private static function escape(?string $value): string
    {
        return str_replace(['[', ']'], ['\[', '\]'], self::line($value));
    }
}
