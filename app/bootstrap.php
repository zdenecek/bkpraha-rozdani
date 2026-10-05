<?php
declare(strict_types=1);
ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'self' https://www.bkpraha.cz https://bkpraha.cz https://bkpraha.cms.webnode.cz");
set_exception_handler(function(Throwable $e): void {
    error_log('BKP rozdani: '.get_class($e));
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>'Služba nyní není dostupná. Zkuste to prosím později.'], JSON_UNESCAPED_UNICODE);
});
$config = require __DIR__.'/config.php';
if (strlen($config['secret'] ?? '') < 32 || !str_starts_with($config['admin_password_hash'] ?? '', '$')) {
    throw new RuntimeException('Incomplete configuration');
}
session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Lax','path'=>'/']);
ini_set('session.use_strict_mode','1');
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
function db(): PDO {
    static $pdo;
    global $config;
    return $pdo ??= new PDO($config['dsn'],$config['db_user'],$config['db_password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
}
function fail(string $message, int $status=400): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>$message],JSON_UNESCAPED_UNICODE); exit;
}
require_once __DIR__.'/settings.php';
function admin(): bool { return admin_session_valid($_SESSION,time()); }
function csrf(string $value): void {
    if (!hash_equals($_SESSION['csrf'], $value)) fail('Platnost stránky vypršela. Obnovte ji a zkuste to znovu.',403);
}
function limit(string $action,int $max): void {
    global $config;
    $bucket=hash_hmac('sha256',$action.'|'.($_SERVER['REMOTE_ADDR'] ?? '').'|'.gmdate('YmdH'),$config['secret']);
    db()->exec('DELETE FROM rate_limits WHERE expires_at < UTC_TIMESTAMP()');
    $q=db()->prepare('INSERT INTO rate_limits(bucket,hits,expires_at) VALUES(?,1,DATE_ADD(UTC_TIMESTAMP(), INTERVAL 2 HOUR)) ON DUPLICATE KEY UPDATE hits=hits+1');
    $q->execute([$bucket]);
    $q=db()->prepare('SELECT hits FROM rate_limits WHERE bucket=?');$q->execute([$bucket]);
    if ((int)$q->fetchColumn()>$max) fail('Příliš mnoho pokusů. Zkuste to prosím později.',429);
}
function h(string $s): string { return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }

