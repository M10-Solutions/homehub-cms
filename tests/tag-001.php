<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
require dirname(__DIR__) . '/src/lib/TagRepository.php';
use HomeHub\TagRepository;
$dir = sys_get_temp_dir() . '/hub-tags-' . bin2hex(random_bytes(8));
$file = $dir . '/tags.json';
function check(bool $ok): void { if (!$ok) throw new RuntimeException('Tag assertion failed'); }
try {
    $a = new TagRepository($file); $b = new TagRepository($file);
    check($a->all() === [] && is_file($file));
    check($b->all() === []);
    $ids = $a->assign(['Docker', 'DOCKER', 'ÄPFEL']);
    $nas = $b->assign(['NAS']);
    check(count(array_unique($ids)) === 3 && TagRepository::isId($nas[0]));
    check(array_values((new TagRepository($file))->all()) === ['DOCKER','Docker','NAS','ÄPFEL']);
    $before = file_get_contents($file);
    check($b->assign(['NAS', $nas[0]]) === $nas);
    check(file_get_contents($file) === $before);
    check((new TagRepository($file))->names($ids) === ['Docker','DOCKER','ÄPFEL']);
    file_put_contents($file, '{broken');
    try { $a->assign(['test']); throw new LogicException('Invalid JSON accepted'); } catch (JsonException $expected) {}
    check(file_get_contents($file) === '{broken');
} finally { foreach (glob($dir . '/*') ?: [] as $path) unlink($path); if (is_dir($dir)) rmdir($dir); }
