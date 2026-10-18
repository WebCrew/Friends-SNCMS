<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
$label=static fn(string $key):string=>e($app->t($key));
$field=static function(string $key,string $type='text',string $value='',bool $required=true) use($app):void { ?><label><?= e($app->t($key)) ?><input name="<?= e($key) ?>" type="<?= e($type) ?>" value="<?= e($value) ?>" <?= $required?'required':'' ?> <?= in_array($key,['password','new_password','password_confirm'],true)?'minlength="12" maxlength="72" autocomplete="new-password"':'' ?>></label><?php };
$form=static function(string $action,int $id=0) use($app):void { echo $app->field().'<input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="id" value="'.$id.'">'; };
switch($view):
case 'error': ?><section class="card"><h1><?= e($message) ?></h1><a href="<?= e($app->url()) ?>">← <?= $label('feed') ?></a></section><?php break;
case 'install':
 $step=(int)($_SESSION['install_step']??1); ?>
 <div class="install-heading"><span class="eyebrow">FRIENDS-SNCMS / SETUP</span><h1><?= $label('install_title') ?></h1><p><?= $label('install_intro') ?></p></div>
 <ol class="steps"><?php for($i=1;$i<=3;$i++): ?><li class="<?= $i===$step?'current':'' ?>"><span><?= $i ?></span><?= $label('step'.$i) ?></li><?php endfor ?></ol>
 <section class="card install-card">
 <?php if(is_file(FRIENDS_ROOT.'/storage/installed.lock')): ?><p><?= $label('installation_locked') ?></p>
 <?php elseif($step===1): ?><h2><?= $label('step1') ?></h2><?php foreach($installer->checks() as $name=>$ok): ?><div class="check-row"><span><?= e($name) ?></span><span class="<?= $ok?'check-ok':'check-fail' ?>"><?= $ok?'✓':'×' ?></span></div><?php endforeach ?><form method="post"><?php $form('install_check') ?><button class="button" <?= in_array(false,$installer->checks(),true)?'disabled':'' ?>><?= $label('continue') ?> →</button></form>
 <?php elseif($step===2): ?><h2><?= $label('step2') ?></h2><p><?= $label('db_note') ?></p><form method="post" class="stack"><?php $form('install_database'); $field('host','text','127.0.0.1'); $field('port','number','3306'); $field('database'); $field('db_user'); $field('db_password','password','',false) ?><button class="button"><?= $label('continue') ?> →</button></form>
 <?php else: ?><h2><?= $label('step3') ?></h2><form method="post" class="stack"><?php $form('install_finish'); $field('site_name','text','Friends-SNCMS'); $field('name'); $field('username'); $field('email','email'); $field('password','password') ?><button class="button"><?= $label('finish') ?> →</button></form><?php endif ?>
 <?php if($step>1): ?><form method="post" class="back-form"><?php $form('install_back') ?><button class="text-button">← <?= $label('back') ?></button></form><?php endif ?></section>
<?php break;
case 'forgot': case 'reset': case 'verify':
 ?>
 <section class="card auth-card"><h1><?= $label($view) ?></h1>
 <?php if($view==='forgot'): ?><p><?= $label('forgot_help') ?></p><form method="post" class="stack"><?php $form('forgot'); $field('email','email') ?><button class="button"><?= $label('send_reset') ?></button></form>
 <?php elseif($view==='reset' || isset($_GET['token'])): ?>
 <?php try { (new Friends\AccountMail($app))->account(is_string($_GET['token']??null)?$_GET['token']:'',$view==='verify'?'verify':'reset'); $valid=true; } catch(DomainException) { $valid=false; } ?>
 <?php if($valid): ?><form method="post" class="stack"><?php $form($view) ?><input type="hidden" name="token" value="<?= e($_GET['token']) ?>"><?php if($view==='reset'){ $field('password','password'); $field('password_confirm','password'); } ?><button class="button"><?= $label($view==='reset'?'save_password':'confirm_email') ?></button></form><?php else: ?><p role="alert"><?= $label('account_link_invalid') ?></p><?php endif ?>
 <?php endif ?>
 <?php if($view==='verify'): ?><h2><?= $label('resend_verification') ?></h2><form method="post" class="stack"><?php $form('resend_verification'); $field('email','email') ?><button class="button"><?= $label('resend_verification') ?></button></form><?php endif ?>
 <p><a href="<?= e($app->url('login')) ?>"><?= $label('login') ?></a> · <a href="<?= e($app->url('forgot')) ?>"><?= $label('forgot') ?></a></p></section>
