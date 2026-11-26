<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
namespace HomeHub;

/** File-based trash. All mutations share the existing page operation lock. */
final class PageTrash
{
    private string $root;
    public function __construct(private string $content, private string $config)
    {
        $this->root = dirname($config) . '/trash';
    }
    private function path(string $base, string $slug): string
    {
        if (!Admin::slugIsValid($slug) || is_link($base)) throw new \RuntimeException('Ungültiger Pfad.');
        $path = $base;
        foreach (explode('/', $slug . '.md') as $part) {
            $path .= '/' . $part;
            if (is_link($path)) throw new \RuntimeException('Symbolische Links sind nicht erlaubt.');
        }
        return $path;
    }
    private function directory(string $path): void
    {
        if (is_link($path) || (!is_dir($path) && !mkdir($path, 0770, true))) throw new \RuntimeException('Ordner konnte nicht erstellt werden.');
    }
    private function entry(string $id): string
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $id) || is_link($this->root)) throw new \RuntimeException('Ungültiger Papierkorbeintrag.');
        $path = $this->root . '/' . $id;
        if (is_link($path) || !is_dir($path)) throw new \RuntimeException('Papierkorbeintrag fehlt.');
        return $path;
    }
    private function metadata(string $id): array
    {
        $path = $this->entry($id) . '/metadata.json';
        if (is_link($path) || !is_file($path)) throw new \RuntimeException('Papierkorbmetadaten fehlen.');
        $m = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($m['pages'] ?? null) || !$m['pages']) throw new \RuntimeException('Ungültige Papierkorbmetadaten.');
        return $m;
    }
    public function entries(): array
    {
        if (!is_dir($this->root)) return [];
        if (is_link($this->root)) throw new \RuntimeException('Ungültiger Papierkorb.');
        $out = [];
        foreach (scandir($this->root) as $id) {
            if (preg_match('/\A[a-f0-9]{32}\z/D', $id)) $out[$id] = $this->metadata($id);
        }
        uasort($out, static fn($a,$b) => strcmp($b['date'], $a['date']));
        return $out;
    }
    public function remove(string $slug, bool $tree, string $actor, callable $authorize, ?callable $preflight = null): string
    {
        return (new MediaOperationLock($this->config))->run(function () use ($slug,$tree,$actor,$authorize,$preflight) {
            if ($preflight !== null) $preflight();
            $pages = [$slug];
            $base = $this->path($this->content, $slug);
            $branch = substr($base, 0, -3);
            if ($tree && is_link($branch)) throw new \RuntimeException('Ungültiger Teilbaum.');
            if ($tree && is_dir($branch)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($branch, \FilesystemIterator::SKIP_DOTS)) as $file) {
                    if ($file->isLink()) throw new \RuntimeException('Symbolische Links sind nicht erlaubt.');
                    if ($file->isFile() && strtolower($file->getExtension()) === 'md') $pages[] = substr($file->getPathname(), strlen($this->content)+1, -3);
                }
            }
            $lease = new PageEditLease($this->config);
            foreach ($pages as $page) {
                $authorize($page);
                $lease->assertAvailable($page);
                if (!is_file($this->path($this->content,$page))) throw new \RuntimeException('Seite fehlt.');
            }
            $this->directory($this->root);
            $id = bin2hex(random_bytes(16));
            $dir = $this->root . '/' . $id;
            $this->directory($dir);
            $meta = ['slug'=>$slug,'pages'=>$pages,'actor'=>$actor,'date'=>date(DATE_ATOM)];
            if (file_put_contents($dir.'/metadata.json',json_encode($meta, JSON_THROW_ON_ERROR)) === false) throw new \RuntimeException('Metadaten konnten nicht gespeichert werden.');
            try { $this->transfer($pages,$this->content,$dir.'/content'); } catch (\Throwable $e) {
                // Keep any remaining payload for manual recovery if rollback failed.
                if (array_reduce($pages, fn($ok,$p)=>$ok && is_file($this->path($this->content,$p)), true)) $this->purgeDirectory($dir);
                throw $e;
            }
            return $id;
        });
    }
    private function transfer(array $pages,string $from,string $to): void
    {
        $moves = [];
        foreach ($pages as $slug) {
            $a=$this->path($from,$slug); $b=$this->path($to,$slug);
            if (!is_file($a) || file_exists($b)) throw new \RuntimeException('Dateikonflikt.');
            $this->directory(dirname($b));
            $moves[]=[$a,$b];
        }
        $done=[];
        try {
            foreach ($moves as [$a,$b]) {
                if (!rename($a,$b)) throw new \RuntimeException('Verschieben fehlgeschlagen.');
                $done[]=[$a,$b];
            }
        } catch (\Throwable $e) {
            foreach (array_reverse($done) as [$a,$b]) if (!rename($b,$a)) throw new \RuntimeException('Rollback fehlgeschlagen; Backup prüfen.',0,$e);
            throw $e;
        }
    }
    public function restore(string $id): void
    {
        (new MediaOperationLock($this->config))->run(function () use ($id) {
            $m=$this->metadata($id); $dir=$this->entry($id);
            $active=PageIdentity::inventory($this->content);
            $seen=[];
            foreach ($m['pages'] as $slug) {
                $source=$this->path($dir.'/content',$slug);
                $target=$this->path($this->content,$slug);
                if (file_exists($target)) throw new \RuntimeException('Zielseite existiert bereits.');
                $parent=dirname($slug);
                if ($parent!=='.' && !in_array($parent,$m['pages'],true) && !is_file($this->path($this->content,$parent))) throw new \RuntimeException('Übergeordnetes Kapitel fehlt.');
                $pid=PageIdentity::readId($source);
                if ($pid!==null && (isset($active[$pid]) || isset($seen[$pid]))) throw new \RuntimeException('Seiten-ID existiert bereits.');
                if ($pid!==null) $seen[$pid]=true;
                (new PageEditLease($this->config))->assertAvailable($slug);
            }
            $this->transfer($m['pages'],$dir.'/content',$this->content);
            $this->purgeDirectory($dir);
        });
    }
    private function purgeDirectory(string $dir): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $ok=$file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            if (!$ok) throw new \RuntimeException('Papierkorbeintrag konnte nicht vollständig entfernt werden.');
        }
        if (!rmdir($dir)) throw new \RuntimeException('Papierkorbeintrag konnte nicht entfernt werden.');
    }
    public function purge(string $id): void
    {
        (new MediaOperationLock($this->config))->run(fn()=> $this->purgeDirectory($this->entry($id)));
    }
    public function empty(): void
    {
        (new MediaOperationLock($this->config))->run(function () { foreach (array_keys($this->entries()) as $id) $this->purgeDirectory($this->entry($id)); });
    }
}
