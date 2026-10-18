<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit;
$dir=sys_get_temp_dir().'/friends-refinements-'.bin2hex(random_bytes(6)); mkdir($dir);
putenv('FRIENDS_CONFIG='.$dir.'/config.php'); require dirname(__DIR__).'/app/bootstrap.php';
use Friends\{App,Db,VoiceSetup,CommunityImages};
$config=['dsn'=>'sqlite:'.$dir.'/test.sqlite']; $db=new Db($config); $db->migrate();
foreach(['secret'=>bin2hex(random_bytes(32)),'schema_version'=>'3','site_name'=>'Preserve me','registration'=>'1'] as $k=>$v) $db->query('INSERT INTO f_settings (name,value) VALUES (?,?)',[$k,$v]);
file_put_contents(FRIENDS_CONFIG,'<?php return '.var_export($config,true).';');
foreach(['admin','owner','stranger'] as $i=>$name) $db->query('INSERT INTO f_users (username,email,password,name,bio,role,created_at) VALUES (?,?,?,?,?,?,?)',[$name,$name.'@example.test',password_hash('Test Fixture Password!',PASSWORD_DEFAULT),$name,'',$i===0?'admin':'member',gmdate('Y-m-d H:i:s')]);
$db->query("INSERT INTO f_communities (owner_id,kind,name,description,created_at) VALUES (2,'group','Test','',?)",[gmdate('Y-m-d H:i:s')]);
$_GET=[]; $_SESSION=[]; $_SERVER['REMOTE_ADDR']='127.0.0.1'; $app=new App(); $count=0;
$check=static function(bool $ok,string $label) use(&$count):void { if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n"; };
$deny=static function(callable $fn,string $label) use($check):void { try{$fn();}catch(DomainException){$check(true,$label);return;}$check(false,$label); };
try {
 $check(VoiceSetup::configuration('direct',[])===[],'direct mode has no external provider');
 $check(VoiceSetup::configuration('stun',[])[0]['urls']==='stun:stun.cloudflare.com:3478','STUN preset uses documented endpoint');
 $input=['turn_host'=>'turn.example.test','turn_port'=>3478,'turn_transport'=>'udp','turn_user'=>'restricted','turn_password'=>'fixture-only'];
 $check(VoiceSetup::configuration('turn',$input)[0]['urls']==='turn:turn.example.test:3478?transport=udp','guided TURN generates browser configuration');
 $tls=VoiceSetup::configuration('turn',array_replace($input,['turn_port'=>5349,'turn_transport'=>'tls']));
 $check($tls[0]['urls']==='turns:turn.example.test:5349?transport=tcp','TLS transport and explicit port retained');
 $check(str_contains(VoiceSetup::configuration('turn',array_replace($input,['turn_host'=>'2001:db8::1']))[0]['urls'],'[2001:db8::1]'),'IPv6 TURN host bracketed correctly');
 foreach([['turn_host'=>'https://bad.test'],['turn_host'=>'user@bad.test'],['turn_port'=>0],['turn_port'=>65536],['turn_password'=>''],['turn_user'=>[]],['turn_transport'=>'bad']] as $change) $deny(fn()=>VoiceSetup::configuration('turn',array_replace($input,$change)),'invalid guided TURN field rejected');
 $deny(fn()=>VoiceSetup::configuration('advanced',['ice_servers'=>'not json']),'advanced configuration validated');
 $check(VoiceSetup::configuration('advanced',['ice_servers'=>'[]'])===[],'advanced empty list supported');
 $images=new CommunityImages($app); $_POST=[];$_FILES=[];
 $deny(fn()=>$images->save(1),'guest cannot edit community images');
 $app->user=$db->one('SELECT * FROM f_users WHERE id=3');
 $deny(fn()=>$images->save(1),'unrelated member cannot edit community images');
 $deny(fn()=>VoiceSetup::save($app),'member cannot change voice setup');
 $app->user=$db->one('SELECT * FROM f_users WHERE id=2');$images->save(1);
 $check($images->images(1)===['avatar'=>0,'header'=>0],'owner can save empty image configuration');
 $app->saveSetting('community.1.avatar','123');$check($images->linked(123),'image reference is scoped to existing community');
 $app->saveSetting('community.999.avatar','456');$check(!$images->linked(456),'orphaned community reference cannot expose media');
 $check(!$images->linked(789),'unlinked media remains private');
 $app->user=$db->one('SELECT * FROM f_users WHERE id=1');$_POST=['voice_setup'=>'stun'];VoiceSetup::save($app);
 $check($app->setting('site_name')==='Preserve me' && $app->setting('registration')==='1','voice setup does not alter site or registration settings');
 $check(json_decode($app->setting('ice_servers'),true)===VoiceSetup::configuration('stun',[]),'guided mode saved as existing ICE format');
 $de=require FRIENDS_ROOT.'/resources/lang/de.php';$en=require FRIENDS_ROOT.'/resources/lang/en.php';$check(array_keys($de)===array_keys($en),'translation keys match');
 echo "$count refinement checks passed. No external network used.\n";
} finally {$images=null;$app=null;$db=null;gc_collect_cycles();foreach(glob($dir.'/*') as $f)@unlink($f);@rmdir($dir);}
