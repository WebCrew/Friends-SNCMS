<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
$art=(new Friends\CommunityImages($app))->images((int)$community['id']);
?>
<section class="card community-profile">
 <div class="community-hero"><?php if($art['header']): ?><img src="<?= e($app->url('media',['id'=>$art['header']])) ?>" alt=""><?php endif ?></div>
 <div class="community-identity"><span class="avatar community-logo"><?php if($art['avatar']): ?><img src="<?= e($app->url('media',['id'=>$art['avatar']])) ?>" alt=""><?php else: ?><?= $community['kind']==='group'?'▦':'⚑' ?><?php endif ?></span><div><span class="eyebrow"><?= $label($community['kind']) ?></span><h1><?= e($community['name']) ?></h1></div></div>
 <p class="preserve"><?= e($community['description']) ?></p>
 <?php if((int)$community['owner_id']===(int)$app->user['id']): ?><span class="pill"><?= $label('owner') ?></span><?php else: ?><form method="post"><?php $form('join',(int)$community['id']) ?><button class="button"><?= $label($community['kind']==='page'?($joined?'unfollow':'follow'):($joined?'leave':'join')) ?></button></form><?php endif ?>
</section>
<?php if((int)$community['owner_id']===(int)$app->user['id'] || $app->user['role']==='admin'): ?>
<details class="card community-image-settings"><summary><?= $label('community_images') ?></summary><p><?= $label('community_images_help') ?></p><form method="post" enctype="multipart/form-data" class="stack"><?php $form('community_images',(int)$community['id']) ?>
 <?php foreach(['avatar','header'] as $slot): ?><div class="profile-upload"><label><?= $label($slot==='avatar'?'community_logo':'community_cover') ?><input type="file" name="<?= $slot ?>" accept="image/jpeg,image/png,image/webp" <?= $app->setting('profile_images','1')!=='1'?'disabled':'' ?>></label><?php if($art[$slot]): ?><img class="upload-thumbnail" src="<?= e($app->url('media',['id'=>$art[$slot]])) ?>" alt=""><label class="checkbox"><input type="checkbox" name="remove_<?= $slot ?>"><?= $label('remove_image') ?></label><?php endif ?></div><?php endforeach ?>
 <small><?= $label('profile_image_help') ?></small><button class="button"><?= $label('save') ?></button></form></details>
<?php endif ?>
