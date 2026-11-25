<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$auth = new class {
    public array $profile = [];
    public function currentUser(): string { return 'pmuster'; }
    public function user(string $name): array { return $this->profile; }
};
$role = 'admin';
foreach ([
    [['first_name'=>'Peter','last_name'=>'Muster'], 'Peter Muster'],
    [['last_name'=>'Muster'], 'Muster'],
    [[], 'pmuster'],
    [['first_name'=>'<Peter>'], '&lt;Peter&gt;'],
] as [$profile,$expected]) {
    $auth->profile = $profile;
    ob_start(); require __DIR__.'/../src/sidebar-account-view.php'; $html=ob_get_clean();
    foreach (['title="Administrator"','aria-label="Administrator"','hub-sidebar-account-name">'.$expected.'</strong>','hub-sidebar-account-username">pmuster</span>'] as $token) {
        if (!str_contains($html,$token)) throw new RuntimeException('Missing '.$token);
    }
}
echo "UI-006 account rendering passed\n";
