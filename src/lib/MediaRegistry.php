<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
namespace HomeHub;

/** Persistent identity map; all mutations use an exclusive lock and atomic replacement. */
final class MediaRegistry
{
    private string $file;
    public function __construct(private readonly string $configDirectory)
    {
        $this->file = rtrim($configDirectory, '/') . '/media-index.json';
    }
    private function transaction(callable $callback): mixed
    {
        if (!is_dir($this->configDirectory)) throw new \RuntimeException('Konfigurationsordner fehlt.');
        $lock = fopen($this->file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new \RuntimeException('Medienregister konnte nicht gesperrt werden.');
        try {
            $data = ['version'=>1, 'items'=>[]];
            if (is_file($this->file)) {
                $raw = file_get_contents($this->file);
                $decoded = json_decode((string)$raw, true);
                if (!is_array($decoded) || ($decoded['version'] ?? null) !== 1 || !isset($decoded['items']) || !is_array($decoded['items'])) throw new \RuntimeException('Medienregister ist beschädigt.');
                $data = $decoded;
            }
            $before = $data;
            $result = $callback($data);
            if ($data !== $before) {
                $tmp = tempnam($this->configDirectory, '.media-');
                if ($tmp === false) throw new \RuntimeException('Medienregister nicht beschreibbar.');
                try {
                    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                    if (file_put_contents($tmp, $json . "\n") === false || !rename($tmp, $this->file)) throw new \RuntimeException('Medienregister konnte nicht gespeichert werden.');
                    chmod($this->file, 0640);
                } finally { if (is_file($tmp)) unlink($tmp); }
            }
            return $result;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    public function register(string $path): string
    {
        if (!MediaLibrary::validRelativePath($path)) throw new \RuntimeException('Ungültiger Medienpfad.');
        return $this->transaction(static function (array &$data) use ($path): string {
            foreach ($data['items'] as $id => $item) if (($item['path'] ?? null) === $path) return $id;
            do { $id = bin2hex(random_bytes(16)); } while (isset($data['items'][$id]));
            $data['items'][$id] = ['path'=>$path, 'name'=>basename($path)];
            return $id;
        });
    }
    public function pathForId(string $id): string
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $id)) throw new \RuntimeException('Ungültige Medien-ID.');
        return $this->transaction(static function (array &$data) use ($id): string {
            $path = $data['items'][$id]['path'] ?? null;
            if (!is_string($path) || !MediaLibrary::validRelativePath($path)) throw new \RuntimeException('Medien-ID nicht gefunden.');
            return $path;
        });
    }
    public function idForPath(string $path): ?string
    {
        return $this->transaction(static function (array &$data) use ($path): ?string {
            foreach ($data['items'] as $id => $item) if (($item['path'] ?? null) === $path) return $id;
            return null;
        });
    }
    /** Move a registered file and update its path under the registry lock. */
    public function relocate(string $old, string $new, MediaLibrary $media): string
    {
        if (!MediaLibrary::validRelativePath($old) || !MediaLibrary::validRelativePath($new) || $old === $new) {
            throw new \RuntimeException('Ungültige oder unveränderte Medienpfade.');
        }
        $moved = false;
        try {
            return $this->transaction(static function (array &$data) use ($old, $new, $media, &$moved): string {
                $id = null;
                foreach ($data['items'] as $key => $item) {
                    if (($item['path'] ?? null) === $new) throw new \RuntimeException('Ziel ist bereits registriert.');
                    if (($item['path'] ?? null) === $old) $id = $key;
                }
                if ($id === null) throw new \RuntimeException('Quelldatei ist nicht registriert.');
                $media->move($old, $new);
                $moved = true;
                $data['items'][$id]['path'] = $new;
                $data['items'][$id]['name'] = basename($new);
                return $id;
            });
        } catch (\Throwable $error) {
            if ($moved) {
                try { $media->move($new, $old); }
                catch (\Throwable $rollback) {
                    throw new \RuntimeException('Kritischer Fehler: Medienverschiebung konnte nicht zurückgesetzt werden. Bitte Datei und Register prüfen.', 0, $error);
                }
            }
            throw $error;
        }
    }
    public function unregister(string $path): void
    {
        $this->transaction(static function (array &$data) use ($path): void {
            foreach ($data['items'] as $id => $item) if (($item['path'] ?? null) === $path) unset($data['items'][$id]);
        });
    }
}
