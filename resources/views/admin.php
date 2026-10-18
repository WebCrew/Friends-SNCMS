<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
$sections=['overview','site_settings','uploads','member_management','legal_pages','mail_settings','extensions','themes','voice_room','open_reports','audit'];
$section=(string)($_GET['section']??'overview');
if (!in_array($section,$sections,true)) $section='overview';
$checkbox=static function(string $key,string $default='1') use($app,$label): void { ?>
 <label class="checkbox"><input type="checkbox" name="<?= e($key) ?>" <?= $app->setting($key,$default)==='1'?'checked':'' ?>><?= $label($key) ?></label>
<?php };
?>
<div class="section-heading"><div><span class="eyebrow">FRIENDS CONTROL ROOM</span><h1><?= $label('admin') ?></h1></div><span class="pill">v1.0.0</span></div>
<nav class="admin-nav" aria-label="<?= $label('admin_navigation') ?>"><?php foreach($sections as $item): ?><a class="<?= $section===$item?'selected':'' ?>" href="<?= e($app->url('admin',['section'=>$item])) ?>" <?= $section===$item?'aria-current="page"':'' ?>><?= $label($item) ?></a><?php endforeach ?></nav>
<div class="section-heading"><h2><?= $label($section) ?></h2></div>
<?php switch($section):
case 'overview': ?>
 <div class="metrics"><?php foreach($counts as $key=>$value): ?><div class="card metric"><span><?= $label($key) ?></span><strong><?= $value ?></strong></div><?php endforeach ?></div>
 <section class="card"><h2><?= $label('overview_help') ?></h2><p><?= $label('admin_intro') ?></p></section>
<?php break;
case 'site_settings': ?>
 <section class="card"><form method="post" class="stack"><?php $form('admin_settings'); $field('site_name','text',$app->setting('site_name')); ?><input type="hidden" name="theme" value="<?= e($app->setting('theme','friends')) ?>"><?php $checkbox('registration') ?><button class="button"><?= $label('save') ?></button></form></section>
 <section class="card"><h2><?= $label('spam_protection') ?></h2><p><?= $label('spam_help') ?></p><form method="post" class="stack"><?php $form('admin_spam'); $checkbox('spam_protection') ?><button class="button"><?= $label('save') ?></button></form></section>
<?php break;
case 'uploads': ?>
 <section class="card"><p><?= $label('upload_help') ?></p><form method="post" class="stack"><?php $form('admin_uploads'); foreach(['feed_emojis','feed_images','feed_files','link_previews','profile_images'] as $key) $checkbox($key); ?>
 <label><?= $label('upload_mb') ?><input type="number" name="upload_mb" min="1" required value="<?= e($app->setting('upload_mb','5')) ?>"></label><label><?= $label('post_attachments') ?><input type="number" name="post_attachments" min="1" required value="<?= e($app->setting('post_attachments','4')) ?>"></label><p class="fineprint"><?= $label('upload_host_hint') ?> <?= e('upload_max_filesize: '.ini_get('upload_max_filesize').' · post_max_size: '.ini_get('post_max_size').' · max_file_uploads: '.ini_get('max_file_uploads')) ?></p><label><?= $label('upload_daily_mb') ?><input type="number" name="upload_daily_mb" min="10" max="500" required value="<?= e($app->setting('upload_daily_mb','100')) ?>"></label><button class="button"><?= $label('save') ?></button></form></section>
 <section class="card"><h3><?= $label('server_capabilities') ?></h3><?php foreach(['gd','fileinfo','curl','zip','openssl'] as $extension): ?><div class="check-row"><span><?= e($extension) ?></span><span><?= extension_loaded($extension)?'✓':'×' ?></span></div><?php endforeach ?><p><?= $label('capabilities_help') ?></p></section>
