<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/app/bootstrap.php';
$db=new Friends\Db(['dsn'=>'sqlite:'.$argv[1].'/test.sqlite']);
if (($argv[2]??'')==='state') { echo json_encode($db->all('SELECT username,status,auth_version FROM f_users')); exit; }
$db->migrate();
foreach(['secret'=>bin2hex(random_bytes(32)),'schema_version'=>'3','mail_transport'=>'smtp','public_url'=>'https://community.example.test','mail_from'=>'sender@example.test','smtp_host'=>'127.0.0.1','smtp_port'=>$argv[2],'smtp_security'=>'tls'] as $k=>$v) $db->query('INSERT INTO f_settings (name,value) VALUES (?,?)',[$k,$v]);
file_put_contents($argv[1].'/config.php','<?php return '.var_export(['dsn'=>'sqlite:'.$argv[1].'/test.sqlite'],true).';');
