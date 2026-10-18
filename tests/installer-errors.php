<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/app/bootstrap.php';
$cases=[1045=>'db_auth_error',1044=>'db_permission_error',1049=>'db_missing_error',2002=>'db_unreachable_error',2003=>'db_unreachable_error',2005=>'db_host_error',2026=>'db_tls_error',1040=>'db_busy_error',9999=>'db_connect_error'];
foreach($cases as $code=>$key) {
    if(Friends\Installer::databaseErrorKey($code)!==$key) throw new RuntimeException('Incorrect database error classification.');
    foreach(['de','en'] as $language) {
        $strings=require FRIENDS_ROOT.'/resources/lang/'.$language.'.php';
        if(empty($strings[$key])) throw new RuntimeException('Missing installer explanation.');
    }
}
echo count($cases)." installer error classifications passed in DE/EN. No database credentials used.\n";
