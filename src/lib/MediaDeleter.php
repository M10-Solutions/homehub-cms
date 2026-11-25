<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
namespace HomeHub;

/** Deletes media under the cross-endpoint operation lock; links may break. */
final class MediaDeleter
{
    public function __construct(
        private readonly MediaLibrary $media,
        private readonly MediaRegistry $registry,
        private readonly MediaOperationLock $lock
    ) {}

    public function delete(string $path): void
    {
        $this->lock->run(function () use ($path): void {
            $this->media->resolve($path);
            $this->media->delete($path);
            $this->registry->unregister($path);
        });
    }
}