<?php break;
case 'member_management':
 $person=$memberToEdit; ?>
 <form class="card inline-form" method="get"><input type="hidden" name="r" value="admin"><input type="hidden" name="section" value="member_management"><input name="q" value="<?= e($search) ?>" aria-label="<?= $label('search_people') ?>" placeholder="<?= $label('search_people') ?>"><button class="button"><?= $label('search_button') ?></button></form>
 <?php if($person): ?><section class="card"><h3><?= $label('edit') ?> · @<?= e($person['username']) ?></h3><form method="post" class="stack"><?php $form('admin_member',(int)$person['id']); $field('name','text',$person['name']); $field('email','email',$person['email']); ?><label><?= $label('bio') ?><textarea name="bio" maxlength="1000"><?= e($person['bio']) ?></textarea></label>
 <label><?= $label('language') ?><select name="locale"><?php foreach(['de'=>'Deutsch','en'=>'English'] as $value=>$title): ?><option value="<?= $value ?>" <?= $person['locale']===$value?'selected':'' ?>><?= $title ?></option><?php endforeach ?></select></label>
 <label><?= $label('role') ?><select name="role"><?php foreach(['member','admin'] as $value): ?><option value="<?= $value ?>" <?= $person['role']===$value?'selected':'' ?>><?= $label('role_'.$value) ?></option><?php endforeach ?></select></label>
 <label><?= $label('status') ?><select name="status"><?php foreach(['active','pending','suspended'] as $value): ?><option value="<?= $value ?>" <?= $person['status']===$value?'selected':'' ?>><?= $label($value) ?></option><?php endforeach ?></select></label>
 <label><?= $label('role_password_help') ?><input type="password" name="current_password" autocomplete="current-password"></label><p><?= $label('member_edit_help') ?></p><button class="button"><?= $label('save') ?></button></form></section><?php endif ?>
 <section class="card table-wrap"><table><thead><tr><th><?= $label('name') ?></th><th><?= $label('account') ?></th><th><?= $label('edit') ?></th></tr></thead><tbody><?php foreach($members as $member): ?><tr><td><a href="<?= e($app->url('profile',['id'=>$member['id']])) ?>"><?= e($member['name']) ?></a><small>@<?= e($member['username']) ?></small></td><td><?= $label('role_'.$member['role']) ?><small><?= $label($member['status']) ?></small></td><td><a class="button soft" href="<?= e($app->url('admin',['section'=>$section,'edit'=>$member['id']])) ?>"><?= $label('edit') ?></a></td></tr><?php endforeach ?></tbody></table></section>
<?php break;
case 'legal_pages':
 $document=$documentToEdit; ?>
 <section class="card"><p><?= $label('legal_help') ?></p><form method="post" class="stack"><?php $form('document_save',(int)($document['id']??0)); $field('slug','text',$document['slug']??''); ?>
 <?php foreach(['de'=>'Deutsch','en'=>'English'] as $code=>$language): ?><h3><?= $language ?></h3><label><?= $label('title') ?><input name="title_<?= $code ?>" required maxlength="190" value="<?= e($document['title_'.$code]??'') ?>"></label><label><?= $label('page_body') ?><textarea name="body_<?= $code ?>" required rows="10" maxlength="50000"><?= e($document['body_'.$code]??'') ?></textarea></label><?php endforeach ?>
 <label class="checkbox"><input type="checkbox" name="published" <?= ($document['published']??0)?'checked':'' ?>><?= $label('published') ?></label><button class="button"><?= $label('save') ?></button></form></section>
 <section class="card"><?php foreach($documents as $item): ?><div class="extension-row"><div><strong><?= e($item['title_'.$app->locale]) ?></strong><small><?= e($item['slug']) ?> · <?= $label($item['published']?'published':'draft') ?></small></div><div class="inline-form"><a class="button soft" href="<?= e($app->url('admin',['section'=>$section,'edit'=>$item['id']])) ?>"><?= $label('edit') ?></a><?php if($item['published']): ?><a class="text-link" href="<?= e($app->url('static',['slug'=>$item['slug']])) ?>"><?= $label('view_page') ?></a><form method="post"><?php $form('document_delete',(int)$item['id']) ?><button class="text-button"><?= $label('unpublish') ?></button></form><?php endif ?></div></div><?php endforeach ?><a class="text-link" href="<?= e($app->url('admin',['section'=>$section])) ?>"><?= $label('new_page') ?> +</a></section>
