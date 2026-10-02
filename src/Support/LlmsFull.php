<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\Data\LlmsDocument;

/**
 * Pure Markdown rendering of llms-full.txt (https://llmstxt.org/): a site preamble plus
 * entries (the full page content) separated by a thematic break. Like {@see LlmsTxt} it
 * knows nothing about Eloquent and takes ready strings.
 */
class LlmsFull
{
    /**
     * One entry: `# Title` + a `Source: url` line + the Markdown body. An empty body
     * leaves just the heading and the source.
     */
    public static function entry(string $title, string $url, string $bodyMarkdown): string
    {
        // A heading and the source line are one line each: a line break in the title would
        // push its tail out of the `# Title` heading and break the entry header.
        $head = '# '.self::line($title)."\nSource: ".self::line($url);
        $body = trim($bodyMarkdown);

        return $body === '' ? $head : "{$head}\n\n{$body}";
    }

    private static function line(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    public static function entryFor(LlmsDocument $document): string
    {
        return self::entry($document->title, $document->url, $document->markdown);
    }

    /**
     * The whole document: the preamble (`# {siteName}` + an optional description
     * blockquote) and the entries joined with `\n\n---\n\n`.
     *
     * @param list<string> $entries
     */
    public static function document(string $siteName, ?string $description, array $entries): string
    {
        // One line each: a newline in the name would end the heading, one in the
        // description would leave the rest outside the blockquote.
        $preamble = '# '.self::line($siteName);
        $description = self::line((string) $description);

        if ($description !== '') {
            $preamble .= "\n\n> ".$description;
        }

        return implode("\n\n---\n\n", [$preamble, ...$entries]);
    }
}
