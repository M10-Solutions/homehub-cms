<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
require __DIR__.'/../src/lib/JSONAuthenticator.php';
use HomeHub\JSONAuthenticator;
function check(bool $ok): void { if (!$ok) throw new RuntimeException('Assertion failed'); }
function rejects(callable $fn): void { try { $fn(); } catch (InvalidArgumentException|RuntimeException $e) { return; } throw new LogicException('Expected rejection'); }
$d=sys_get_temp_dir().'/homehub-user-'.bin2hex(random_bytes(8)); mkdir($d);
try {
 $hash=password_hash('legacy-password', PASSWORD_DEFAULT);
 file_put_contents($d.'/users.json',json_encode([['username'=>'admin','password'=>$hash,'role'=>'admin','active'=>true]]));
 $a=new JSONAuthenticator($d); $a->ensureUserIds(); $id=$a->user('admin')['id'];
 check($a->allUsers()[0]['email']==='' && !isset($a->allUsers()[0]['password']));
 $a->updateUser('admin','admin','', 'admin',true,['first_name'=>'Alex','last_name'=>'Example','email'=>'alex@example.org']);
 check($a->user('admin')['password']===$hash && $a->user('admin')['id']===$id && $a->allUsers()[0]['first_name']==='Alex');
 rejects(fn()=> $a->updateUser('admin','admin','','admin',true,['email'=>'invalid']));
 rejects(fn()=> $a->updateUser('admin','admin','','reader',true)); rejects(fn()=> $a->deleteUser('admin'));
 $a->registerUser('reader','reader-password','reader',true,['email'=>'reader@example.org']);
 $a->updateUser('reader','renamed','','editor',false); check($a->user('renamed')['email']==='reader@example.org');
 $a->updateUser('renamed','renamed','','editor',false,['email'=>'']); check($a->user('renamed')['email']==='');
 $a->deleteUser('renamed'); check(count($a->allUsers())===1);
} finally {
    foreach (scandir($d) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        unlink($d . '/' . $file);
    }
    rmdir($d);
}
echo "USER-001 OK\n";
