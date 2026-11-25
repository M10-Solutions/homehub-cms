<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

/** JSON-backed authentication and user management; compatible with the supplied username/password array. */
final class JSONAuthenticator
{
    public string $error = '';
    public string $success = '';
    private string $storage;
    private string $lockPath;
    private string $setupMarker;

    public function __construct(string $configDirectory)
    {
        $this->storage = rtrim($configDirectory, '/') . '/users.json';
        $this->lockPath = $this->storage . '.lock';
        $this->setupMarker = rtrim($configDirectory, '/') . '/.homehub-initialized';
        $this->migrateLegacyRole();
        // Existing installations are considered initialized even if created before web setup.
        if (!is_file($this->setupMarker) && $this->readRaw() !== []) $this->markInitialized();
    }

    /** One-time, locked migration of persisted member/visitor roles to editor/reader. */
    private function migrateLegacyRole(): void
    {
        if (!is_file($this->storage)) return;
        $users = $this->readRaw();
        if (!array_filter($users, static fn($u) => is_array($u) && in_array(($u['role'] ?? null), ['member', 'visitor'], true))) return;
        $this->mutate(static function (array &$users): void {
            foreach ($users as &$user) {
                if (is_array($user)) {
                    if (($user['role'] ?? null) === 'member') $user['role'] = 'editor';
                    if (($user['role'] ?? null) === 'visitor') $user['role'] = 'reader';
                }
            }
            unset($user);
        });
    }

    private function markInitialized(): void
    {
        if (is_file($this->setupMarker)) return;
        if (file_put_contents($this->setupMarker, "initialized\n", LOCK_EX) === false) {
            throw new \RuntimeException('Initialisierungsstatus konnte nicht gespeichert werden.');
        }
        chmod($this->setupMarker, 0600);
    }

    public function needsSetup(): bool
    {
        if (is_file($this->setupMarker)) return false;
        // A missing users file is allowed only on a genuinely uninitialized instance.
        return !is_file($this->storage);
    }

