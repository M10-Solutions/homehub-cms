<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
namespace HomeHub;

/** Independent per-file operations; failures do not undo successful items. */
final class MediaBatch
{
    public function __construct(private readonly MediaLibrary $media, private readonly MediaRegistry $registry, private readonly MediaOperationLock $lock) {}

    public function run(string $operation, string $folder, string $target, array $files): array
    {
        $folders = $this->media->folders();
        if (!in_array($operation, ['move', 'delete'], true) || !in_array($folder, $folders, true) || ($operation === 'move' && (!in_array($target, $folders, true) || $target === $folder))) throw new \RuntimeException('Ungültige Sammelaktion oder Ordner.');
        $allowed = array_column($this->media->all($folder), 'path');
        $mover = new MediaMover($this->media, $this->registry, $this->lock);
        $deleter = new MediaDeleter($this->media, $this->registry, $this->lock);
        $done = 0; $failures = [];
        foreach (array_unique(array_filter($files, 'is_string')) as $file) {
            try {
                if (!in_array($file, $allowed, true)) throw new \RuntimeException('Datei nicht im aktuellen Ordner.');
                if ($operation === 'move') $mover->move($file, $target, basename($file));
                else $deleter->delete($file);
                ++$done;
            } catch (\Throwable $e) { $failures[] = basename($file) . ': ' . $e->getMessage(); }
        }
        return [$done, $failures];
    }
}
