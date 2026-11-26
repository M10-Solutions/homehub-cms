<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\PageRepository;
use HomeHub\PageSearch;
use HomeHub\PermissionService;
use HomeHub\JSONAuthenticator;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = require __DIR__ . '/config/app.php';
date_default_timezone_set($config['timezone']);

$pages = new PageRepository($config['content_path']);
Session::start();
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
if ($auth->needsSetup()) {
    header('Location: /setup.php', true, 303);
    exit;
}
$loginError = '';
$loginModalOpen = false;
$authToken = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array((string) ($_POST['auth_action'] ?? ''), ['login', 'logout'], true)) {
    $loginModalOpen = (string) $_POST['auth_action'] === 'login';
    $postedToken = (string) ($_POST['csrf'] ?? '');
    $authRedirect = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (in_array($authRedirect, ['/', '/index.php'], true) && isset($_GET['q']) && is_string($_GET['q'])) {
        $authRedirect = '/?q=' . rawurlencode(PageSearch::query($_GET['q']));
    }
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $expectedOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
    if (!hash_equals($authToken, $postedToken) || ($origin !== '' && !hash_equals($expectedOrigin, $origin))) {
        http_response_code(403);
        $loginError = 'Sicherheitsprüfung fehlgeschlagen. Bitte die Seite neu laden.';
    } elseif ($_POST['auth_action'] === 'login') {
        $last = (int) ($_SESSION['last_login_attempt'] ?? 0);
        if (time() - $last < 2) {
            $loginError = 'Bitte kurz warten und erneut versuchen.';
        } else {
            $_SESSION['last_login_attempt'] = time();
            if ($auth->login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''))) {
                header('Location: ' . $authRedirect, true, 303);
                exit;
            }
            sleep(1);
            $loginError = 'Benutzername oder Passwort falsch.';
        }
    } else {
        if ($auth->isSigned()) {
            $auth->logout();
            $_SESSION = [];
            session_regenerate_id(true);
        }
        header('Location: ' . $authRedirect, true, 303);
        exit;
    }
}
$role = $auth->currentRole();
$userId = $auth->currentUserId();
$allPages = array_values(array_filter($pages->all(), static fn($page) => PermissionService::canReadPage($page, $role, $pages, $userId)));
// Drafts are visible by default to admins and ACL-authorised editors.
$showDrafts = PermissionService::can($role, 'page.read_draft');
$draftSlug = $showDrafts && isset($_GET['draft']) && is_string($_GET['draft']) ? $_GET['draft'] : '';
$draftPage = $draftSlug !== '' ? $pages->findForAccess($draftSlug) : null;
if ($draftPage !== null && !PermissionService::canReadDraft($draftPage, $role, $pages, $userId)) $draftPage = null;

$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$slug = trim(rawurldecode($requestedPath), '/');
if ($slug === '') {
    $slug = $config['home_page'];
}

$searchActive = array_key_exists('q', $_GET) && in_array($requestedPath, ['/', '/index.php'], true);
$searchQuery = $searchActive ? PageSearch::query($_GET['q']) : '';
$searchResults = $searchActive ? PageSearch::find($allPages, $searchQuery) : [];
$currentPage = $searchActive ? null : ($draftPage ?? $pages->find($slug));
$isDraftView = $draftPage !== null && !$searchActive;
if ($isDraftView) {
    $slug = $draftPage->slug;
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex');
}
$accessDenied = $currentPage !== null && !($isDraftView ? PermissionService::canReadDraft($currentPage, $role, $pages, $userId) : PermissionService::canReadPage($currentPage, $role, $pages, $userId));
if ($accessDenied) {
    http_response_code($role === null ? 401 : 403);
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex');
}
$loginModalOpen = $loginModalOpen || ($accessDenied && $role === null);
$notFound = $currentPage === null || $accessDenied;
// Own and inherited visibility restrictions both count as protected.
$hasRestrictedVisibility = !$notFound && (
    $currentPage->visibility !== 'all'
    || PermissionService::inheritedPolicies($currentPage->slug, $pages) !== []
);

$inlineAllowed = !$notFound && PermissionService::can($role, 'page.edit');
if ($inlineAllowed && $role !== 'admin') {
    $inlineAllowed = ($isDraftView ? PermissionService::canReadDraft($currentPage, $role, $pages, $userId) : PermissionService::canReadPage($currentPage, $role, $pages, $userId));
}
$inlineToken = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$inlineRevision = $inlineAllowed ? hash('sha256', $currentPage->content) : '';
$canManagePages = PermissionService::can($role, 'page.create');
$manageAdmin = $canManagePages ? new \HomeHub\Admin($config['content_path'], dirname(__DIR__) . '/data/config') : null;
$canManageCurrent = $canManagePages && $currentPage !== null && PermissionService::canManagePage($currentPage, $role, $pages, $userId);
$managePage = $canManageCurrent ? $manageAdmin->read($currentPage->slug) : null;
$manageRevision = $managePage !== null ? hash('sha256', json_encode($managePage, JSON_THROW_ON_ERROR)) : '';
$knownTags = [];
$tagWarning = '';
if ($managePage !== null) {
    try {
        $tagRepo = new \HomeHub\TagRepository(dirname(__DIR__) . '/data/config/tags.json');
        $knownTags = $tagRepo->all();
    } catch (\Throwable $error) {
        $tagWarning = 'Schlagwortkatalog nicht verfügbar.';
    }
    header('Cache-Control: no-store, private');
}
$manageUsers = $role === 'admin' ? $auth->allUsers() : [];
$moveFolders = $canManagePages ? \HomeHub\PageFolders::all($config['content_path']) : [];


