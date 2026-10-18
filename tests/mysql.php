<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('FRIENDS_TEST_MYSQL') !== '1') exit;
// Only the explicitly named, disposable localhost CI database is used. Never load site configuration.
$directory=sys_get_temp_dir().'/friends-mysql-'.bin2hex(random_bytes(6));
mkdir($directory,0700);
putenv('FRIENDS_CONFIG='.$directory.'/config.php');
require dirname(__DIR__).'/app/bootstrap.php';
use Friends\{App,Db,Features};
$config=['dsn'=>'mysql:host=127.0.0.1;port=3306;dbname=friends_ci;charset=utf8mb4','user'=>'friends_ci','password'=>'disposable-ci-only'];
$db=new Db($config);
$count=0;
$assert=static function(bool $ok,string $message)use(&$count):void { if(!$ok)throw new RuntimeException($message);++$count;echo "PASS $message\n"; };
try {
    $assert(str_starts_with((string)$db->value('SELECT VERSION()'),'5.7.'),'actual MySQL 5.7 server');
    $assert((int)$db->value('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')===0,'empty disposable database required');
    $db->migrate();
    $assert((int)$db->value('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')>15,'complete schema installs');
    file_put_contents(FRIENDS_CONFIG,'<?php return '.var_export($config,true).';');
    foreach(['secret'=>bin2hex(random_bytes(32)),'schema_version'=>'3','site_name'=>'CI','theme'=>'ember'] as $key=>$value)$db->query('INSERT INTO f_settings (name,value) VALUES (?,?)',[$key,$value]);
    foreach(['admin','member','outsider'] as $i=>$username)$db->query('INSERT INTO f_users (username,email,password,name,bio,role,created_at) VALUES (?,?,?,?,?,?,?)',[$username,$username.'@example.invalid',password_hash(bin2hex(random_bytes(12)),PASSWORD_DEFAULT),$username,'',$i===0?'admin':'member',gmdate('Y-m-d H:i:s')]);
    $_SESSION=['user_id'=>1,'installation'=>hash('sha256',(string)$db->value("SELECT value FROM f_settings WHERE name='secret'")),'locale'=>'en'];
    $_GET=['r'=>'feed'];$_POST=[];$_FILES=[];$_SERVER['REMOTE_ADDR']='127.0.0.1';
    $app=new App();
    $assert($app->user['role']==='admin','installed admin loads');
    $_POST=['upload_mb'=>64,'upload_daily_mb'=>100,'post_attachments'=>8,'feed_images'=>1,'feed_files'=>1,'link_previews'=>1];
    $app->act('admin_uploads');
    $assert($app->setting('upload_mb')==='64'&&$app->setting('post_attachments')==='8','upload settings persist');
    $_POST=['body'=>'MySQL emoji test 😀','visibility'=>'members'];$app->act('post');
    $id=(int)$db->value('SELECT MAX(id) FROM f_posts');
    $assert($db->value('SELECT body FROM f_posts WHERE id=?',[$id])==='MySQL emoji test 😀','utf8mb4 post round trip');
    $_POST=['body'=>'Private test','visibility'=>'private'];$app->act('post');
    $private=(int)$db->value('SELECT MAX(id) FROM f_posts');
    $_SESSION['user_id']=2;$member=new App();
    try{$member->visiblePost($private);throw new RuntimeException('Private post exposed');}catch(DomainException){$assert(true,'private post denied to another member');}
    try{$member->act('admin_uploads');throw new RuntimeException('Member admin access');}catch(DomainException){$assert(true,'member settings access denied');}
    $db->query('INSERT INTO f_friendships (low_id,high_id,requester_id,status) VALUES (1,2,1,?)',['accepted']);
    $assert($member->friends(1,2),'accepted friendship query');
    $db->query('INSERT INTO f_comments (post_id,user_id,body,created_at) VALUES (?,?,?,?)',[$id,2,'CI comment',gmdate('Y-m-d H:i:s')]);
    $db->query('INSERT INTO f_likes (post_id,user_id) VALUES (?,?)',[$id,2]);
    $assert((int)$db->value('SELECT COUNT(*) FROM f_comments WHERE post_id=?',[$id])===1,'comment insert');
    $db->query('DELETE FROM f_posts WHERE id=?',[$id]);
    $assert((int)$db->value('SELECT COUNT(*) FROM f_comments WHERE post_id=?',[$id])===0&&(int)$db->value('SELECT COUNT(*) FROM f_likes WHERE post_id=?',[$id])===0,'foreign-key cascades');
    $app->throttle('mysql-ci',2,60,false);$app->throttle('mysql-ci',2,60,false);
    try{$app->throttle('mysql-ci',2,60,false);throw new RuntimeException('Throttle ignored');}catch(DomainException){$assert(true,'MySQL atomic throttle upsert');}
    echo "$count MySQL 5.7 smoke checks passed. Disposable CI database only.\n";
} finally {
    if(is_file(FRIENDS_CONFIG))unlink(FRIENDS_CONFIG);
    rmdir($directory);
}
