<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
switch($view):
case 'static': ?>
 <article class="card document"><span class="eyebrow">FRIENDS / <?= $label('legal_pages') ?></span><h1><?= e($document['title_'.$app->locale]) ?></h1><div class="preserve document-body"><?= Friends\Links::render($document['body_'.$app->locale]) ?></div><small><?= $label('updated') ?> <?= e($document['updated_at']) ?> UTC</small></article>
<?php break;
case 'contact': ?>
 <section class="card document"><h1><?= $label('contact') ?></h1><p><?= $label('contact_help') ?></p><form method="post" class="stack"><?php $form('contact_send'); $field('name','text',$app->user['name']??''); $field('email','email',$app->user['email']??''); ?><label><?= $label('your_message') ?><textarea name="contact_body" required maxlength="5000" rows="8"></textarea></label><?= Friends\AntiSpam::fields($app) ?><label class="checkbox"><input type="checkbox" name="contact_consent" required><?= $label('contact_consent') ?></label><button class="button"><?= $label('send_contact') ?></button></form></section>
<?php break;
case 'unsubscribe': ?>
 <section class="card document"><h1><?= $label('unsubscribe') ?></h1><p><?= $label('unsubscribe_help') ?></p><form method="post"><?php $form('unsubscribe') ?><input type="hidden" name="token" value="<?= e($_GET['token']??'') ?>"><button class="button"><?= $label('unsubscribe') ?></button></form></section>
<?php break;
case 'upgrade': ?>
 <section class="card document"><h1><?= $label('upgrade') ?></h1><p><?= $label('upgrade_help') ?></p><form method="post" class="stack"><?= $app->field() ?><label class="checkbox"><input type="checkbox" name="backup_confirmed" required><?= $label('backup_confirmed') ?></label><button class="button"><?= $label('upgrade') ?></button></form></section>
<?php break; endswitch; ?>
