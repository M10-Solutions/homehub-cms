<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
namespace HomeHub;

/** Serializes destructive media operations across admin.php and media-api.php. */
final class MediaOperationLock
{
    public function __construct(private readonly string $configDirectory) {}

    public function run(callable $operation): mixed
    {
        if (!is_dir($this->configDirectory)) throw new \RuntimeException('Konfigurationsordner fehlt.');
        $handle = fopen(rtrim($this->configDirectory, '/') . '/media-operations.lock', 'c');
        if ($handle === false) throw new \RuntimeException('Mediensperre konnte nicht geöffnet werden.');
        try {
            if (!flock($handle, LOCK_EX)) throw new \RuntimeException('Medienoperation konnte nicht gesperrt werden.');
            try { return $operation(); }
            finally { flock($handle, LOCK_UN); }
        } finally { fclose($handle); }
    }
}