<?php break;
case 'login': case 'register': ?>
 <section class="auth-layout"><div class="auth-intro"><span class="eyebrow">WELCOME TO FRIENDS</span><h1><?= $label('intro') ?></h1><p><?= $label('intro_text') ?></p><div class="people-art" aria-hidden="true"><span>✳</span><span>f.</span><span>☺</span><div class="art-caption">FRIENDS, TOGETHER.</div></div><span class="fineprint"><?= $label('project') ?></span></div><div class="card auth-card"><h2><?= $label($view) ?></h2><p><?= $label('tagline') ?></p><form method="post" class="stack"><?php $form($view); if($view==='register'){ $field('name'); $field('username'); $field('email','email'); } else $field('identity'); ?>
 <label><?= $label('password') ?><input type="password" name="password" required <?= $view==='register'?'minlength="12" maxlength="72" autocomplete="new-password"':'autocomplete="current-password"' ?>></label><?php if($view==='register') echo Friends\AntiSpam::fields($app) ?><button class="button"><?= $label($view) ?> →</button></form><?php if($view==='login'): ?><p><a href="<?= e($app->url('forgot')) ?>"><?= $label('forgot') ?></a> · <a href="<?= e($app->url('verify')) ?>"><?= $label('resend_verification') ?></a></p><?php endif ?><p class="auth-switch"><?= $label($view==='login'?'no_account':'have_account') ?> <a href="<?= e($app->url($view==='login'?'register':'login')) ?>"><?= $label($view==='login'?'register':'login') ?></a></p></div></section>
<?php break;
case 'feed':
 $app->extensions->emit('feed.before'); ?><div class="section-heading"><h2><?= $label('feed') ?></h2><span><?= $label('latest') ?></span></div><?php require __DIR__.'/posts.php'; break;
case 'profile': ?>
 <?php require __DIR__.'/profile-about.php'; ?>
 <?php require __DIR__.'/posts.php'; break;
case 'people': ?>
 <div class="section-heading"><h1><?= $label('people') ?></h1></div><form method="get" class="card inline-form"><input type="hidden" name="r" value="people"><input name="q" placeholder="<?= $label('search_people') ?>" aria-label="<?= $label('search_people') ?>" value="<?= e($_GET['q']??'') ?>" maxlength="100"><button class="button"><?= $label('search_button') ?></button></form><div class="directory"><?php foreach($people as $person): ?><a class="card person" href="<?= e($app->url('profile',['id'=>$person['id']])) ?>"><?= (new Friends\Features($app))->avatar((int)$person['id'],$person['username']) ?><div><h3><?= e($person['name']) ?></h3><p>@<?= e($person['username']) ?></p><small><?= e($person['bio']) ?></small></div><span>↗</span></a><?php endforeach ?></div><?php if(!$people): ?><p class="card"><?= $label('empty') ?></p><?php endif ?>
