<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

final class MediaLibrary
{
    private const TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/gif' => ['gif'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt'],
    ];

    public function __construct(private readonly string $directory) {}

    public static function validName(string $name): bool
    {
        return (bool) preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,149}\z/D', $name)
            && !str_contains($name, '..');
    }

    public static function validFolder(string $folder): bool
    {
        if ($folder === '') return true;
        if (strlen($folder) > 180) return false;
        foreach (explode('/', $folder) as $part) {
            if (!self::validName($part) || $part === '.' || $part === '..') return false;
        }
        return true;
    }

    public static function validRelativePath(string $path): bool
    {
        $parts = explode('/', $path);
        return $path !== '' && array_reduce($parts, static fn(bool $ok, string $part): bool => $ok && self::validName($part) && $part !== '.' && $part !== '..', true);
    }

    public function folders(): array
    {
        $result = [''];
        $walk = function (string $relative) use (&$walk, &$result): void {
            $base = $this->path($relative === '' ? 'folder-placeholder' : $relative . '/folder-placeholder');
            $dir = dirname($base);
            if (!is_dir($dir)) return;
            foreach (new \DirectoryIterator($dir) as $entry) {
                if ($entry->isDot() || !$entry->isDir() || $entry->isLink()) continue;
                $next = ltrim($relative . '/' . $entry->getFilename(), '/');
                if (!self::validFolder($next)) continue;
                $result[] = $next;
                $walk($next);
            }
        };
        $walk('');
        natcasesort($result);
        return array_values($result);
    }

    public function createFolder(string $folder): void
    {
        if ($folder === '' || !self::validFolder($folder)) throw new \RuntimeException('Ungültiger Ordnername.');
        $path = $this->path($folder . '/folder-placeholder');
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0755, true)) throw new \RuntimeException('Ordner konnte nicht angelegt werden.');
    }

    public function path(string $name): string
    {
        $parts = explode('/', $name);
        if (!$parts || !array_reduce($parts, static fn(bool $ok, string $part): bool => $ok && self::validName($part) && $part !== '.' && $part !== '..', true)) {
            throw new \RuntimeException('Ungültiger Medienpfad.');
        }
        $base = realpath($this->directory);
        if ($base === false) throw new \RuntimeException('Medienordner fehlt.');
        $path = $base;
        foreach ($parts as $part) {
            $path .= '/' . $part;
            if (is_link($path)) throw new \RuntimeException('Symbolische Links sind nicht erlaubt.');
        }
        return $path;
    }

    /** @return list<array{name:string,size:int,url:string,image:bool}> */
    public function all(string $folder = ''): array
    {
        if (!self::validFolder($folder)) throw new \RuntimeException('Ungültiger Ordner.');
        $dir = $folder === '' ? $this->directory : dirname($this->path($folder . '/folder-placeholder'));
        if (!is_dir($dir)) return [];
        $items = [];
        foreach (new \DirectoryIterator($dir) as $entry) {
            if ($entry->isDot() || !$entry->isFile() || $entry->isLink()) continue;
            $name = $entry->getFilename();
            if (!self::validName($name)) continue;
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($entry->getPathname());
            if (!isset(self::TYPES[$mime])) continue;
            $relative = ltrim($folder . '/' . $name, '/');
            $items[] = ['name'=>$name, 'path'=>$relative, 'size'=>$entry->getSize(), 'url'=>'/media.php?file='.rawurlencode($relative), 'image'=>str_starts_with($mime, 'image/')];
        }
        usort($items, static fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));
        return $items;
    }

    public function upload(array $upload, string $folder = ''): string
    {
        if (!self::validFolder($folder)) throw new \RuntimeException('Ungültiger Ordner.');
        if ($folder !== '' && !is_dir(dirname($this->path($folder . '/folder-placeholder')))) throw new \RuntimeException('Ordner existiert nicht.');
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new \RuntimeException('Upload fehlgeschlagen.');
        $size = (int) ($upload['size'] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) throw new \RuntimeException('Maximal 10 MB pro Datei.');
        $tmp = (string) ($upload['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) throw new \RuntimeException('Ungültiger Upload.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extension = strtolower(pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$mime]) || !in_array($extension, self::TYPES[$mime], true)) {
            throw new \RuntimeException('Erlaubt sind JPG, PNG, WebP, GIF, PDF und TXT.');
        }
        $original = pathinfo((string) $upload['name'], PATHINFO_FILENAME);
        $stem = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $original), '-'));
        if ($stem === '') $stem = 'datei';
        $stem = substr($stem, 0, 65);
        $name = $stem . '-' . bin2hex(random_bytes(5)) . '.' . $extension;
        $name = ltrim($folder . '/' . $name, '/');
        $target = $this->path($name);
        if (!move_uploaded_file($tmp, $target)) throw new \RuntimeException('Medienordner ist nicht beschreibbar.');
        chmod($target, 0644);
        return $name;
    }

    public function move(string $old, string $new): void
    {
        if ($old === $new || !self::validRelativePath($old) || !self::validRelativePath($new)) {
            throw new \RuntimeException('Ungültige Medienpfade.');
        }
        $source = $this->resolve($old)['path'];
        $target = $this->path($new);
        if (!is_dir(dirname($target))) throw new \RuntimeException('Zielordner existiert nicht.');
        if (file_exists($target) || is_link($target)) throw new \RuntimeException('Zieldatei existiert bereits.');
        if (!rename($source, $target)) throw new \RuntimeException('Medienverschiebung fehlgeschlagen.');
    }

    public function delete(string $name): void
    {
        $path = $this->path($name);
        if (!is_file($path) || !unlink($path)) throw new \RuntimeException('Datei konnte nicht gelöscht werden.');
    }

    /** @return array{path:string,mime:string} */
    public function resolve(string $name): array
    {
        $path = $this->path($name);
        if (!is_file($path)) throw new \RuntimeException('Datei nicht gefunden.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$mime]) || !in_array($ext, self::TYPES[$mime], true)) {
            throw new \RuntimeException('Nicht unterstützte Datei.');
        }
        return ['path'=>$path, 'mime'=>$mime];
    }
}
