<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
$about=(new Friends\Features($app))->profile((int)$app->user['id']);
$links=json_decode($about['links'],true)?:[];
?>
<section class="card"><h2><?= $label('account') ?></h2><form method="post" class="stack" enctype="multipart/form-data"><?php $form('profile'); $field('name','text',$app->user['name']); ?>
<label><?= $label('bio') ?><textarea name="bio" maxlength="1000"><?= e($app->user['bio']) ?></textarea></label>
<label><?= $label('profile_links') ?><textarea name="profile_links" rows="5" maxlength="2500" placeholder="GitHub | https://github.com/your-name"><?= e(implode("\n",array_map(static fn($link)=>$link['label'].' | '.$link['url'],$links))) ?></textarea><small><?= $label('profile_links_help') ?></small></label>
<?php foreach(['avatar','header'] as $slot): ?><div class="profile-upload"><label><?= $label($slot.'_image') ?><input type="file" name="<?= $slot ?>" accept="image/jpeg,image/png,image/webp" <?= $app->setting('profile_images','1')!=='1'?'disabled':'' ?>></label><?php if($about[$slot.'_id']): ?><img class="upload-thumbnail" src="<?= e($app->url('media',['id'=>$about[$slot.'_id']])) ?>" alt="<?= $label($slot.'_image') ?>"><label class="checkbox"><input type="checkbox" name="remove_<?= $slot ?>"><?= $label('remove_image') ?></label><?php endif ?></div><?php endforeach ?>
<small><?= $label('profile_image_help') ?></small>
<label><?= $label('language') ?><select name="locale"><option value="de" <?= $app->locale==='de'?'selected':'' ?>>Deutsch</option><option value="en" <?= $app->locale==='en'?'selected':'' ?>>English</option></select></label>
<?php if($app->setting('newsletter_enabled')==='1'): ?><label class="checkbox"><input type="checkbox" name="newsletter" <?= $about['newsletter']?'checked':'' ?>><?= $label('newsletter_consent') ?></label><?php endif ?>
<button class="button"><?= $label('save') ?></button></form></section>