<?php break;
case 'groups': case 'pages': ?>
 <div class="section-heading"><h1><?= $label($view) ?></h1></div><p><?= $label('public_communities') ?></p><details class="card create-community"><summary><?= $label($view==='groups'?'create_group':'create_page') ?> +</summary><form method="post" class="stack"><?php $form('community') ?><input type="hidden" name="kind" value="<?= $view==='groups'?'group':'page' ?>"><?php $field('name'); ?><label><?= $label('description') ?><textarea name="description" maxlength="1000"></textarea></label><button class="button"><?= $label('create') ?></button></form></details><div class="directory"><?php foreach($communities as $item): ?><a class="card community-card" href="<?= e($app->url('community',['id'=>$item['id']])) ?>"><div class="community-cover"><?php $communityArt=(new Friends\CommunityImages($app))->images((int)$item['id']); if($communityArt['header']): ?><img src="<?= e($app->url('media',['id'=>$communityArt['header']])) ?>" alt=""><?php endif ?><?= $view==='groups'?'▦':'⚑' ?></div><?php if($communityArt['avatar']): ?><span class="avatar"><img src="<?= e($app->url('media',['id'=>$communityArt['avatar']])) ?>" alt=""></span><?php endif ?><h2><?= e($item['name']) ?></h2><p><?= e($item['description']) ?></p><small><?= (int)$item['members'] ?> <?= $label('members_count') ?> ↗</small></a><?php endforeach ?></div><?php if(!$communities): ?><p class="card"><?= $label('empty') ?></p><?php endif ?>
<?php break;
case 'community': ?>
 <?php require __DIR__.'/community-header.php'; require __DIR__.'/posts.php'; break;
case 'messages': ?>
 <div class="section-heading"><h1><?= $label('messages') ?></h1><span><?= $label('chat_live') ?></span></div><?php require __DIR__.'/voice-room.php'; ?><div class="messenger"><aside class="card contacts"><?php foreach($contacts as $person): ?><a class="contact <?= ($contact['id']??0)===$person['id']?'selected':'' ?>" href="<?= e($app->url('messages',['id'=>$person['id']])) ?>"><span class="avatar small"><?= e(strtoupper(substr($person['username'],0,1))) ?></span><?= e($person['name']) ?></a><?php endforeach ?></aside><section class="card conversation" data-chat-peer="<?= (int)($contact['id']??0) ?>"><?php if(!$contact): ?><p><?= $label('no_contacts') ?></p><?php else: ?><div class="conversation-heading"><h2><?= e($contact['name']) ?></h2></div><div class="message-list" aria-live="polite"><?php foreach($messages as $message): ?><div data-message="<?= (int)$message['id'] ?>" class="bubble <?= (int)$message['sender_id']===(int)$app->user['id']?'mine':'' ?>"><?php if(($message['kind']??'text')==='audio'): ?><audio controls preload="none" src="<?= e($app->url('chat_media',['id'=>$message['id']])) ?>"></audio><?php else: ?><p><?= e($message['body']) ?></p><?php endif ?><small><?= e($message['created_at']) ?> UTC</small></div><?php endforeach ?></div><?php $chatPeer=(int)$contact['id']; require __DIR__.'/chat-controls.php'; ?><?php endif ?></section></div>
<?php break;
case 'notifications': ?>
 <div class="section-heading"><h1><?= $label('notifications') ?></h1><form method="post"><?php $form('seen') ?><button class="text-button"><?= $label('mark_read') ?></button></form></div><section class="card"><?php foreach($notifications as $item): $route=in_array($item['kind'],['like','comment'],true)?'feed':($item['kind']==='message'?'messages':'profile'); ?><a class="notification <?= !$item['seen']?'is-unread':'' ?>" href="<?= e($app->url($route,$route==='feed'?[]:['id'=>$item['target_id']])) ?>"><span class="notification-dot"></span><div><strong><?= e($item['name']) ?></strong> <?= $label('notification_'.$item['kind']) ?><small><?= e($item['created_at']) ?> UTC</small></div><span>↗</span></a><?php endforeach ?><?php if(!$notifications): ?><p><?= $label('empty') ?></p><?php endif ?></section>
<?php break;
case 'settings': ?>
 <div class="section-heading"><h1><?= $label('settings') ?></h1></div><?php require __DIR__.'/settings-profile.php'; ?><section class="card"><h2><?= $label('change_password') ?></h2><form method="post" class="stack"><?php $form('password'); $field('current_password','password'); ?><label><?= $label('new_password') ?><input name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label><button class="button"><?= $label('save') ?></button></form></section>
<?php break;
case 'static': case 'contact': case 'unsubscribe': case 'upgrade': require __DIR__.'/public-pages.php'; break;
case 'admin': require __DIR__.'/admin.php'; break;
endswitch;