if ($searchActive) {
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex');
    $pageTitle = 'Suchergebnisse';
    $description = '';
    $htmlContent = '';
} elseif ($notFound) {
    if (!$accessDenied) http_response_code(404);
    $pageTitle = $accessDenied ? 'Zugriff geschützt' : 'Seite nicht gefunden';
    $description = $accessDenied ? 'Für diese Seite ist eine Berechtigung erforderlich.' : 'Diese Seite existiert nicht oder ist nicht veröffentlicht.';
    $htmlContent = $accessDenied
        ? ($role === null ? '<h1>Anmeldung erforderlich</h1><p>Bitte oben rechts auf „Anmelden“ klicken, um diese Seite zu lesen.</p>' : '<h1>Kein Zugriff</h1><p>Deine Benutzerrolle darf diese Seite nicht lesen.</p>')
        : '<h1>Seite nicht gefunden</h1><p>Die gewünschte Seite wurde nicht gefunden.</p><p><a href="/">Zur Startseite</a></p>';
} else {
    $pageTitle = $currentPage->title;
    $description = $currentPage->description;
    $htmlContent = ($isDraftView ? '<p class="hub-draft-banner">Draft – unveröffentlicht. Nur für berechtigte Redakteure sichtbar.</p>' : '') . $pages->render($currentPage);
}

$navigationPages = array_values(array_filter(
    $showDrafts ? $pages->allIncludingDrafts() : $allPages,
    static fn ($page): bool => ($page->showInNavigation || ($showDrafts && !$page->published))
        && ($page->published ? PermissionService::canReadPage($page, $role, $pages, $userId)
            : PermissionService::canReadDraft($page, $role, $pages, $userId))
));
$navItems = array_map(static fn ($page): array => [
    'slug' => $page->slug,
    'title' => $page->title,
    'order' => $page->order,
    'draft' => !$page->published,
    'hidden_in_navigation' => !$page->showInNavigation,
], $navigationPages);
$navigationTree = \HomeHub\NavigationTree::build($navItems);
$createFolder = !$notFound ? ((is_dir($config['content_path'] . '/' . $currentPage->slug) || array_filter($navigationPages, static fn ($p) => str_starts_with($p->slug, $currentPage->slug . '/'))) ? $currentPage->slug : (str_contains($currentPage->slug, '/') ? dirname($currentPage->slug) : '')) : '';
$createPrefix = $createFolder === '' ? '' : $createFolder . '/';


/** Build linked breadcrumbs from the physical Markdown path. */
function publicBreadcrumbs(string $slug, array $allPages, string $homeSlug): array
{
    $lookup = [];
    foreach ($allPages as $item) $lookup[$item->slug] = $item->title;
    $parts = explode('/', trim($slug, '/'));
    $crumbs = [['label' => 'Startseite', 'href' => '/']];
    $path = '';
    foreach ($parts as $i => $part) {
        $path = $path === '' ? $part : $path . '/' . $part;
        if ($i === count($parts) - 1 && $path === $homeSlug) continue;
        $target = isset($lookup[$path]) ? $path : null;
        if ($target === null) {
            foreach ($allPages as $candidate) {
                if (str_starts_with($candidate->slug, $path . '/')) {
                    $target = $candidate->slug;
                    break;
                }
            }
        }
        $crumbs[] = [
            'label' => $lookup[$path] ?? str_replace(['-', '_'], ' ', $part),
            'href' => $i === count($parts) - 1 ? null : ($target === null ? null : '/' . implode('/', array_map('rawurlencode', explode('/', $target)))),
        ];
    }
    return $crumbs;
}

function publicTreeHasActive(array $node, ?string $activeSlug): bool
{
    if ($activeSlug === null) return false;
    if (($node['page']['slug'] ?? null) === $activeSlug) return true;
    foreach ($node['children'] as $child) {
        if (publicTreeHasActive($child, $activeSlug)) return true;
    }
    return false;
}

