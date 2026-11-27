<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

use League\CommonMark\MarkdownConverter;
use Symfony\Component\Yaml\Yaml;

final class Admin
{
    private MarkdownConverter $converter;

    public function __construct(private readonly string $contentDir, private readonly string $dataDir)
    {
        $this->converter = AlignedMarkdown::converter();
    }

    public static function slugIsValid(string $slug): bool
    {
        return (bool) preg_match('~\A[a-z0-9_-]+(?:/[a-z0-9_-]+)*\z~D', $slug)
            && strlen($slug) <= 180;
    }

    private function file(string $slug): string
    {
        if (!self::slugIsValid($slug)) {
            throw new \InvalidArgumentException('Ungültiger Seitenpfad. Erlaubt: a-z, 0-9, _ und - sowie Unterordner.');
        }
        $root = realpath($this->contentDir);
        if ($root === false) {
            throw new \RuntimeException('Inhaltsordner fehlt.');
        }
        $parts = explode('/', $slug);
        $cursor = $root;
        foreach (array_slice($parts, 0, -1) as $part) {
            $cursor .= '/' . $part;
            if (is_link($cursor)) {
                throw new \RuntimeException('Symbolische Links sind nicht erlaubt.');
            }
            if (file_exists($cursor) && !is_dir($cursor)) {
                throw new \RuntimeException('Ordnerpfad ist ungültig.');
            }
        }
        $target = $cursor . '/' . end($parts) . '.md';
        if (is_link($target)) {
            throw new \RuntimeException('Symbolische Links sind nicht erlaubt.');
        }
        return $target;
    }

    public function assertCreateAvailable(string $slug): void
    {
        if (file_exists($this->file($slug))) throw new \RuntimeException('Diese Seite existiert bereits.');
    }

    /** @return array{title:string,description:string,category:string,order:int,published:bool,nav:bool,body:string} */
    public function read(string $slug): array
    {
        $path = $this->file($slug);
        if (!is_file($path)) throw new \RuntimeException('Seite nicht gefunden.');
        $raw = file_get_contents($path);
        if ($raw === false) throw new \RuntimeException('Seite konnte nicht gelesen werden.');
        [$meta, $body] = PageMetadata::parse($raw);
        return [
            'id' => $meta['id'] ?? null,
            'title' => (string) ($meta['title'] ?? $slug),
            'description' => (string) ($meta['description'] ?? ''),
            'category' => str_contains($slug, '/') ? explode('/', $slug, 2)[0] : '',
            'order' => (int) ($meta['order'] ?? 100),
            'published' => (bool) ($meta['published'] ?? true),
            'nav' => (bool) ($meta['nav'] ?? true),
            'body' => DocumentBody::extractContent($body, (string) ($meta['title'] ?? $slug), (string) ($meta['description'] ?? '')),
            'date-created' => isset($meta['date-created']) ? (string) $meta['date-created'] : null,
            'date-modified' => isset($meta['date-modified']) ? (string) $meta['date-modified'] : null,
            'labels' => implode(', ', self::parseLabels($meta['labels'] ?? [])),
            'visibility' => in_array(($meta['visibility'] ?? 'all'), ['all', 'authenticated', 'roles', 'users'], true) ? ($meta['visibility'] ?? 'all') : 'roles',
            'allowed_roles' => is_array($meta['allowed_roles'] ?? null) ? array_values(array_unique(array_intersect(['reader', 'editor', 'admin'], array_map(static fn($role) => match ($role) { 'member' => 'editor', 'visitor' => 'reader', default => $role }, $meta['allowed_roles'])))) : [],
            'allowed_users' => is_array($meta['allowed_users'] ?? null) ? array_values(array_filter($meta['allowed_users'], static fn($id) => is_string($id) && (bool) preg_match('/\Au_[a-f0-9]{32}\z/D', $id))) : [],
        ];
    }

    /** @param array<string,mixed> $input */
    public function save(string $slug, array $input, bool $create, string $originalSlug = '', bool $contentOnly = false, string $expectedRevision = '', string $lockUser = '', string $lockToken = '', bool $editorMetadata = false): void
    {
        // Share the same lock as media deletion and relocation. Keep the entire
        // page mutation (including content-only reads) inside the critical section.
        (new MediaOperationLock($this->dataDir))->run(function () use ($slug, $input, $create, $originalSlug, $contentOnly, $expectedRevision, $lockUser, $lockToken, $editorMetadata): void {
            if (!$create) {
                $lease = new PageEditLease($this->dataDir);
                if ($editorMetadata) $lease->assertOwner($originalSlug, $lockUser, $lockToken);
                else $lease->assertAvailable($originalSlug, $lockUser, $lockToken);
                if ($expectedRevision !== '') {
                    $actual = $this->read($originalSlug);
                    $hash = $contentOnly && !$editorMetadata ? hash('sha256', $actual['body']) : hash('sha256', json_encode($actual, JSON_THROW_ON_ERROR));
                    if (!hash_equals($hash, $expectedRevision)) throw new \RuntimeException('Diese Seite wurde zwischenzeitlich geändert. Bitte Änderungen sichern und neu laden.');
                }
            }
            $this->saveUnlocked($slug, $input, $create, $originalSlug, $contentOnly, $editorMetadata);
        });
    }

