<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('URBS_VERSION', '0.2.0');
define('SCHEMA_VERSION', 2); // aumente ao adicionar tabelas em app/schema/mysql.sql

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Urbs requer PHP 8.1 ou superior.');
}

$configFile = ROOT . '/config.php';
if (!is_file($configFile)) {
    header('Location: install.php');
    exit;
}
$GLOBALS['URBS_CONFIG'] = require $configFile;

require ROOT . '/app/lib/helpers.php';
require ROOT . '/app/lib/db.php';
require ROOT . '/app/lib/auth.php';
require ROOT . '/app/lib/mailer.php';
require ROOT . '/app/lib/upload.php';
require ROOT . '/app/lib/view.php';
require ROOT . '/app/lib/domain.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

$debug = (bool)config('debug', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/storage/logs/php-error.log');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) use ($debug) {
    error_log((string)$e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($debug) {
        echo '<pre style="white-space:pre-wrap;padding:1rem">' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Erro</title>'
            . '<div style="font-family:system-ui;max-width:32rem;margin:4rem auto;padding:0 1rem">'
            . '<h1>Algo deu errado</h1><p>Ocorreu um erro inesperado. Tente novamente em instantes.</p></div>';
    }
});

// Migração automática: o esquema só usa CREATE TABLE/INDEX IF NOT EXISTS, então reaplicá-lo é seguro.
if ((int)setting('schema_version', '1') < SCHEMA_VERSION) {
    foreach (db_schema_statements((string)config('db.driver', 'mysql')) as $stmt) {
        db()->exec($stmt);
    }
    save_setting('schema_version', (string)SCHEMA_VERSION);
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name('urbs_sid');
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30,
    'path' => base_path() . '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
ini_set('session.use_strict_mode', '1');
session_start();

$GLOBALS['URBS_OLD'] = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header('Permissions-Policy: geolocation=(self), camera=(self)');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https:; "
    . "connect-src 'self' https://nominatim.openstreetmap.org; script-src 'self'; "
    . "style-src 'self' 'unsafe-inline'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
