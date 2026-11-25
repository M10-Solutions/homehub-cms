<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
namespace HomeHub;
use Symfony\Component\Yaml\Yaml;

/** Protect page assignments while administering the central catalogue. */
final class TagAdministration
{
    public function __construct(private readonly string $content, private readonly string $config) {}
    private function usage(): array
    {
        $counts = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->content, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!$file->isFile() || $file->isLink() || strtolower($file->getExtension()) !== 'md') continue;
            $raw = file_get_contents($file->getPathname());
            if ($raw === false) throw new \RuntimeException('Seite nicht lesbar.');
            if (!preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', $raw, $m)) continue;
            $meta = Yaml::parse($m[1]);
            if (!is_array($meta)) throw new \RuntimeException('Ungültige Seitenmetadaten.');
            $labels = $meta['labels'] ?? [];
            if (is_string($labels)) $labels = explode(',', $labels);
            if (!is_array($labels)) throw new \RuntimeException('Ungültige Schlagwörter in Seite.');
            foreach (TagRepository::normalize($labels) as $tag) $counts[$tag] = ($counts[$tag] ?? 0) + 1;
        }
        return $counts;
    }
    public function all(): array
    {
        return (new MediaOperationLock($this->config))->run(function () {
            $usage = $this->usage();
            $repo = new TagRepository($this->config . '/tags.json');
            $entries = $repo->all();
            return array_map(static fn($id, $tag) => ['id'=>$id, 'tag'=>$tag, 'count'=>$usage[$id] ?? 0], array_keys($entries), array_values($entries));
        });
    }
    public function change(string $action, string $original, string $tag): void
    {
        (new MediaOperationLock($this->config))->run(function () use ($action, $original, $tag) {
            if ($action === 'tag-delete' && isset($this->usage()[$original])) throw new \RuntimeException('Schlagwort wird noch auf Seiten verwendet. Bitte zuerst die Zuordnungen entfernen.');
            $repo = new TagRepository($this->config . '/tags.json');
            $repo->change($action, $original, $tag);
        });
    }
}
