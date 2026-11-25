<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;

/** HomeHub ::: left|center|right|justify blocks, rendered with safe fixed classes. */
final class AlignedMarkdown
{
    public static function converter(): MarkdownConverter
    {
        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        return new MarkdownConverter($environment);
    }

    public static function render(string $source, MarkdownConverter $converter): string
    {
        $lines = preg_split('/\R/', $source) ?: [];
        $out = '';
        $buffer = [];
        $stack = [];
        $fence = null;
        $flush = static function () use (&$buffer, &$out, $converter): void {
            if ($buffer !== []) {
                $out .= $converter->convert(implode("\n", $buffer))->getContent();
                $buffer = [];
            }
        };
        foreach ($lines as $line) {
            if (preg_match('/^\s*(`{3,}|~{3,})/', $line, $match)) {
                $marker = $match[1];
                if ($fence === null) $fence = $marker;
                elseif ($marker[0] === $fence[0] && strlen($marker) >= strlen($fence)) $fence = null;
            }
            if ($fence === null && preg_match('/^:::\s*(left|center|right|justify)\s*$/', $line, $match)) {
                $flush();
                $out .= '<div class="homehub-align-' . $match[1] . '">';
                $stack[] = $match[1];
                continue;
            }
            if ($fence === null && $stack !== [] && preg_match('/^:::\s*$/', $line)) {
                $flush();
                array_pop($stack);
                $out .= '</div>';
                continue;
            }
            $buffer[] = $line;
        }
        $flush();
        while ($stack !== []) { array_pop($stack); $out .= '</div>'; }
        return $out;
    }

    /** Convert HomeHub blocks to Pandoc fenced divs for document export. */
    public static function forPandoc(string $source): string
    {
        return preg_replace('/^:::\s*(left|center|right|justify)\s*$/m', '::: {.homehub-align-$1}', $source) ?? $source;
    }
}