    private function saveUnlocked(string $slug, array $input, bool $create, string $originalSlug = '', bool $contentOnly = false, bool $editorMetadata = false): void
    {
        if ($contentOnly) {
            if ($create || $slug !== $originalSlug) throw new \RuntimeException('Mitglieder dürfen Seiten weder erstellen noch verschieben.');
            $existing = $this->read($originalSlug);
            $auth = new JSONAuthenticator($this->dataDir);
            $repository = new PageRepository($this->contentDir);
            $page = $repository->findForAccess($originalSlug);
            if ($page === null || !($page->published ? PermissionService::canReadPage($page, $auth->currentRole(), $repository, $auth->currentUserId()) : PermissionService::canReadDraft($page, $auth->currentRole(), $repository, $auth->currentUserId()))) throw new \RuntimeException('Kein Zugriff auf diese Seite.');
            // Editorial save accepts only title, description, labels and body; never ACL or publication.
            $body = (string) ($input['body'] ?? '');
            $editorFields = $editorMetadata ? array_intersect_key($input, array_flip(['title', 'description', 'labels'])) : [];
            if ($editorMetadata && !PermissionService::canManagePage($page, $auth->currentRole(), $repository, $auth->currentUserId())) throw new \RuntimeException('Keine Bearbeitungsberechtigung.');
            $input = array_replace($existing, $editorFields);
            $input['body'] = $body;
        }
        $path = $this->file($slug);
        $originalPath = $create ? null : $this->file($originalSlug);
        if ($create) $this->assertCreateAvailable($slug);
        if (!$create && !is_file($originalPath)) throw new \RuntimeException('Die Seite existiert nicht mehr.');
        if (!$create && $path !== $originalPath && file_exists($path)) {
            throw new \RuntimeException('Am neuen Seitenpfad existiert bereits eine Seite.');
        }
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 160) throw new \RuntimeException('Titel muss 1 bis 160 Zeichen enthalten.');
        $description = trim((string) ($input['description'] ?? ''));
        $body = (string) ($input['body'] ?? '');
        $labels = self::parseLabels((string) ($input['labels'] ?? ''));
        if (mb_strlen($description) > 500 || strlen($body) > 1024 * 1024) {
            throw new \RuntimeException('Ein Feld überschreitet die erlaubte Länge.');
        }
        $order = filter_var($input['order'] ?? 100, FILTER_VALIDATE_INT);
        if ($order === false || $order < -99999 || $order > 99999) throw new \RuntimeException('Ungültige Sortierung.');
        $visibility = (string) ($input['visibility'] ?? 'all');
        if (!in_array($visibility, ['all', 'authenticated', 'roles', 'users'], true)) throw new \RuntimeException('Ungültige Sichtbarkeit.');
        $roles = $input['allowed_roles'] ?? [];
        if (!is_array($roles)) throw new \RuntimeException('Ungültige Rollenauswahl.');
        if (array_diff($roles, ['reader', 'editor', 'admin'])) throw new \RuntimeException('Ungültige Benutzerrolle.');
        $roles = array_values(array_unique(array_intersect(['reader', 'editor', 'admin'], $roles)));
        if ($visibility === 'roles' && $roles === []) throw new \RuntimeException('Mindestens eine Rolle auswählen.');
        $users = $input['allowed_users'] ?? [];
        if (!is_array($users)) throw new \RuntimeException('Ungültige Benutzerauswahl.');
        $users = array_values(array_unique($users));
        $auth = new JSONAuthenticator($this->dataDir);
        $validIds = array_column($auth->allUsers(), 'id');
        foreach ($users as $id) {
            if (!is_string($id) || !in_array($id, $validIds, true)) throw new \RuntimeException('Unbekannte Benutzer-ID.');
        }
        if ($visibility === 'users' && $users === []) throw new \RuntimeException('Mindestens einen Benutzer auswählen.');
        // Preserve the original creation date; for legacy pages use the file's
        // last modification time as a documented approximation.
        $originalCreated = null;
        if (!$create && $originalPath !== null) {
            $previous = $this->read($originalSlug);
            $originalCreated = $previous['date-created'] ?? null;
            if (!$originalCreated) $originalCreated = date(DATE_ATOM, (int) (filemtime($originalPath) ?: time()));
        }
        // IDs are server-owned. Preserve existing IDs on edits and moves.
        $ids = PageIdentity::inventory($this->contentDir);
        $pageId = $create ? PageIdentity::generate() : PageIdentity::readId($originalPath);
        if ($pageId === null) $pageId = PageIdentity::generate();
        if (isset($ids[$pageId])) {
            $expected = $create ? null : realpath($originalPath);
            $root = realpath($this->contentDir);
            if ($expected === null || $root === false || $ids[$pageId] !== substr($expected, strlen($root) + 1)) {
                throw new \RuntimeException('Doppelte Seiten-ID erkannt. Speichern abgebrochen.');
            }
        }
        $now = date(DATE_ATOM);
        $meta = [
            'id' => $pageId,
            'date-created' => $originalCreated ?: $now,
            'date-modified' => $now,
            'visibility' => $visibility,
            'allowed_roles' => $visibility === 'roles' ? $roles : [],
            'allowed_users' => $visibility === 'users' ? $users : [],
            'title' => $title,
            'description' => $description,
            'labels' => ($contentOnly && (!$editorMetadata || $input['labels'] === $existing['labels'])) ? $labels : (new TagRepository($this->dataDir . '/tags.json'))->assign($labels),
            'order' => $order,
            'published' => !empty($input['published']),
            'nav' => !empty($input['nav']),
        ];
        $parent = dirname($path);
        if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) throw new \RuntimeException('Ordner konnte nicht angelegt werden.');
        $text = "---\n" . Yaml::dump($meta, 2, 2) . "---\n\n" . DocumentBody::compose($title, $description, $body);
        $temp = tempnam($parent, '.homehub-');
        if ($temp === false) throw new \RuntimeException('Temporäre Datei konnte nicht erstellt werden.');
        try {
            if (file_put_contents($temp, $text, LOCK_EX) === false || !rename($temp, $path)) {
                throw new \RuntimeException('Speichern fehlgeschlagen. Dateiberechtigungen prüfen.');
            }
            chmod($path, 0664);
            if ($originalPath !== null && $originalPath !== $path && !unlink($originalPath)) {
                // Avoid leaving two copies if the old file could not be removed.
                unlink($path);
                throw new \RuntimeException('Alter Seitenpfad konnte nicht entfernt werden.');
            }
        } finally {
            if (file_exists($temp)) unlink($temp);
        }
    }

    /** @return list<string> */
    private static function parseLabels(mixed $input): array
    {
        $values = is_string($input) ? explode(',', $input) : $input;
        if (!is_array($values)) return [];
        $result = [];
        $seen = [];
        foreach ($values as $value) {
            if (!is_string($value)) continue;
            $label = trim($value);
            if ($label === '') continue;
            if (mb_strlen($label) > 40) throw new \RuntimeException('Ein Schlagwort darf höchstens 40 Zeichen enthalten.');
            $key = $label;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $result[] = $label;
            if (count($result) > 20) throw new \RuntimeException('Maximal 20 Schlagworte erlaubt.');
        }
        return $result;
    }

    /** Move a page to another folder/slug without overwriting existing pages. */
    public function move(string $oldSlug, string $newSlug, string $revision = ''): void
    {
        // Share the same lock as media deletion and relocation. Keep the entire
        // page mutation (including content-only reads) inside the critical section.
        (new MediaOperationLock($this->dataDir))->run(function () use ($oldSlug, $newSlug, $revision): void {
            (new PageEditLease($this->dataDir))->assertAvailable($oldSlug);
            $this->moveUnlocked($oldSlug, $newSlug, $revision);
        });
    }

    private function moveUnlocked(string $oldSlug, string $newSlug, string $revision): void
    {
        PageIdentity::inventory($this->contentDir);
        $old = $this->file($oldSlug);
        $new = $this->file($newSlug);
        if (!is_file($old)) throw new \RuntimeException('Ursprungsseite fehlt.');
        if ($revision !== '') {
            $current = hash('sha256', json_encode($this->read($oldSlug), JSON_THROW_ON_ERROR));
            if (!hash_equals($current, $revision)) {
                throw new \RuntimeException('Die Seite wurde zwischenzeitlich geändert. Bitte neu laden.');
            }
        }
        if ($oldSlug === $newSlug) return;
        if (file_exists($new)) throw new \RuntimeException('Zielseite existiert bereits.');
        $parent = dirname($new);
        if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) {
            throw new \RuntimeException('Zielordner konnte nicht erstellt werden.');
        }
        if (!rename($old, $new)) throw new \RuntimeException('Verschieben fehlgeschlagen.');
    }

    public function preview(string $markdown): string
    {
        if (strlen($markdown) > 1024 * 1024) throw new \RuntimeException('Markdown ist zu lang.');
        return AlignedMarkdown::render($markdown, $this->converter);
    }

}
