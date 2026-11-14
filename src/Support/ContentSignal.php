<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

/**
 * Reads and rewrites the `Content-Signal` directive (contentsignals.org) of the `User-agent: *`
 * group of a robots.txt. Pure text in, text out: the editor's switches are only a view of it.
 *
 * Only the one line is touched; every other byte, including the line-ending style, stays.
 */
final class ContentSignal
{
    public const KEYS = ['search', 'ai-input', 'ai-train'];

    /**
     * @return array{search: ?bool, 'ai-input': ?bool, 'ai-train': ?bool}
     */
    public static function parse(string $robots): array
    {
        $result = ['search' => null, 'ai-input' => null, 'ai-train' => null];
        $found = self::locate(explode("\n", $robots));

        if ($found['line'] === null) {
            return $result;
        }

        foreach ($found['pairs'] as [$key, $value]) {
            if (array_key_exists($key, $result) && $result[$key] === null) {
                $result[$key] = match (strtolower($value)) {
                    'yes' => true,
                    'no' => false,
                    default => null,
                };
            }
        }

        return $result;
    }

    /**
     * @param array<string, ?bool> $signals keyed by KEYS; a key that is absent stays as it is
     */
    public static function apply(string $robots, array $signals): string
    {
        $lines = explode("\n", $robots);
        $cr = str_contains($robots, "\r\n") ? "\r" : '';
        $found = self::locate($lines);

        $pairs = [];
        $seen = [];

        foreach ($found['pairs'] as [$key, $value]) {
            if (!in_array($key, self::KEYS, true)) {
                $pairs[] = [$key, $value];

                continue;
            }

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            if (!array_key_exists($key, $signals)) {
                $pairs[] = [$key, $value];
            } elseif ($signals[$key] !== null) {
                $pairs[] = [$key, $signals[$key] ? 'yes' : 'no'];
            }
        }

        foreach (self::KEYS as $key) {
            if (!isset($seen[$key]) && ($signals[$key] ?? null) !== null) {
                $pairs[] = [$key, $signals[$key] ? 'yes' : 'no'];
            }
        }

        $text = $pairs === []
            ? null
            : 'Content-Signal: '.implode(', ', array_map(static fn (array $pair): string => $pair[0].'='.$pair[1], $pairs));

        if ($found['line'] !== null) {
            if ($text === null) {
                unset($lines[$found['line']]);
            } else {
                $lines[$found['line']] = $text.$found['comment'].$cr;
            }

            return implode("\n", $lines);
        }

        if ($text === null) {
            return $robots;
        }

        if ($found['lastAgent'] !== null) {
            array_splice($lines, $found['lastAgent'] + 1, 0, [$text.$cr]);

            return implode("\n", $lines);
        }

        $head = ['User-agent: *'.$cr, $text.$cr];

        if (trim($robots) !== '') {
            $head[] = $cr;
        }

        return implode("\n", array_merge($head, $lines));
    }

    /**
     * Finds the first Content-Signal line of a `*` group and the last User-agent line of the
     * first `*` group (where a missing directive goes).
     *
     * @param list<string> $lines split on "\n", so a CRLF file keeps its "\r"
     *
     * @return array{line: ?int, comment: string, pairs: list<array{string, string}>, lastAgent: ?int}
     */
    private static function locate(array $lines): array
    {
        $out = ['line' => null, 'comment' => '', 'pairs' => [], 'lastAgent' => null];

        $agents = [];
        $inDirectives = false;
        $starClosed = false;

        foreach ($lines as $index => $raw) {
            $line = trim($raw);
            $comment = '';
            $hash = strpos($line, '#');

            if ($hash !== false) {
                $comment = ' '.substr($line, $hash);
                $line = trim(substr($line, 0, $hash));
            }

            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode(':', $line, 2));
            $name = strtolower($name);

            if ($name === 'user-agent') {
                if ($inDirectives) {
                    $agents = [];
                    $inDirectives = false;
                }

                $agents[] = $value;

                if (!$starClosed && in_array('*', $agents, true)) {
                    $out['lastAgent'] = $index;
                }

                continue;
            }

            $inDirectives = true;
            $isStar = in_array('*', $agents, true);

            if ($isStar) {
                $starClosed = true;
            }

            if ($isStar && $name === 'content-signal' && $out['line'] === null) {
                $out['line'] = $index;
                $out['comment'] = $comment;
                $out['pairs'] = self::pairs($value);
            }
        }

        return $out;
    }

    /**
     * @return list<array{string, string}>
     */
    private static function pairs(string $value): array
    {
        $pairs = [];

        foreach (explode(',', $value) as $part) {
            if (preg_match('/^\s*([A-Za-z0-9_-]+)\s*=\s*(\S+)\s*$/', $part, $m) === 1) {
                $pairs[] = [strtolower($m[1]), $m[2]];
            }
        }

        return $pairs;
    }
}