<?php break;
case 'mail_settings': require __DIR__.'/admin-mail.php'; break;
case 'extensions': case 'themes':
 $type=$section==='extensions'?'plugins':'themes'; ?>
 <section class="card"><h3><?= $label('package_upload') ?></h3><p><?= $label('package_help') ?></p><form method="post" enctype="multipart/form-data" class="stack"><?php $form('package_install') ?><input type="hidden" name="type" value="<?= $type ?>"><label><?= $label('package_zip') ?><input type="file" name="package" accept=".zip" required></label><label><?= $label('current_password') ?><input type="password" name="current_password" autocomplete="current-password" required></label><label class="checkbox"><input type="checkbox" name="trust_package" required><?= $label('package_trust') ?></label><button class="button"><?= $label('install_package') ?></button></form></section>
 <section class="card"><?php foreach($app->extensions->discover($type) as $id=>$manifest): $enabled=$type==='plugins'?$app->setting('plugin.'.$id,'0')==='1':$app->setting('theme.'.$id,'1')==='1'; ?>
 <div class="extension-row"><div><h3><?= e($manifest['name']) ?> <small><?= e($manifest['version']) ?></small></h3><p><?= e($manifest['description'][$app->locale]??'') ?></p><small><?= e($manifest['author']) ?> · <?= $label($enabled?'enabled':'disabled') ?><?= $type==='themes' && $app->setting('theme')===$id?' · '.$label('theme'):'' ?></small></div>
 <?php if($type==='plugins'): ?><form method="post" class="stack"><?php $form('plugin') ?><input type="hidden" name="plugin" value="<?= e($id) ?>"><?php if(!$enabled && $app->setting('installed.plugins.'.$id)==='1'): ?><label><?= $label('current_password') ?><input type="password" name="current_password" autocomplete="current-password" required></label><label class="checkbox"><input type="checkbox" name="trust_package" required><?= $label('package_trust') ?></label><?php endif ?><button class="button soft"><?= $label($enabled?'disable':'enable') ?></button></form>
 <?php else: ?><div class="stack"><?php if($enabled && $app->setting('theme')!==$id): ?><form method="post"><?php $form('admin_settings') ?><input type="hidden" name="site_name" value="<?= e($app->setting('site_name')) ?>"><input type="hidden" name="theme" value="<?= e($id) ?>"><?php if($app->setting('registration','1')==='1'): ?><input type="hidden" name="registration" value="1"><?php endif ?><button class="button"><?= $label('activate') ?></button></form><?php endif ?>
 <?php if($id!=='friends'): ?><form method="post"><?php $form('theme_toggle') ?><input type="hidden" name="theme" value="<?= e($id) ?>"><button class="button soft"><?= $label($enabled?'disable':'enable') ?></button></form><?php endif ?></div><?php endif ?></div><?php endforeach ?></section>
<?php break;
case 'voice_room': require __DIR__.'/admin-voice.php'; break;
case 'open_reports': ?>
 <section class="card"><?php foreach($reports as $report): ?><div class="report"><small>#<?= (int)$report['id'] ?> · <?= e($report['name']) ?></small><p><?= e($report['reason']) ?></p><blockquote><?= e($report['body']) ?></blockquote><form method="post"><?php $form('resolve',(int)$report['id']) ?><button class="button soft"><?= $label('resolve') ?></button></form></div><?php endforeach ?><?php if(!$reports): ?><p><?= $label('empty') ?></p><?php endif ?></section>
<?php break;
case 'audit': ?>
 <section class="card"><?php foreach($audit as $entry): ?><div class="audit-entry"><?= e($entry['name']) ?> · <?= e($entry['action']) ?> #<?= (int)$entry['target_id'] ?><small><?= e($entry['created_at']) ?> UTC</small></div><?php endforeach ?></section>
<?php break; endswitch; ?>

