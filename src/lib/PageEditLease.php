<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
namespace HomeHub;

/** Short-lived editing leases. All methods must be called under MediaOperationLock. */
final class PageEditLease
{
    public const TTL = 120;
    public function __construct(private readonly string $directory) {}

    private function path(string $slug): string
    {
        if (!Admin::slugIsValid($slug)) throw new \RuntimeException('Ungültiger Seitenpfad.');
        return rtrim($this->directory, '/') . '/page-edit-' . hash('sha256', $slug) . '.json';
    }

    public function current(string $slug): ?array
    {
        $path = $this->path($slug);
        if (!is_file($path)) return null;
        $record = json_decode((string) file_get_contents($path), true);
        if (!is_array($record) || !isset($record['expires'], $record['token'], $record['user']) || $record['expires'] <= time()) return null;
        return $record;
    }

    public function acquire(string $slug, string $user): string
    {
        if ($user === '') throw new \RuntimeException('Anmeldung erforderlich.');
        if ($this->current($slug) !== null) throw new \RuntimeException('Diese Seite wird bereits bearbeitet. Bitte später erneut versuchen.');
        $token = bin2hex(random_bytes(32));
        $this->write($slug, ['token'=>$token, 'user'=>$user, 'expires'=>time() + self::TTL]);
        return $token;
    }

    public function assertOwner(string $slug, string $user, string $token): void
    {
        $record = $this->current($slug);
        if ($record === null || $user === '' || $token === '' || !hash_equals($record['user'], $user) || !hash_equals($record['token'], $token)) {
            throw new \RuntimeException('Bearbeitungssperre abgelaufen oder ungültig. Änderungen bitte sichern und Seite neu laden.');
        }
    }

    public function assertAvailable(string $slug, string $user = '', string $token = ''): void
    {
        if ($this->current($slug) !== null) {
            $this->assertOwner($slug, $user, $token);
        }
    }

    public function heartbeat(string $slug, string $user, string $token): void
    {
        $this->assertOwner($slug, $user, $token);
        $this->write($slug, ['token'=>$token, 'user'=>$user, 'expires'=>time() + self::TTL]);
    }

    public function release(string $slug, string $user, string $token): void
    {
        $this->assertOwner($slug, $user, $token);
        @unlink($this->path($slug));
    }

    private function write(string $slug, array $record): void
    {
        $path = $this->path($slug);
        $temp = tempnam($this->directory, 'lease-');
        if ($temp === false) throw new \RuntimeException('Sperre konnte nicht gespeichert werden.');
        try {
            if (file_put_contents($temp, json_encode($record, JSON_THROW_ON_ERROR)) === false || !rename($temp, $path)) throw new \RuntimeException('Sperre konnte nicht gespeichert werden.');
        } finally { if (is_file($temp)) @unlink($temp); }
    }
}