function renderPublicTree(array $node, ?string $activeSlug, bool $canManage = false, int $depth = 0, bool $showDrafts = false): void
{
    echo '<div class="hub-nav-level">';
    foreach ($node['children'] as $child) {
        $page = $child['page'];
        $hasChildren = !empty($child['children']);
        $isCategory = $depth === 0 && $hasChildren;
        $label = $page !== null ? (string) $page['title'] : (string) $child['segment'];
        $active = $page !== null && $activeSlug === $page['slug'];
        $pathActive = publicTreeHasActive($child, $activeSlug);
        $isDraft = (bool) ($page['draft'] ?? false);
        $isHiddenDraft = $isDraft && (bool) ($page['hidden_in_navigation'] ?? false);
        $draftMark = $isDraft ? ' <span class="hub-draft-label">Draft</span>' . ($isHiddenDraft ? ' <span class="hub-draft-hidden" title="Nach Veröffentlichung nicht in der Navigation" aria-label="Nach Veröffentlichung nicht in der Navigation"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></span>' : '') : '';
        $pageHref = $isDraft ? '/?draft=' . rawurlencode((string) $page['slug']) . '' : '/' . e((string) ($page['slug'] ?? '')) . '';
        $class = $isCategory ? ' hub-nav-category' : ($hasChildren ? ' hub-nav-folder' : ' hub-nav-page');
        echo '<div class="hub-nav-node' . $class . ($isDraft ? ' hub-nav-is-draft' : '') . '" data-nav-slug="' . e((string) ($page['slug'] ?? '')) . '">';
        if ($hasChildren) {
            echo '<details class="hub-nav-branch"' . ($pathActive ? ' open' : '') . '><summary class="hub-nav-row hub-nav-summary" aria-label="' . e($label) . ' auf- oder zuklappen"><span class="hub-nav-toggle" aria-hidden="true"><span class="hub-nav-chevron"></span></span>';
            echo '<span class="hub-nav-icon hub-nav-folder-icon" aria-hidden="true"></span>';
            if ($page !== null) {
                echo '<a class="hub-nav-title' . ($active ? ' active' : '') . '"' . ($active ? ' aria-current="page"' : '') . ' href="' . $pageHref . '" onclick="event.stopPropagation()">' . e($label) . $draftMark . '</a>';
            } else {
                echo '<span class="hub-nav-title hub-nav-plain">' . e($label) . '</span>';
            }
            echo '</summary><div class="hub-nav-children">';
            renderPublicTree($child, $activeSlug, $canManage, $depth + 1, $showDrafts);
            echo '</div></details>';
        } else {
            echo '<div class="hub-nav-row hub-nav-leaf">';
            echo '<span class="hub-nav-icon hub-nav-file-icon" aria-hidden="true"></span>';
            if ($page !== null) {
                echo '<a class="hub-nav-title' . ($active ? ' active' : '') . '"' . ($active ? ' aria-current="page"' : '') . ' href="' . $pageHref . '">' . e($label) . $draftMark . '</a>';
            } else {
                echo '<span class="hub-nav-title hub-nav-plain">' . e($label) . '</span>';
            }
            echo '</div>';
        }
        if ($canManage) {
            echo '<button type="button" class="hub-nav-menu-trigger" aria-label="Aktionen für ' . e($label) . '" title="Seitenaktionen" data-nav-menu-trigger data-slug="' . e((string) ($page['slug'] ?? '')) . '" data-label="' . e($label) . '"><i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i></button>';
        }
        echo '</div>';
    }
    echo '</div>';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($description) ?>">
    <title><?= e($pageTitle) ?> · <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/css/text-alignment.css?v=1">
    <link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css?v=6.7.2">
    <link rel="stylesheet" href="/css/app.css?v=3&amp;theme=1">
    <link rel="stylesheet" href="/css/labels.css?v=1&amp;theme=1">
    <link rel="stylesheet" href="/css/layout.css?v=3&amp;theme=1"><link rel="stylesheet" href="/css/navigation.css?v=3&amp;theme=1">
    <link rel="stylesheet" href="/css/code-blocks.css?v=1">
    <link rel="stylesheet" href="/css/page-export.css?v=1&amp;theme=1">
    <link rel="stylesheet" href="/css/inline-editor.css?v=6&amp;theme=1">
    <link rel="stylesheet" href="/css/media-picker.css?v=2&amp;theme=1">
    <?php if ($inlineAllowed): ?><link rel="stylesheet" href="/css/page-picker.css?v=1&amp;theme=1"><?php endif; ?>
    <link rel="stylesheet" href="/css/login-modal.css?v=2&amp;theme=1">
    <link rel="stylesheet" href="/css/page-metadata.css?v=1&amp;theme=1">
    <link rel="stylesheet" href="/css/page-actions.css?v=7&amp;theme=1">
    <link rel="stylesheet" href="/css/header-create.css?v=1">
    <link rel="stylesheet" href="/css/wiki-navigation-ux.css?v=3&amp;theme=1">
    <link rel="stylesheet" href="/css/sidebar-account.css?v=3&amp;theme=1">
    <link rel="stylesheet" href="/css/page-protection.css?v=1&amp;theme=1">
    <link rel="stylesheet" href="/css/chapter-export.css?v=2&amp;theme=1">
<link rel="stylesheet" href="/css/fontawesome-ui.css?v=2">
    <script src="/js/prism-homehub.min.js?v=1.30.0" defer></script>
    <script src="/js/code-blocks.js?v=1" defer></script>
    <script src="/js/markdown-toolbar.js?v=3" defer></script>
    <script src="/js/inline-editor.js?v=6" defer></script>
    <?php if ($inlineAllowed): ?><script src="/js/page-picker.js?v=4" defer></script><?php endif; ?>
    <script src="/js/login-modal.js?v=1" defer></script>
    <script src="/js/page-actions.js?v=7" defer></script>
    <script src="/js/wiki-navigation-ux.js?v=4" defer></script>
    <script src="/js/chapter-export.js?v=2" defer></script>
<link rel="stylesheet" href="/css/dialog-header.css?v=1">
<link rel="stylesheet" href="/css/admin-buttons.css?v=4&amp;theme=1">
<link rel="stylesheet" href="/css/wiki-search.css?v=1">
<link rel="stylesheet" href="/css/theme.css?v=1"><link rel="stylesheet" href="/css/hub-feedback.css?v=2">
<link rel="stylesheet" href="/css/responsive-navigation.css?v=1">
<script src="/js/responsive-navigation.js?v=1" defer></script>
<script src="/js/hub-feedback.js?v=3" defer></script><script src="/js/page-link-feedback.js?v=1" defer></script><script src="/js/theme.js?v=1"></script>
</head>
<body>
<?php if (isset($_SESSION['hub_page_deleted_notice'])): ?>
<div class="alert success" data-hub-notice="delete"><?= e((string) $_SESSION['hub_page_deleted_notice']) ?></div>
<?php unset($_SESSION['hub_page_deleted_notice']); endif; ?>
<div class="app-shell">
    <header class="hub-header">
        <button type="button" class="hub-mobile-nav-toggle" aria-controls="hub-wiki-sidebar" aria-expanded="false" aria-label="Navigation öffnen" title="Navigation öffnen" hidden><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        <a class="hub-brand" href="/" aria-label="HomeHub Startseite">
            <span class="hub-brand-mark">H</span>
            <span class="hub-brand-copy"><strong><?= e($config['name']) ?></strong><small><?= e($config['tagline']) ?></small></span>
        </a>
        <nav class="hub-header-context hub-breadcrumbs" aria-label="Brotkrumennavigation">
            <?php foreach (($searchActive ? [['label' => 'Startseite', 'href' => '/'], ['label' => 'Suche', 'href' => null]] : ($notFound ? [['label' => 'Startseite', 'href' => '/'], ['label' => 'Seite nicht gefunden', 'href' => null]] : publicBreadcrumbs($slug, $allPages, $config['home_page']))) as $i => $crumb): ?>
                <?php if ($i > 0): ?><span class="hub-crumb-separator" aria-hidden="true">›</span><?php endif; ?>
                <?php if ($crumb['href'] !== null): ?><a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a>
                <?php else: ?><span aria-current="page"><?= e($crumb['label']) ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <form method="get" action="/" class="hub-wiki-search" role="search" aria-label="Wiki durchsuchen">
            <input type="search" name="q" aria-label="Text oder Schlagwort suchen" placeholder="Wiki durchsuchen …" maxlength="<?= PageSearch::MAX_QUERY ?>" value="<?= e($searchQuery) ?>" required>
            <button type="submit" title="Suchen" aria-label="Suchen"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
        </form>
        <div class="hub-header-actions"><?php require __DIR__ . '/theme-toggle-view.php'; ?>
            <?php if ($canManagePages): ?>
                <button type="button" class="hub-header-create" data-open-page-dialog="hub-page-create" aria-haspopup="dialog" aria-controls="hub-page-create" aria-label="Neue Seite erstellen" title="Neue Seite erstellen"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Neue Seite</span></button>
            <?php endif; ?>
            <?php if ($role === null): ?>
                <button type="button" class="hub-auth-button" id="hub-login-open" aria-haspopup="dialog" aria-controls="hub-login-dialog" title="Anmelden" aria-label="Anmelden"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></button>
            <?php else: ?>
                <?php if ($role === 'admin' || $role === 'editor'): ?><a class="hub-header-link" href="/admin.php" title="Verwaltung" aria-label="Verwaltung"><i class="fa-solid fa-gear" aria-hidden="true"></i></a><?php endif; ?>
                <form method="post" class="hub-logout-form">
                    <input type="hidden" name="csrf" value="<?= e($authToken) ?>">
                    <input type="hidden" name="auth_action" value="logout">
                    <button type="submit" class="hub-auth-button" title="Abmelden" aria-label="Abmelden"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></button>
                </form>
            <?php endif; ?>
        </div>
    </header>
    <button type="button" id="hub-nav-toggle" class="hub-nav-edge-toggle" aria-controls="hub-wiki-sidebar" aria-expanded="true" aria-label="Navigation einklappen" title="Navigation einklappen"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
    <div class="hub-mobile-nav-backdrop" hidden></div>
    <aside class="sidebar" id="hub-wiki-sidebar">
        <div class="hub-nav-search-wrap"><label for="hub-nav-search" class="hub-nav-search-label">Seiten durchsuchen</label><div class="hub-nav-search-field"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="hub-nav-search" type="search" placeholder="Seite suchen …" autocomplete="off" aria-controls="hub-wiki-tree"><button type="button" id="hub-nav-search-clear" title="Suche leeren" aria-label="Suche leeren" hidden><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div><span id="hub-nav-search-status" role="status" class="hub-nav-search-status"></span></div>
        <nav id="hub-wiki-tree" aria-label="Hauptnavigation">
            <?php renderPublicTree($navigationTree, $currentPage?->slug, $role === 'admin', 0, $showDrafts); ?>
        </nav>

        <?php if ($role !== null): ?>
            <?php require __DIR__ . '/sidebar-account-view.php'; ?>
        <?php else: ?>
            <div class="sidebar-footer">Dein privates Home-Intranet</div>
        <?php endif; ?>
        <div id="hub-nav-resize" class="hub-nav-resize" role="separator" aria-label="Navigationsbreite ändern" aria-orientation="vertical" tabindex="0" aria-valuemin="200" aria-valuemax="500" aria-valuenow="268"></div>
    </aside>

    <?php if ($canManagePages): ?><div id="hub-nav-context-menu" class="hub-nav-context-menu" role="menu" hidden>
        <button type="button" role="menuitem" data-nav-action="create"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Neue Unterseite</button>
        <button type="button" role="menuitem" data-nav-action="settings"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Einstellungen</button>
        <button type="button" role="menuitem" data-nav-action="move"><i class="fa-solid fa-folder-tree" aria-hidden="true"></i> Verschieben</button>
        <button type="button" role="menuitem" data-nav-action="delete"><i class="fa-solid fa-trash" aria-hidden="true"></i> Löschen</button>
    </div><?php endif; ?>
    <main class="main-area">
        <article class="content">
            <div class="page-heading">
                <div>
                    <div class="eyebrow">HOMEHUB · WISSENSBASIS</div>
                    <div class="hub-page-title-row">
                        <h1><?= e($pageTitle) ?></h1>
                        <?php if ($inlineAllowed): ?><span class="inline-edit-pill" aria-label="Bearbeitungsmodus" hidden><i class="fa-solid fa-pen" aria-hidden="true"></i> Bearbeiten</span><?php endif; ?>
                        <?php if ($hasRestrictedVisibility): ?>
                            <span class="hub-page-protected" role="img" aria-label="Zugriff eingeschränkt" title="Zugriff eingeschränkt">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($description !== ''): ?>
                        <p class="page-description"><?= e($description) ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!$notFound): ?>
                <div class="page-heading-actions" aria-label="Seitenaktionen">
                    <div class="page-heading-read-actions" id="page-heading-read-actions">
                        <?php if ($canManagePages): ?>
                        <span class="hub-page-management">
                            <button type="button" class="hub-icon-button" data-open-page-dialog="hub-page-settings" title="Seiteneinstellungen" aria-label="Seiteneinstellungen"><i class="fa-solid fa-sliders" aria-hidden="true"></i></button>
                            <button type="button" class="hub-icon-button" data-open-page-dialog="hub-page-move" title="Seite verschieben" aria-label="Seite verschieben"><i class="fa-solid fa-folder-tree" aria-hidden="true"></i></button>
                            <button type="button" class="hub-icon-button hub-action-btn danger" data-open-page-dialog="hub-page-delete" title="Seite löschen" aria-label="Seite löschen"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                        </span>
                        <?php endif; ?>
                        <?php if ($inlineAllowed): ?>
                        <button type="button" id="inline-edit-toggle" class="inline-primary hub-icon-button" title="Seite bearbeiten" aria-label="Seite bearbeiten"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                        <?php endif; ?>
                        <?php if (!$isDraftView): ?><a href="/export.php?format=pdf&amp;page=<?= rawurlencode($currentPage->slug) ?>" title="Als PDF herunterladen" download>
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> PDF
                        </a>
                        <a href="/export.php?format=odt&amp;page=<?= rawurlencode($currentPage->slug) ?>" title="Als ODT herunterladen" download>
                            <i class="fa-solid fa-file-word" aria-hidden="true"></i> ODT
                        </a>
                        <button type="button" class="hub-chapter-export-trigger" id="hub-chapter-export-open" title="Kapitelstruktur exportieren" aria-label="Kapitelstruktur exportieren"><i class="fa-solid fa-file-export" aria-hidden="true"></i> Export</button><?php endif; ?>
                    </div>

                </div>
                <?php endif; ?>
            </div>
            <?php if ($inlineAllowed): ?>
            <?php endif; ?>
            <?php if ($canManagePages && $notFound && !$accessDenied): ?>
            <p><button type="button" data-open-page-dialog="hub-page-create"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Neue Wiki-Seite erstellen</button></p>
            <?php endif; ?>
            <?php if ($inlineAllowed): ?>
            <section id="inline-workspace" class="inline-workspace" hidden aria-label="Markdown bearbeiten">
                <div class="inline-toolbar" role="toolbar" aria-label="Markdown-Werkzeuge">
