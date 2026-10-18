<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
?>
<section class="card profile-card">
<div class="profile-cover"><?php if($about['header_id']): ?><img src="<?= e($app->url('media',['id'=>$about['header_id']])) ?>" alt="" class="cover-image"><?php endif ?></div>
<div class="profile-main"><?= (new Friends\Features($app))->avatar((int)$profile['id'],$profile['username'],'large') ?><div><h1><?= e($profile['name']) ?></h1><p>@<?= e($profile['username']) ?></p></div></div>
<div class="about-section"><h2><?= $label('about_section') ?></h2><p class="preserve"><?= e($profile['bio']) ?></p><div class="profile-links"><?php foreach(json_decode($about['links'],true)?:[] as $link): ?><a class="profile-link" href="<?= e($link['url']) ?>" target="_blank" rel="ugc noopener noreferrer"><?= e($link['label']) ?> ↗<small><?= e(parse_url($link['url'],PHP_URL_HOST)) ?></small></a><?php endforeach ?></div></div>
<small><?= $label('member_since') ?> <?= e(substr($profile['created_at'],0,10)) ?></small>
<?php if((int)$profile['id']===(int)$app->user['id']): ?><p><a class="button soft" href="<?= e($app->url('settings')) ?>"><?= $label('edit_profile') ?></a></p>
<?php else: ?><form method="post" class="inline-form"><?php $form('friend',(int)$profile['id']); if(!$friendship): ?><button class="button"><?= $label('connect') ?></button><?php else: ?><?php if($friendship['status']==='pending' && (int)$friendship['requester_id']!==(int)$app->user['id']): ?><button class="button" name="choice" value="accept"><?= $label('accept') ?></button><?php endif ?><span class="pill"><?= $label($friendship['status']==='accepted'?'friend':'request_sent') ?></span><button class="text-button" name="choice" value="remove"><?= $label('cancel') ?></button><?php endif ?></form><?php endif ?>
</section>
