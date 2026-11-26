<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
namespace HomeHub;

/** Portable chapter archive; only pages supplied by the permission-filtered endpoint. */
final class ChapterZipExporter
{
    public function __construct(private readonly string $contentDirectory, private readonly string $uploadDirectory, private readonly string $configDirectory) {}

    /** @param list<Page> $pages */
    public function export(array $pages, string $slug): array
    {
        $directory = sys_get_temp_dir() . '/homehub-export-' . bin2hex(random_bytes(12));
        if (!mkdir($directory, 0700)) throw new \RuntimeException('Exportverzeichnis konnte nicht erstellt werden.');
        $output = $directory . '/kapitel.zip';
        try {
            (new MediaOperationLock($this->configDirectory))->run(function () use ($pages, $output): void {
                $zip = new \ZipArchive();
                if ($zip->open($output, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) throw new \RuntimeException('ZIP konnte nicht erstellt werden.');
                try {
                    $this->collect($pages,
                        static function (string $name, string $body) use ($zip): void {
                            if (!$zip->addFromString($name, $body)) throw new \RuntimeException('ZIP-Eintrag fehlgeschlagen.');
                        },
                        static function (string $name, string $path) use ($zip): void {
                            if (!$zip->addFile($path, $name)) throw new \RuntimeException('ZIP-Medieneintrag fehlgeschlagen.');
                        });
                } finally {
                    if (!$zip->close()) throw new \RuntimeException('ZIP konnte nicht gespeichert werden.');
                }
            });
            return ['path'=>$output, 'filename'=>str_replace('/', '-', $slug).'.zip', 'mime'=>'application/zip', 'directory'=>$directory];
        } catch (\Throwable $error) {
            DocumentExporter::cleanup($directory);
            throw $error;
        }
    }

    /** Collect entries separately from the ZIP transport for isolated regression tests. */
    public function collect(array $pages, callable $addText, callable $addFile): void
    {
        $base = realpath($this->contentDirectory);
        if ($base === false) throw new \RuntimeException('Content-Ordner fehlt.');
        $media = new MediaLibrary($this->uploadDirectory);
        $registry = new MediaRegistry($this->configDirectory);
        $ids = []; $slugs = []; $included = []; $warnings = [];
        foreach ($pages as $page) {
            if (!preg_match('~\A[a-z0-9_-]+(?:/[a-z0-9_-]+)*\z~D', $page->slug)) throw new \RuntimeException('Ungültiger Seitenpfad.');
            $slugs[$page->slug] = true;
            if ($page->id !== null) $ids[$page->id] = isset($ids[$page->id]) ? false : $page->slug;
        }
        foreach ($pages as $page) {
            $path = $base . '/' . $page->slug . '.md';
            $real = realpath($path);
            if ($real !== $path || !is_file($real)) throw new \RuntimeException('Seitenpfad nicht verfügbar.');
            $raw = file_get_contents($real);
            if ($raw === false) throw new \RuntimeException('Seite konnte nicht gelesen werden.');
            $rendered = AlignedMarkdown::render($page->content, AlignedMarkdown::converter());
            $prefix = str_repeat('../', substr_count($page->slug, '/') + 1);
            $rewrite = function (string $url) use ($rendered, $media, $registry, $prefix, $page, $ids, $slugs, &$included, &$warnings, $addFile): ?string {
                $parts = parse_url(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) return null;
                $path = $parts['path'] ?? '';
                parse_str($parts['query'] ?? '', $query);
                $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
                if (in_array($path, ['/page.php', 'page.php'], true)) {
                    $target = is_string($query['id'] ?? null) ? ($ids[$query['id']] ?? null) : null;
                    return is_string($target) ? $prefix . 'pages/' . $target . '.md' . $fragment : null;
                }
                if (in_array($path, ['/', '/index.php', 'index.php'], true) && is_string($query['page'] ?? null) && isset($slugs[$query['page']])) return $prefix . 'pages/' . $query['page'] . '.md' . $fragment;
                try {
                    $selector = MediaAccess::reference($url);
                    if ($selector === null || !MediaAccess::contains($rendered, $selector)) return null;
                    $name = isset($selector['id']) ? $registry->pathForId($selector['id']) : $selector['file'];
                    if (!is_string($name)) throw new \RuntimeException('Ungültiger Medienpfad.');
                    $file = $media->resolve($name);
                } catch (\Throwable) {
                    $warnings[] = $page->slug . ': ' . str_replace(["\r", "\n"], '', $url);
                    return null;
                }
                if (!isset($included[$name])) {
                    $addFile('media/' . $name, $file['path']);
                    $included[$name] = true;
                }
                return $prefix . 'media/' . implode('/', array_map('rawurlencode', explode('/', $name))) . $fragment;
            };
            // Collect even references whose Markdown spelling the portable rewriter cannot handle.
            foreach (MediaAccess::references($rendered) as $selector) {
                $rewrite('/media.php?' . http_build_query($selector, '', '&', PHP_QUERY_RFC3986));
            }
            // Portable Markdown contains names, not installation-specific tag IDs.
            if (preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', $raw, $front) && preg_match('/^labels:/m', $front[1]) && preg_match('/\bt_[a-f0-9]{32}\b/', $front[1])) {
                $meta = \Symfony\Component\Yaml\Yaml::parse($front[1]);
                if (is_array($meta) && isset($meta['labels'])) {
                    $meta['labels'] = $page->labels;
                    $raw = "---\n" . \Symfony\Component\Yaml\Yaml::dump($meta, 2, 2) . "---\n" . $front[2];
                }
            }
            $addText('pages/' . $page->slug . '.md', self::rewriteMarkdown($raw, $rewrite));
        }
        if ($warnings) $addText('EXPORT-HINWEISE.txt', "Nicht verfügbare Medien (Originalverweise beibehalten):\n" . implode("\n", array_unique($warnings)) . "\n");
    }

    private static function rewriteMarkdown(string $raw, callable $rewrite): string
    {
        $lines = preg_split('/(?<=\n)/', $raw);
        $front = trim($lines[0] ?? '') === '---'; $fence = null;
        foreach ($lines as $n => &$line) {
            if ($front) { if ($n !== 0 && trim($line) === '---') $front = false; continue; }
            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $m)) {
                if ($fence === null) $fence = $m[1];
                elseif ($fence[0] === $m[1][0] && strlen($m[1]) >= strlen($fence)) $fence = null;
                continue;
            }
            if ($fence !== null || preg_match('/^(?: {4}|\t)/', $line)) continue;
            $pieces = preg_split('/(`+[^`\r\n]*`+)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($pieces as $i => &$piece) {
                if ($i % 2) continue;
                $replace = static fn(array $m): string => $m[1] . ($rewrite($m[2]) ?? $m[2]) . $m[3];
                $piece = preg_replace_callback('~(!?\[[^\]\r\n]*\]\(\s*<?)([^\s)>]+)(>?[^)]*\))~', $replace, $piece);
                $piece = preg_replace_callback('~(^\s{0,3}\[[^\]\r\n]+\]:\s*<?)([^\s>]+)(>?[^\r\n]*)~m', $replace, $piece);
                $piece = preg_replace_callback('~<img\b[^>]*>~i', static function (array $m) use ($rewrite): string {
                    return preg_replace_callback('~(\bsrc\s*=\s*)(["\'])(.*?)\2~i', static fn(array $a): string => $a[1].$a[2].($rewrite($a[3]) ?? $a[3]).$a[2], $m[0]);
                }, $piece);
            }
            unset($piece);
            $line = implode('', $pieces);
        }
        unset($line);
        return implode('', $lines);
    }
}