    private function readRaw(): array
    {
        if (!is_file($this->storage)) return [];
        $raw = file_get_contents($this->storage);
        if ($raw === false) throw new \RuntimeException('Benutzerdatei konnte nicht gelesen werden.');
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !array_is_list($data)) throw new \RuntimeException('Ungültige Benutzerdatei.');
        return $data;
    }

    private function read(): array
    {
        $data = $this->readRaw();
        // Read old user records without changing passwords or account IDs.
        foreach ($data as &$entry) {
            if (is_array($entry)) {
                if (($entry['role'] ?? null) === 'member') $entry['role'] = 'editor';
                if (($entry['role'] ?? null) === 'visitor') $entry['role'] = 'reader';
            }
        }
        unset($entry);
        return $data;
    }

    private function mutate(callable $callback): mixed
    {
        $lock = fopen($this->lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) throw new \RuntimeException('Benutzerspeicher nicht verfügbar.');
        try {
            $users = $this->readRaw();
            $result = $callback($users);
            $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
            $tmp = tempnam(dirname($this->storage), '.users-');
            if ($tmp === false) throw new \RuntimeException('Temporäre Datei konnte nicht erstellt werden.');
            try {
                chmod($tmp, 0600);
                if (file_put_contents($tmp, $json) === false || !rename($tmp, $this->storage)) throw new \RuntimeException('Benutzer konnten nicht gespeichert werden.');
            } finally { if (is_file($tmp)) unlink($tmp); }
            return $result;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private static function username(string $name): string
    {
        $name = trim($name);
        if (!preg_match('/\A[a-zA-Z0-9._-]{3,50}\z/D', $name)) throw new \InvalidArgumentException('Benutzername: 3–50 Zeichen (Buchstaben, Zahlen, Punkt, _ oder -).');
        return $name;
    }

    private static function role(string $role): string
    {
        if (!in_array($role, ['reader', 'editor', 'admin'], true)) throw new \InvalidArgumentException('Ungültige Benutzerrolle.');
        return $role;
    }

    /** Backfill stable IDs for legacy accounts; never changes password hashes. */
    public function ensureUserIds(): void
    {
        foreach ($this->read() as $user) {
            if (!isset($user['id']) || !is_string($user['id']) || !preg_match('/\Au_[a-f0-9]{32}\z/D', $user['id'])) {
                $this->mutate(static function (array &$users): void {
                    $seen = [];
                    foreach ($users as &$entry) {
                        $id = $entry['id'] ?? null;
                        if (!is_string($id) || !preg_match('/\Au_[a-f0-9]{32}\z/D', $id) || isset($seen[$id])) {
                            $id = 'u_' . bin2hex(random_bytes(16));
                            $entry['id'] = $id;
                        }
                        $seen[$id] = true;
                    }
                    unset($entry);
                });
                return;
            }
        }
    }

    public function currentUserId(): ?string
    {
        $name = $this->currentUser();
        if ($name === null) return null;
        $id = $this->user($name)['id'] ?? null;
        return is_string($id) && preg_match('/\Au_[a-f0-9]{32}\z/D', $id) ? $id : null;
    }

    public function allUsers(): array
    {
        $users = array_map(static fn(array $u): array => [
            'id' => (string) ($u['id'] ?? ''),
            'username' => (string) ($u['username'] ?? ''),
            'role' => (string) ($u['role'] ?? 'reader'),
            'first_name' => (string) ($u['first_name'] ?? ''),
            'last_name' => (string) ($u['last_name'] ?? ''),
            'email' => (string) ($u['email'] ?? ''),
            'active' => (bool) ($u['active'] ?? true),
        ], $this->read());
        usort($users, static fn($a, $b) => strcasecmp($a['username'], $b['username']));
        return $users;
    }

    public function user(string $username): ?array
    {
        foreach ($this->read() as $user) {
            if (($user['username'] ?? '') === $username) return $user;
        }
        return null;
    }

    public function login(string $username, string $password): bool
    {
        $this->error = 'Benutzername oder Passwort falsch.';
        $user = $this->user(trim($username));
        if (!$user || !($user['active'] ?? true) || !password_verify($password, (string) ($user['password'] ?? ''))) return false;
        session_regenerate_id(true);
        $_SESSION['user'] = $user['username'];
        $this->error = '';
        return true;
    }

    public function logout(): void { unset($_SESSION['user'], $_SESSION['admin_authenticated']); }
    public function isSigned(): bool { return $this->currentUser() !== null; }
    public function currentUser(): ?string
    {
        $name = $_SESSION['user'] ?? null;
        if (!is_string($name)) return null;
        $user = $this->user($name);
        return $user && ($user['active'] ?? true) ? $name : null;
    }
    public function currentRole(): ?string
    {
        $name = $this->currentUser();
        return $name === null ? null : (string) ($this->user($name)['role'] ?? 'reader');
    }

    private static function validateProfile(array $profile): array
    {
        $result = [];
        foreach (['first_name', 'last_name', 'email'] as $key) {
            if (!array_key_exists($key, $profile)) continue;
            if (!is_string($profile[$key])) throw new \InvalidArgumentException('Ungültige Profildaten.');
            $value = trim($profile[$key]);
            if (strlen($value) > ($key === 'email' ? 254 : 400) || preg_match('/[\x00-\x1f\x7f]/', $value)) throw new \InvalidArgumentException('Ungültige Profildaten.');
            if ($key === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Ungültige E-Mail-Adresse.');
            $result[$key] = $value;
        }
        return $result;
    }

    public function registerUser(string $username, string $password, string $role = 'reader', bool $active = true, array $profile = [], bool $initialOnly = false): bool
    {
        $username = self::username($username);
        self::role($role);
        $profile = self::validateProfile($profile);
        if (strlen($password) < 12) throw new \InvalidArgumentException('Passwort muss mindestens 12 Zeichen enthalten.');
        $marker = $this->setupMarker;
        $this->mutate(static function (array &$users) use ($username, $password, $role, $active, $profile, $initialOnly, $marker): void {
            if ($initialOnly && ($users !== [] || is_file($marker))) throw new \RuntimeException('Ersteinrichtung ist bereits abgeschlossen.');
            foreach ($users as $user) if (strcasecmp((string) ($user['username'] ?? ''), $username) === 0) throw new \RuntimeException('Benutzername bereits vergeben.');
            $users[] = ['id' => 'u_' . bin2hex(random_bytes(16)), 'username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role, 'active' => $active] + $profile;
        });
        if ($initialOnly) $this->markInitialized();
        $this->success = 'Benutzer angelegt.';
        return true;
    }

    public function updateUser(string $original, string $username, string $password, string $role, bool $active, array $profile = []): bool
    {
        $username = self::username($username);
        self::role($role);
        $profile = self::validateProfile($profile);
        if ($password !== '' && strlen($password) < 12) throw new \InvalidArgumentException('Neues Passwort muss mindestens 12 Zeichen enthalten.');
        $this->mutate(static function (array &$users) use ($original, $username, $password, $role, $active, $profile): void {
            $index = null;
            foreach ($users as $i => $user) {
                if (($user['username'] ?? '') === $original) $index = $i;
                if (($user['username'] ?? '') !== $original && strcasecmp((string) ($user['username'] ?? ''), $username) === 0) throw new \RuntimeException('Benutzername bereits vergeben.');
            }
            if ($index === null) throw new \RuntimeException('Benutzer nicht gefunden.');
            $wasAdmin = ($users[$index]['role'] ?? 'reader') === 'admin' && ($users[$index]['active'] ?? true);
            if ($wasAdmin && ($role !== 'admin' || !$active) && self::activeAdminCount($users) <= 1) throw new \RuntimeException('Der letzte aktive Administrator muss erhalten bleiben.');
            $users[$index]['username'] = $username;
            $users[$index]['role'] = $role;
            $users[$index]['active'] = $active;
            foreach ($profile as $key => $value) $users[$index][$key] = $value;
            if ($password !== '') $users[$index]['password'] = password_hash($password, PASSWORD_DEFAULT);
        });
        return true;
    }

    public function deleteUser(string $username): bool
    {
        $this->mutate(static function (array &$users) use ($username): void {
            foreach ($users as $i => $user) {
                if (($user['username'] ?? '') !== $username) continue;
                if (($user['role'] ?? 'reader') === 'admin' && ($user['active'] ?? true) && self::activeAdminCount($users) <= 1) throw new \RuntimeException('Der letzte aktive Administrator darf nicht gelöscht werden.');
                array_splice($users, $i, 1);
                return;
            }
            throw new \RuntimeException('Benutzer nicht gefunden.');
        });
        return true;
    }

    private static function activeAdminCount(array $users): int
    {
        return count(array_filter($users, static fn($u) => ($u['role'] ?? 'reader') === 'admin' && ($u['active'] ?? true)));
    }

}