<button type="button" data-inline-md="heading1" title="Überschrift 1" aria-label="Überschrift 1"><i class="fa-solid fa-heading" aria-hidden="true"></i></button>
<button type="button" data-inline-md="heading2" title="Überschrift 2" aria-label="Überschrift 2"><i class="fa-solid fa-heading" aria-hidden="true"></i></button>
<button type="button" data-inline-md="heading3" title="Überschrift 3" aria-label="Überschrift 3"><i class="fa-solid fa-heading" aria-hidden="true"></i></button>
<button type="button" data-inline-md="bold" title="Fett" aria-label="Fett"><i class="fa-solid fa-bold" aria-hidden="true"></i></button>
<button type="button" data-inline-md="italic" title="Kursiv" aria-label="Kursiv"><i class="fa-solid fa-italic" aria-hidden="true"></i></button>
<button type="button" data-inline-md="strike" title="Durchgestrichen" aria-label="Durchgestrichen"><i class="fa-solid fa-strikethrough" aria-hidden="true"></i></button>
<button type="button" data-inline-md="link" title="Link" aria-label="Link"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
<button type="button" data-page-picker data-page-target="inline-body" title="Wiki-Seite verlinken" aria-label="Wiki-Seite verlinken"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i></button>
<button type="button" data-media-picker data-media-target="inline-body" title="Bild aus Medienbibliothek" aria-label="Bild aus Medienbibliothek"><i class="fa-solid fa-images" aria-hidden="true"></i></button>
<button type="button" data-inline-md="image" title="Bild" aria-label="Bild"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
<button type="button" data-inline-md="unordered" title="Aufzählung" aria-label="Aufzählung"><i class="fa-solid fa-list-ul" aria-hidden="true"></i></button>
<button type="button" data-inline-md="ordered" title="Nummerierte Liste" aria-label="Nummerierte Liste"><i class="fa-solid fa-list-ol" aria-hidden="true"></i></button>
<button type="button" data-inline-md="task" title="Aufgabenliste" aria-label="Aufgabenliste"><i class="fa-solid fa-list-check" aria-hidden="true"></i></button>
<button type="button" data-inline-md="quote" title="Zitat" aria-label="Zitat"><i class="fa-solid fa-quote-left" aria-hidden="true"></i></button>
<button type="button" data-inline-md="inlinecode" title="Inline-Code" aria-label="Inline-Code"><i class="fa-solid fa-code" aria-hidden="true"></i></button>
<button type="button" data-inline-md="codeblock" title="Codeblock" aria-label="Codeblock"><i class="fa-solid fa-file-code" aria-hidden="true"></i></button>
<button type="button" data-inline-md="table" title="Tabelle" aria-label="Tabelle"><i class="fa-solid fa-table" aria-hidden="true"></i></button>
<button type="button" data-inline-md="rule" title="Trennlinie" aria-label="Trennlinie"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
<button type="button" data-inline-md="align-left" title="Linksbündig" aria-label="Linksbündig"><i class="fa-solid fa-align-left" aria-hidden="true"></i></button>
<button type="button" data-inline-md="align-center" title="Zentriert" aria-label="Zentriert"><i class="fa-solid fa-align-center" aria-hidden="true"></i></button>
<button type="button" data-inline-md="align-right" title="Rechtsbündig" aria-label="Rechtsbündig"><i class="fa-solid fa-align-right" aria-hidden="true"></i></button>
<button type="button" data-inline-md="align-justify" title="Blocksatz" aria-label="Blocksatz"><i class="fa-solid fa-align-justify" aria-hidden="true"></i></button>
                </div>
                <div id="inline-panels" class="inline-panels" data-view="edit">
                    <label class="inline-edit-pane" for="inline-body"><span class="inline-pane-header"><span class="inline-pane-label">Markdown</span><span id="inline-status" role="status" aria-live="polite"></span></span><textarea id="inline-body" spellcheck="false" maxlength="1048576"><?= e($currentPage->content) ?></textarea></label>
                    <div class="inline-preview-pane"><span class="inline-pane-label">Vorschau</span><div id="inline-preview" class="markdown-content" aria-live="polite"></div></div>
                </div>
                <div class="inline-bottom" aria-label="Bearbeitungsaktionen">
                    <div class="inline-tabs" role="group" aria-label="Editoransicht">
                    <button type="button" data-inline-view="edit" aria-pressed="true" title="Bearbeiten" aria-label="Bearbeiten"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
                    <button type="button" data-inline-view="preview" aria-pressed="false" title="Vorschau" aria-label="Vorschau"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                    <button type="button" data-inline-view="split" aria-pressed="false" title="Geteilt" aria-label="Geteilt"><i class="fa-solid fa-table-columns" aria-hidden="true"></i></button>
                </div>
                    <div class="page-heading-actions page-heading-edit-actions" id="page-heading-edit-actions">
                        <button type="button" id="inline-cancel">Abbrechen</button>
                        <button type="button" id="inline-save" class="inline-primary">Speichern</button>
                    </div>
                </div>
                <input type="hidden" id="inline-slug" value="<?= e($currentPage->slug) ?>">
                <input type="hidden" id="media-csrf" value="<?= e($inlineToken) ?>">
                <input type="hidden" id="inline-csrf" value="<?= e($inlineToken) ?>">
                <input type="hidden" id="inline-revision" value="<?= e($inlineRevision) ?>">
            </section>
            <?php endif; ?>
            <?php if ($searchActive): ?>
                <?php require __DIR__ . '/search-results-view.php'; ?>
            <?php else: ?>
            <div class="markdown-content" id="inline-read-content">
                <?= $htmlContent ?>
                <?php if (!$notFound && $currentPage->labels !== []): ?>
                    <div class="page-labels" aria-label="Schlagworte">
                        <?php foreach ($currentPage->labels as $label): ?>
                            <span class="page-label-chip"><?= e($label) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$notFound && $currentPage->dateModified !== null): ?>
                <div class="page-last-modified" aria-label="Letzte Änderung">
                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                    Zuletzt geändert: <time datetime="<?= e($currentPage->dateModified) ?>"><?= e((new DateTimeImmutable($currentPage->dateModified))->format('d.m.Y, H:i')) ?> Uhr</time>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </article>

        <footer class="footer hub-public-footer">
            <span>HomeHub CMS · © 2026 M10-Solutions · <a href="https://www.gnu.org/licenses/gpl-3.0.html" rel="noreferrer">GNU GPLv3+</a></span>
            <span class="hub-app-version">Version <?= e($config['version']) ?></span>
        </footer>
    </main>
