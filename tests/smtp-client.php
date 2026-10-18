<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
putenv('FRIENDS_CONFIG='.$argv[1].'/config.php');
require dirname(__DIR__).'/app/bootstrap.php';
$db=new Friends\Db(['dsn'=>'sqlite:'.$argv[1].'/test.sqlite']); $db->migrate();
foreach(['secret'=>bin2hex(random_bytes(32)),'schema_version'=>'3','mail_transport'=>'smtp','mail_from'=>'sender@example.test','smtp_host'=>'127.0.0.1','smtp_port'=>$argv[2],'smtp_security'=>$argv[3],'smtp_user'=>'fixture-user'] as $key=>$value) $db->query('INSERT INTO f_settings (name,value) VALUES (?,?)',[$key,$value]);
file_put_contents(FRIENDS_CONFIG,'<?php return '.var_export(['dsn'=>'sqlite:'.$argv[1].'/test.sqlite'],true).';');
file_put_contents($argv[1].'/smtp-secret.php',"<?php return 'fixture-password';");
$_SESSION=[];$_GET=[];$app=new Friends\App();
try { (new Friends\Mailer($app))->send('recipient@example.test','Lokaler TLS-Test','Grüße aus dem isolierten SMTP-Test 😊'); echo 'accepted'; }
catch(DomainException) { echo 'rejected'; exit(2); }
