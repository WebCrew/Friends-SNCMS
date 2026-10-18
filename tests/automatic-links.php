<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit;
$dir=sys_get_temp_dir().'/friends-links-'.bin2hex(random_bytes(6)); mkdir($dir);
putenv('FRIENDS_CONFIG='.$dir.'/config.php'); require dirname(__DIR__).'/app/bootstrap.php';
use Friends\{App,Db,Links};
$db=new Db(['dsn'=>'sqlite:'.$dir.'/test.sqlite']); $db->migrate();
foreach(['secret'=>bin2hex(random_bytes(32)),'schema_version'=>'3','spam_protection'=>'0'] as $k=>$v) $db->query('INSERT INTO f_settings (name,value) VALUES (?,?)',[$k,$v]);
file_put_contents(FRIENDS_CONFIG,'<?php return '.var_export(['dsn'=>'sqlite:'.$dir.'/test.sqlite'],true).';');
foreach(['alice','bob'] as $name) $db->query('INSERT INTO f_users (username,email,password,name,bio,created_at) VALUES (?,?,?,?,?,?)',[$name,$name.'@example.test',password_hash('Fixture Password!',PASSWORD_DEFAULT),$name,'',gmdate('Y-m-d H:i:s')]);
$_SESSION=[];$_GET=[];$_FILES=[];$_SERVER['REMOTE_ADDR']='127.0.0.1';$app=new App();$app->user=$db->one('SELECT * FROM f_users WHERE id=1');$count=0;
$check=static function(bool $ok,string $label)use(&$count):void{if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";};
$post=static function(string $body,array $extra=[])use($app,$db):int{$_POST=array_replace(['body'=>$body,'visibility'=>'members'],$extra);$app->act('post');return (int)$db->value('SELECT MAX(id) FROM f_posts');};
try {
 foreach(json_decode(file_get_contents(__DIR__.'/link-cases.json'),true,512,JSON_THROW_ON_ERROR) as [$text,$expected]) $check(Links::first($text)===$expected,'server URL extraction matches shared fixture');
 $id=$post('Without JavaScript: http://127.0.0.1/example.');
 $check($db->value('SELECT url FROM f_post_links WHERE post_id=?',[$id])==='http://127.0.0.1/example','body URL creates stored card without preview API or extra field');
 $id=$post('First http://127.0.0.1/first then http://127.0.0.1/second');
 $check($db->value('SELECT url FROM f_post_links WHERE post_id=?',[$id])==='http://127.0.0.1/first','first valid body URL determines the single card');
 $url='https://example.test/article';$metadata=Links::metadata('<title>Server-side article</title><meta name="description" content="Safe description">',$url);
 $_SESSION['link_previews'][hash('sha256',$url)]=['expires'=>time()+900,'result'=>$metadata];
 $id=$post('Cached server preview '.$url,['title'=>'Forged title','description'=>'Forged description']);
 $check($db->value('SELECT title FROM f_post_links WHERE post_id=?',[$id])==='Server-side article','published card reuses trusted server metadata and ignores client metadata');
 $posts=$app->posts();$card=array_values(array_filter($posts,static fn($p)=>(int)$p['id']===$id))[0]['link'];
 $check($card['description']==='Safe description','feed query includes the persisted card');
 $id=$post('Changed text http://127.0.0.1/new',['post_link'=>$url]);
 $check($db->value('SELECT url FROM f_post_links WHERE post_id=?',[$id])==='http://127.0.0.1/new','current body wins over stale old link fields');
 $id=$post('No URL remains after editing');$check(!(bool)$db->one('SELECT post_id FROM f_post_links WHERE post_id=?',[$id]),'text without URL stores no card');
 $app->saveSetting('link_previews','0');$id=$post('Disabled preview http://127.0.0.1/disabled');
 $check(!(bool)$db->one('SELECT post_id FROM f_post_links WHERE post_id=?',[$id]),'admin disabling previews still allows ordinary URL text');
 $app->saveSetting('link_previews','1');
 for($i=0;$i<31;$i++){try{$app->throttle('preview:1',30,3600,false);}catch(DomainException){}}
 $id=$post('Limited preview http://127.0.0.1/limited',['visibility'=>'private']);
 $check($db->value('SELECT title FROM f_post_links WHERE post_id=?',[$id])==='127.0.0.1','preview rate limit does not discard valid post or fallback card');
 $app->user=$db->one('SELECT * FROM f_users WHERE id=2');
 try{$app->visiblePost($id);$check(false,'private preview denied');}catch(DomainException){$check(true,'private link post remains private');}
 echo "$count automatic-link checks passed. No external network used.\n";
} finally {$app=null;$db=null;gc_collect_cycles();foreach(glob($dir.'/*') as $f)@unlink($f);@rmdir($dir);}

