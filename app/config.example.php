<?php
return [
    'dsn' => 'mysql:host=localhost;dbname=bkp_rozdani;charset=utf8mb4',
    'db_user' => 'bkp_rozdani',
    'db_password' => 'DOPLNIT',
    // php -r 'echo password_hash(readline("Heslo: "), PASSWORD_DEFAULT), PHP_EOL;'
    'admin_password_hash' => 'DOPLNIT',
    // php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
    'secret' => 'DOPLNIT',
];
