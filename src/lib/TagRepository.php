<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
namespace HomeHub;

/** Central JSON tag catalogue; based on the supplied TagRepository API. */
final class TagRepository
{
    private ?array $tags = null;
    public function __construct(private readonly string $filePath) {}

    public static function normalize(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            if (!is_string($tag)) continue;
            $tag = trim($tag);
            if ($tag !== '') $result[] = $tag;
        }
        $result = array_values(array_unique($result));
        sort($result, SORT_STRING);
        return $result;
    }

    public function all(): array
    {
        if ($this->tags === null) $this->mutate(static fn(array $old): array => $old);
        return $this->tags;
    }

    public static function isId(string $value): bool
    {
        return (bool) preg_match('/\At_[a-f0-9]{32}\z/', $value);
    }

    private static function validate(string $name): void
    {
        if (self::isId($name) || !preg_match('/\A[^,\r\n]{1,40}\z/u', $name)) throw new \RuntimeException('Ungültiges Schlagwort (1 bis 40 Zeichen, ohne Komma, Zeilenumbruch oder reservierte ID).');
    }

    public function names(array $labels): array
    {
        if (!array_filter($labels, self::isId(...))) return $labels;
        $catalogue = $this->all();
        return array_map(static fn($label) => $catalogue[$label] ?? $label, $labels);
    }

    /** Resolve selected IDs and register newly typed names under the catalogue lock. */
    public function assign(array $labels): array
    {
        if (!$labels) return [];
        $ids = [];
        $this->mutate(static function(array $old) use ($labels, &$ids): array {
            foreach ($labels as $label) {
                if (self::isId($label)) {
                    if (!isset($old[$label])) throw new \RuntimeException('Schlagwort nicht mehr vorhanden. Bitte neu laden.');
                    $id = $label;
                } else {
                    self::validate($label);
                    $id = array_search($label, $old, true);
                    if ($id === false) { $id = 't_' . bin2hex(random_bytes(16)); $old[$id] = $label; }
                }
                if (!in_array($id, $ids, true)) $ids[] = $id;
            }
            return $old;
        });
        return $ids;
    }

    public function change(string $action, string $original, string $tag): bool
    {
        $tag = trim($tag);
        if ($action !== 'tag-delete') self::validate($tag);
        return $this->mutate(static function(array $old) use ($action, $original, $tag): array {
            if (!in_array($action, ['tag-create', 'tag-update', 'tag-delete'], true)) throw new \RuntimeException('Ungültige Aktion.');
            if ($action !== 'tag-create' && !isset($old[$original])) throw new \RuntimeException('Schlagwort nicht mehr vorhanden.');
            $existing = array_search($tag, $old, true);
            if ($action !== 'tag-delete' && $existing !== false && ($action === 'tag-create' || $existing !== $original)) throw new \RuntimeException('Schlagwort existiert bereits.');
            if ($action === 'tag-delete') unset($old[$original]);
            else $old[$action === 'tag-create' ? 't_' . bin2hex(random_bytes(16)) : $original] = $tag;
            return $old;
        });
    }

    private function mutate(callable $operation): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) throw new \RuntimeException('Tag-Ordner fehlt.');
        $lock = fopen($this->filePath . '.lock', 'c');
        if ($lock === false) throw new \RuntimeException('Tag-Sperre nicht verfügbar.');
        try {
            if (!flock($lock, LOCK_EX)) throw new \RuntimeException('Tag-Sperre fehlgeschlagen.');
            $exists = is_file($this->filePath);
            $old = [];
            if ($exists) {
                $json = file_get_contents($this->filePath);
                if ($json === false) throw new \RuntimeException('Tag-Datei nicht lesbar.');
                $old = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
                if (!is_array($old)) throw new \RuntimeException('Ungültige Tag-Datei.');
                $entries = $old; $old = [];
                foreach ($entries as $entry) {
                    if (!is_object($entry) || !is_string($entry->id ?? null) || !self::isId($entry->id) || !is_string($entry->name ?? null) || isset($old[$entry->id]) || in_array($entry->name, $old, true)) throw new \RuntimeException('Ungültige Tag-Datei oder altes Format. TAG-004 benötigt einen neuen Katalog.');
                    self::validate($entry->name);
                    $old[$entry->id] = $entry->name;
                }
            }
            $merged = $operation($old);
            asort($merged, SORT_STRING);
            $changed = !$exists || $merged !== $old;
            if ($changed) {
                $json = json_encode(array_map(static fn($id, $name) => ['id'=>$id, 'name'=>$name], array_keys($merged), array_values($merged)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $temp = tempnam($dir, '.tags-');
                if ($temp === false) throw new \RuntimeException('Tag-Datei konnte nicht erstellt werden.');
                try {
                    if (file_put_contents($temp, $json . "\n") === false || !rename($temp, $this->filePath)) throw new \RuntimeException('Tag-Datei konnte nicht gespeichert werden.');
                } finally { if (is_file($temp)) unlink($temp); }
            }
            $this->tags = $merged;
            return $changed;
        } finally { fclose($lock); }
    }
}
