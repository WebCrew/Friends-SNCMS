<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
// Preserve the optional public-only deployment, but do not let /public/index.php
// bypass root-upload protection on a server that ignores .htaccess.
if (PHP_SAPI!=='cli' && !defined('FRIENDS_ASSET_PREFIX')) {
    $publicRoot=realpath(__DIR__);
    if (realpath($_SERVER['DOCUMENT_ROOT']??'')!==$publicRoot) {
        require_once dirname(__DIR__).'/app/RootHosting.php';
        Friends\RootHosting::requireProtection();
        define('FRIENDS_ASSET_PREFIX','public/assets/');
    }
}
require dirname(__DIR__) . '/app/bootstrap.php';
try {
    (new Friends\App())->run();
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Friends-SNCMS</title><h1>Friends-SNCMS</h1><p>Something went wrong. Please contact your administrator. / Ein Fehler ist aufgetreten. Bitte kontaktiere die Administration.</p>';
}
