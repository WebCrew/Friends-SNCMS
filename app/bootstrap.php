<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
define('FRIENDS_ROOT', dirname(__DIR__));
if (!defined('FRIENDS_ASSET_PREFIX')) define('FRIENDS_ASSET_PREFIX','assets/');
define('FRIENDS_CONFIG', getenv('FRIENDS_CONFIG') ?: FRIENDS_ROOT . '/storage/config.php');
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, __NAMESPACE__ . '\\')) {
        $file = FRIENDS_ROOT . '/app/' . substr($class, strlen(__NAMESPACE__) + 1) . '.php';
        if (is_file($file)) require $file;
    }
});
date_default_timezone_set('UTC');
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors','0');
    ini_set('log_errors','1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('friends_' . substr(hash('sha256', FRIENDS_CONFIG), 0, 12));
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => '/']);
    session_start();
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    header('Permissions-Policy: microphone=(self), camera=()');
    header('Cache-Control: no-store');
}
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
