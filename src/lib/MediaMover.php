<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
namespace HomeHub;

/** Moves registered media without scanning wiki pages; run MEDIA-004 migration first. */
final class MediaMover
{
    public function __construct(
        private readonly MediaLibrary $media,
        private readonly MediaRegistry $registry,
        private readonly MediaOperationLock $lock
    ) {}

    public function move(string $old, string $folder, string $filename): string
    {
        if (!MediaLibrary::validFolder($folder) || !MediaLibrary::validName($filename)) {
            throw new \RuntimeException('Ungültiger Zielordner oder Dateiname.');
        }
        if (strtolower(pathinfo($old, PATHINFO_EXTENSION)) !== strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            throw new \RuntimeException('Dateiendung darf beim Umbenennen nicht verändert werden.');
        }
        $new = ltrim($folder . '/' . $filename, '/');
        return $this->lock->run(function () use ($old, $new): string {
        $this->media->resolve($old);
        $id = $this->registry->idForPath($old);
        if ($id === null) throw new \RuntimeException('Datei ist noch nicht registriert. Bitte Medienliste neu laden.');
        return $this->registry->relocate($old, $new, $this->media);
        });
    }
}
