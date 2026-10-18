<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/app/RootHosting.php';
use Friends\RootHosting;
$count=0;
$check=static function(bool $ok,string $message) use(&$count):void {
    if (!$ok) throw new RuntimeException('FAIL '.$message);
    ++$count; echo "PASS $message\n";
};
$check(!RootHosting::protectedRequest([]),'missing protection marker is refused');
$check(!RootHosting::protectedRequest(['HTTP_FRIENDS_ROOT_GUARD'=>'1']),'HTTP header cannot forge the protection marker');
$check(!RootHosting::protectedRequest(['HTTP_REDIRECT_FRIENDS_ROOT_GUARD'=>'1']),'prefixed HTTP header cannot forge protection');
$check(RootHosting::protectedRequest(['FRIENDS_ROOT_GUARD'=>'1']),'server rewrite marker accepted');
$check(RootHosting::protectedRequest(['REDIRECT_REDIRECT_FRIENDS_ROOT_GUARD'=>'1']),'CGI redirect marker preserved');
foreach (['','index.php','install.php','public/assets/app.css','public/assets/features.js','public/assets/mark.svg','.well-known/acme-challenge/test-token'] as $path) $check(RootHosting::allowed($path),'public path allowed '.$path);
foreach (['storage/config.php','storage/files/file.png','app/bootstrap.php','database/schema.sql','resources/lang/de.php','plugins/welcome/plugin.php','themes/ember/manifest.json','tests/integration.php','bin/router.php','README.md','.git/config','.htaccess','public/index.php','public/assets/payload.php','public/assets/../../storage/config.php','INDEX.PHP/extra'] as $path) $check(!RootHosting::allowed($path),'private path refused '.$path);
echo "\n$count hosting policy checks passed. Actual web-server rules require HTTP acceptance.\n";
