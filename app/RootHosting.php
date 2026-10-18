<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
final class RootHosting
{
    public static function protectedRequest(array $server): bool
    {
        // Apache CGI/FastCGI can prefix rewrite environment variables after an internal redirect.
        // Never accept a similarly named HTTP request header as proof.
        for ($prefix='',$i=0;$i<5;$i++,$prefix.='REDIRECT_') {
            if (($server[$prefix.'FRIENDS_ROOT_GUARD']??'')==='1') return true;
        }
        return false;
    }
    public static function allowed(string $path): bool
    {
        return preg_match('~^(?:index\.php|install\.php)?$~D',$path)===1
            || preg_match('~^public/assets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_.-]+\.(?:css|js|svg|png|jpe?g|webp|ico|woff2?)$~D',$path)===1
            || preg_match('~^\.well-known/acme-challenge/[a-zA-Z0-9_-]+$~D',$path)===1;
    }
    public static function requireProtection(): void
    {
        if (self::protectedRequest($_SERVER)) return;
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Friends-SNCMS · Webspace</title><h1>Friends-SNCMS</h1><p>Die Webspace-Schutzregeln sind noch nicht aktiv. Bitte lade auch die mitgelieferte versteckte Datei <strong>.htaccess</strong> hoch.</p><p>Bleibt diese Meldung bestehen, bitte den Hoster, die Apache-kompatiblen .htaccess-Regeln mit mod_rewrite zu aktivieren. Bis dahin startet die Installation nicht.</p><p>The included .htaccess protection must be active before installation. Upload hidden files too; ask your host to enable compatible Apache rewrite rules if needed.</p></html>';
        exit;
    }
}
