<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
// Explicit development tool. Writes only to a new directory supplied on the command line.
if (PHP_SAPI!=='cli' || empty($argv[1])) { exit("Usage: php -d extension=pdo_sqlite bin/seed-preview.php NEW_DIRECTORY\n"); }
$directory=$argv[1];
if (file_exists($directory)) exit("Refusing to overwrite an existing directory.\n");
mkdir($directory,0700,true); putenv('FRIENDS_CONFIG='.$directory.'/config.php');
require dirname(__DIR__).'/app/bootstrap.php';
$config=['dsn'=>'sqlite:'.str_replace('\\','/',$directory).'/preview.sqlite'];
$db=new Friends\Db($config); $db->migrate();
foreach(['site_name'=>'Friends-SNCMS','locale'=>'de','registration'=>'1','theme'=>'friends','plugin.welcome'=>'1','secret'=>bin2hex(random_bytes(32)),'schema_version'=>'3'] as $key=>$value) $db->query('INSERT INTO f_settings (name,value) VALUES (?,?)',[$key,$value]);
$password=getenv('FRIENDS_PREVIEW_PASSWORD')?:bin2hex(random_bytes(12));
foreach([['andreas','Andreas Holzer','Eine neue Community entsteht. Gemeinsam bauen, lernen und Ideen teilen.'],['mira','Mira Sommer','Design, Kaffee und gute Gespräche.'],['jonas','Jonas Berg','Am liebsten draußen. Und manchmal hier.']] as $i=>$person) $db->query('INSERT INTO f_users (username,email,password,name,bio,role,locale,created_at) VALUES (?,?,?,?,?,?,?,?)',[$person[0],$person[0].'@example.test',password_hash($password,PASSWORD_DEFAULT),$person[1],$person[2],$i===0?'admin':'member','de',gmdate('Y-m-d H:i:s')]);
foreach([['group','Kreativ zusammen','Ein Raum für neue Ideen und kleine Herzensprojekte.'],['group','Draußen unterwegs','Neue Wege, frische Luft und die nächste gemeinsame Tour.'],['page','Friends Werkstatt','Neuigkeiten aus der Entwicklung unserer Community.']] as $community) { $id=$db->insert('INSERT INTO f_communities (owner_id,kind,name,description,created_at) VALUES (1,?,?,?,?)',[...$community,gmdate('Y-m-d H:i:s')]); foreach([1,2,3] as $uid) $db->query('INSERT INTO f_memberships (community_id,user_id) VALUES (?,?)',[$id,$uid]); }
foreach([[2,'Manchmal braucht es nur einen Ort, an dem aus einer kleinen Idee ein gutes Gespräch wird.\n\nWas möchtet ihr hier gemeinsam aufbauen? 🌱'],[1,'Willkommen bei Friends!\n\nDies ist unsere lokale Entwicklungsvorschau: mit eigenem Design, einem integrierten Erweiterungssystem und viel Platz für eure Ideen.'],[3,'Laptop zu, Schuhe an. Wer war heute schon draußen unterwegs? ☀']] as $entry) $db->query('INSERT INTO f_posts (user_id,body,created_at) VALUES (?,?,?)',[$entry[0],str_replace('\\n',"\n",$entry[1]),gmdate('Y-m-d H:i:s')]);
$db->query('INSERT INTO f_friendships (low_id,high_id,requester_id,status) VALUES (1,2,2,?)',['accepted']);
$db->query('INSERT INTO f_comments (post_id,user_id,body,created_at) VALUES (1,1,?,?)',['Einen offenen Ort für Menschen, die gerne gemeinsam etwas gestalten.',gmdate('Y-m-d H:i:s')]);
$db->query('INSERT INTO f_likes (post_id,user_id) VALUES (1,1)');
file_put_contents(FRIENDS_CONFIG,'<?php return '.var_export($config,true).';');
echo "Local development preview created. Username: andreas\n";
if (!getenv('FRIENDS_PREVIEW_PASSWORD')) echo 'Temporary password: '.$password."\n";