</div>
<?php if ($role === null): ?>
<dialog id="hub-login-dialog" class="hub-login-dialog" aria-labelledby="hub-login-title" <?= $loginModalOpen ? 'data-open-on-load' : '' ?>>
    <div class="hub-login-modal-heading hub-dialog-header">
        <div><h2 id="hub-login-title"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> <span class="hub-dialog-title-text">Bei HomeHub anmelden</span></h2><p>Mit deinem Benutzerkonto anmelden.</p></div>
        <button type="button" class="hub-login-close" id="hub-login-close" aria-label="Dialog schliessen"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <?php if ($loginError !== ''): ?><p class="hub-login-error" role="alert"><?= e($loginError) ?></p><?php endif; ?>
    <form method="post" class="hub-login-form">
        <input type="hidden" name="csrf" value="<?= e($authToken) ?>">
        <input type="hidden" name="auth_action" value="login">
        <label for="hub-login-username">Benutzername</label>
        <input id="hub-login-username" name="username" type="text" autocomplete="username" required maxlength="50">
        <label for="hub-login-password">Passwort</label>
        <input id="hub-login-password" name="password" type="password" autocomplete="current-password" required>
        <button type="submit" class="hub-login-submit">Anmelden</button>
    </form>
</dialog>
<?php endif; ?>
<?php if ($canManagePages): ?>
<dialog id="hub-page-create" class="hub-page-dialog" data-page-dialog aria-labelledby="hub-create-title">
<form action="/page-actions.php" method="post">
<h2 class="hub-dialog-header" id="hub-create-title"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> <span class="hub-dialog-title-text">Neue Wiki-Seite</span></h2>
<input type="hidden" name="csrf" value="<?= e($authToken) ?>"><input type="hidden" name="page_action" value="create">
<label>Seitenpfad (a-z, 0-9, - und _)<input name="slug" aria-describedby="hub-create-error" type="text" maxlength="180" required pattern="[a-z0-9_\-]+(\/[a-z0-9_\-]+)*" value="<?= e($createPrefix) ?>" placeholder="thema/neue-seite"></label>
<p id="hub-create-error" role="status" aria-live="polite" class="hub-create-error" hidden></p>
<label>Titel<input name="title" type="text" maxlength="160" required></label>
<label>Beschreibung<textarea name="description" rows="2" maxlength="500"></textarea></label>
<p class="hub-muted"><?= $role === 'admin' ? 'Die neue Seite wird veröffentlicht. Sichtbarkeit und weitere Einstellungen kannst du anschliessend anpassen.' : 'Die neue Seite wird als Draft im berechtigten Kapitel angelegt und kann anschliessend veröffentlicht werden.' ?></p>
<div class="hub-dialog-actions"><button type="button" data-close-page-dialog>Abbrechen</button><button type="submit">Erstellen</button></div>
</form></dialog>
<?php if ($managePage !== null): ?>
<dialog id="hub-page-settings" class="hub-page-dialog" data-page-dialog aria-labelledby="hub-settings-title">
<form action="/page-actions.php" method="post">
<h2 class="hub-dialog-header" id="hub-settings-title"><i class="fa-solid fa-gear" aria-hidden="true"></i> <span class="hub-dialog-title-text">Seiteneinstellungen</span></h2>
<input type="hidden" name="csrf" value="<?= e($authToken) ?>"><input type="hidden" name="page_action" value="settings"><input type="hidden" name="slug" value="<?= e($currentPage->slug) ?>"><input type="hidden" name="revision" value="<?= e($manageRevision) ?>">
<label>Titel<input name="title" type="text" required maxlength="160" value="<?= e($managePage['title']) ?>"></label>
<label>Beschreibung<textarea name="description" rows="2" maxlength="500"><?= e($managePage['description']) ?></textarea></label>
<div class="hub-tag-editor" data-tag-editor data-known-tags="<?= e(json_encode((object) $knownTags, JSON_THROW_ON_ERROR)) ?>">
<label for="hub-tag-fallback" data-tag-label>Schlagworte</label>
<input id="hub-tag-fallback" name="labels" type="text" value="<?= e($managePage['labels']) ?>" aria-label="Schlagworte (kommagetrennt)">
<div data-tag-enhanced hidden>
<div class="hub-tag-field"><div data-tag-pills class="hub-tag-pills"></div><input id="hub-tag-input" type="text" maxlength="40" placeholder="Schlagwort eingeben …" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="hub-tag-options"></div>
<div id="hub-tag-options" class="hub-tag-options" role="listbox" hidden></div>
<p class="hub-muted" data-tag-status aria-live="polite"></p>
</div>
<?php if ($tagWarning !== ''): ?><p class="hub-muted"><?= e($tagWarning) ?></p><?php endif; ?>
</div>
<label>Sortierung<input name="order" type="number" min="-99999" max="99999" value="<?= (int)$managePage['order'] ?>"></label>
<label class="hub-checkbox"><input name="published" type="checkbox" value="1" <?= $managePage['published'] ? 'checked' : '' ?>> Veröffentlicht</label>
<label class="hub-checkbox"><input name="nav" type="checkbox" value="1" <?= $managePage['nav'] ? 'checked' : '' ?>> In Navigation anzeigen</label>
<p class="hub-muted hub-nav-setting-help">Bestimmt, ob die Seite nach der Veröffentlichung in der normalen Wiki-Navigation erscheint. Entwürfe bleiben für berechtigte Admins und Editoren sichtbar; bei deaktivierter Option mit einem Symbol gekennzeichnet.</p>
<?php if ($role === 'admin'): ?>
<label>Sichtbarkeit<select name="visibility">
<?php foreach (['all'=>'Alle', 'authenticated'=>'Angemeldete Benutzer', 'roles'=>'Ausgewählte Rollen', 'users'=>'Ausgewählte Benutzer'] as $value=>$label): ?>
<option value="<?= e($value) ?>" <?= $managePage['visibility'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?></select></label>
<fieldset><legend>Erlaubte Rollen (bei Sichtbarkeit „Ausgewählte Rollen“)</legend>
<?php foreach (['reader','editor','admin'] as $r): ?><label><input type="checkbox" name="allowed_roles[]" value="<?= e($r) ?>" <?= in_array($r,$managePage['allowed_roles'],true) ? 'checked' : '' ?>> <?= e($r) ?></label><?php endforeach; ?>
</fieldset>
<fieldset><legend>Erlaubte Benutzer (bei Sichtbarkeit „Ausgewählte Benutzer“)</legend>
<?php foreach ($manageUsers as $u): ?><label><input type="checkbox" name="allowed_users[]" value="<?= e((string)$u['id']) ?>" <?= in_array($u['id'],$managePage['allowed_users'],true) ? 'checked' : '' ?>> <?= e((string)$u['username']) ?></label><?php endforeach; ?>
</fieldset>
<p class="hub-muted">Die Berechtigungen übergeordneter Seiten gelten weiterhin.</p>
<?php endif; ?>
<div class="hub-dialog-actions"><button type="button" data-close-page-dialog>Abbrechen</button><button type="submit">Speichern</button></div>
</form></dialog>
<dialog id="hub-page-move" class="hub-page-dialog" data-page-dialog aria-labelledby="hub-move-title"><form action="/page-actions.php" method="post">
<h2 class="hub-dialog-header" id="hub-move-title"><i class="fa-solid fa-folder-open" aria-hidden="true"></i> <span class="hub-dialog-title-text">Seite umbenennen oder verschieben</span></h2><input type="hidden" name="csrf" value="<?= e($authToken) ?>"><input type="hidden" name="page_action" value="move"><input type="hidden" name="slug" value="<?= e($currentPage->slug) ?>"><input type="hidden" name="revision" value="<?= e($manageRevision) ?>">
<div class="hub-move-fields" data-page-move-fields>
<label>Zielordner
<select data-move-folder aria-describedby="hub-move-folder-help">
<?php $currentFolder = str_contains($currentPage->slug, '/') ? dirname($currentPage->slug) : ''; ?>
<?php foreach ($moveFolders as $folder): ?>
<option value="<?= e($folder) ?>" <?= $folder === $currentFolder ? 'selected' : '' ?>><?= e($folder === '' ? 'Hauptverzeichnis' : str_repeat('  ', substr_count($folder, '/')) . '📁 ' . basename($folder)) ?></option>
<?php endforeach; ?>
</select></label>
<p id="hub-move-folder-help" class="hub-muted">Wähle einen vorhandenen Ordner. Unterordner sind eingerückt dargestellt.</p>
<label>Seitenname (ohne .md)<input type="text" data-move-name required maxlength="180" pattern="[a-z0-9_-]+" value="<?= e(basename($currentPage->slug)) ?>" autocomplete="off"></label>
<input type="hidden" name="target_slug" data-move-target value="<?= e($currentPage->slug) ?>">
<p class="hub-move-preview" aria-live="polite">Neuer Pfad: <code data-move-preview><?= e($currentPage->slug) ?></code></p>
<p class="hub-move-error" data-move-error role="alert" hidden></p>
</div>
<p class="hub-muted">Die Seiten-ID bleibt erhalten. ID-basierte Links funktionieren weiter; alte pfadbasierte Links müssen gegebenenfalls separat migriert werden.</p>
<div class="hub-dialog-actions"><button type="button" data-close-page-dialog>Abbrechen</button><button type="submit">Verschieben</button></div></form></dialog>
<dialog id="hub-page-delete" class="hub-page-dialog" data-page-dialog aria-labelledby="hub-delete-title"><form id="hub-delete-page-form" action="/page-actions.php" method="post">
<h2 class="hub-dialog-header" id="hub-delete-title"><i class="fa-solid fa-trash" aria-hidden="true"></i> <span class="hub-dialog-title-text">Seite löschen?</span></h2><p>„<?= e($pageTitle) ?>“ wird in den Papierkorb verschoben.</p><input type="hidden" name="csrf" value="<?= e($authToken) ?>"><input type="hidden" name="page_action" value="delete"><input type="hidden" name="slug" value="<?= e($currentPage->slug) ?>">
<?php $descendantCount = count(array_filter($pages->allIncludingDrafts(), static fn($p) => str_starts_with($p->slug, $currentPage->slug . "/"))); ?>
<?php if ($descendantCount > 0 || $role !== 'admin'): ?>
<?php $forceTree = $role === 'admin' && $descendantCount > 0 && $currentPage->visibility !== 'all'; ?>
<label class="hub-checkbox"><input type="checkbox" name="delete_scope" value="tree"<?= $forceTree ? ' checked disabled' : '' ?>> Gesamte Seitenstruktur löschen<?= $role === 'admin' ? ' (' . ($descendantCount + 1) . ' Seiten)' : '' ?></label>
<?php if ($forceTree): ?><input type="hidden" name="delete_scope" value="tree"><p class="hub-muted">Geschützte Kapitel können nur als gesamte Struktur gelöscht werden.</p><?php else: ?><p class="hub-muted">Ohne Auswahl wird nur diese Seite gelöscht. Unterseiten bleiben erhalten.</p><?php endif; ?>
<?php endif; ?>
<div class="hub-dialog-actions"><button type="button" data-close-page-dialog>Abbrechen</button><button type="submit" class="hub-danger">In Papierkorb verschieben</button></div></form></dialog>
<?php endif; ?>
<?php endif; ?>
<?php if (!$notFound && !$isDraftView): ?>
<dialog id="hub-chapter-export-dialog" class="hub-chapter-export-dialog" aria-labelledby="hub-chapter-export-title">
    <form method="get" action="/chapter-export.php" class="hub-chapter-export-form">
        <h2 class="hub-dialog-header" id="hub-chapter-export-title"><i class="fa-solid fa-file-export" aria-hidden="true"></i> Kapitel exportieren</h2>
        <p class="hub-chapter-export-subtitle"><?= e($currentPage->title) ?></p>
        <input type="hidden" name="page" value="<?= e($currentPage->slug) ?>">
        <fieldset><legend>Exportumfang</legend>
            <label><input type="radio" name="scope" value="page"> Nur diese Seite</label>
            <label><input type="radio" name="scope" value="tree" checked> Diese Seite mit allen Unterseiten</label>
        </fieldset>
        <fieldset><legend>Dokumentoptionen (PDF / ODT)</legend>
            <label><input type="checkbox" name="cover" value="1" checked> Deckblatt erstellen</label>
            <label><input type="checkbox" name="toc" value="1" checked> Inhaltsverzeichnis erstellen</label>
            <label><input type="checkbox" name="numbering" value="1" checked> Kapitel automatisch nummerieren</label>
        </fieldset>
        <p class="hub-chapter-export-note">Es werden nur veröffentlichte Seiten berücksichtigt, auf die du Zugriff hast.</p>
        <div class="hub-chapter-export-buttons">
            <button type="button" class="hub-action-btn" data-chapter-export-close><i class="fa-solid fa-xmark" aria-hidden="true"></i> <span>Abbrechen</span></button>
            <button type="submit" class="hub-action-btn primary" name="format" value="pdf"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> <span>PDF erstellen</span></button>
            <button type="submit" class="hub-action-btn primary" name="format" value="odt"><i class="fa-solid fa-file-word" aria-hidden="true"></i> <span>ODT erstellen</span></button>
            <button type="submit" class="hub-action-btn primary" name="format" value="zip"><i class="fa-solid fa-file-export" aria-hidden="true"></i> <span>ZIP erstellen</span></button>
        </div>
    </form>
</dialog>
<?php endif; ?>
<?php if ($inlineAllowed): ?><?php require __DIR__ . "/media-picker-view.php"; ?><script src="/js/media-picker.js?v=2" defer></script><?php endif; ?></body>
</html>
