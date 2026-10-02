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
        $head = "# {$title}\nSource: {$url}";
        $body = trim($bodyMarkdown);

        return $body === '' ? $head : "{$head}\n\n{$body}";
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
        $preamble = "# {$siteName}";

        if ($description !== null && trim($description) !== '') {
            $preamble .= "\n\n> ".trim($description);
        }

        return implode("\n\n---\n\n", [$preamble, ...$entries]);
    }
}
