<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/** UAT-002a: integration and contract checks for shared feedback. */
$root = dirname(__DIR__);
$read = static fn(string $path): string => file_get_contents($root . '/' . $path);
$assert = static function (bool $ok, string $message): void { if (!$ok) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } };
foreach (['src/index.php', 'src/admin.php'] as $entry) {
    $text = $read($entry);
    $assert(str_contains($text, '/js/hub-feedback.js?v=2'), "$entry loads shared JS");
    $assert(str_contains($text, '/css/hub-feedback.css?v=2'), "$entry loads shared CSS");
}
$feedback = $read('src/js/hub-feedback.js');
foreach (['window.HomeHubFeedback', 'dialog.showModal()', 'aria-labelledby', 'data-hub-notice', 'setTimeout', 'textContent'] as $token) {
    $assert(str_contains($feedback, $token), "Feedback contract $token");
}
foreach (['src/js/inline-editor.js', 'src/js/admin-media.js'] as $script) {
    $assert(str_contains($read($script), 'HomeHubFeedback.confirm'), "$script uses confirmation");
}
$assert(str_contains($read('src/admin.php'), 'data-hub-notice="<?= h($noticeType) ?>"'), 'admin typed feedback');
$assert(str_contains($read('src/admin.php'), 'data-hub-notice="error"'), 'admin error feedback');
$assert((bool) preg_match('/(?:0\.(?:[1-9][0-9]+)|[1-9][0-9]*\.[0-9]+)-[0-9]{8}/', $read('src/config/app.php')), 'version at least 0.10');
echo "UAT-002a integration checks OK\n";
