<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
?>
<section class="card"><p><?= $label('account_mail_help') ?></p><p><?= $label('mail_help') ?></p><form method="post" class="stack"><?php $form('mail_settings') ?>
<label><?= $label('mail_transport') ?><select name="mail_transport"><?php foreach(['off','system','smtp'] as $value): ?><option value="<?= $value ?>" <?= $app->setting('mail_transport','off')===$value?'selected':'' ?>><?= $label('mail_'.$value) ?></option><?php endforeach ?></select></label>
<?php $field('mail_from','email',$app->setting('mail_from'),false); $field('contact_to','email',$app->setting('contact_to'),false); $field('public_url','url',$app->setting('public_url'),false); ?>
<h3>SMTP</h3><?php $field('smtp_host','text',$app->setting('smtp_host'),false); $field('smtp_port','number',$app->setting('smtp_port','587')); ?>
<label><?= $label('smtp_security') ?><select name="smtp_security"><option value="starttls" <?= $app->setting('smtp_security')!=='tls'?'selected':'' ?>>STARTTLS</option><option value="tls" <?= $app->setting('smtp_security')==='tls'?'selected':'' ?>>TLS</option></select></label>
<?php $field('smtp_user','text',$app->setting('smtp_user'),false); ?>
<label><?= $label('smtp_password') ?><input type="password" name="smtp_password" autocomplete="new-password" maxlength="500"></label><label class="checkbox"><input type="checkbox" name="clear_smtp_password"><?= $label('clear_smtp_password') ?></label>
<?php $checkbox('contact_enabled','0'); $checkbox('newsletter_enabled','0'); ?><button class="button"><?= $label('save') ?></button></form></section>
<section class="card"><h3><?= $label('mail_test') ?></h3><p><?= $label('mail_test_help') ?> <?= e($app->user['email']) ?></p><form method="post"><?php $form('mail_test') ?><button class="button soft"><?= $label('mail_test') ?></button></form></section>
<section class="card"><h3><?= $label('newsletter') ?></h3><p><?= $label('newsletter_help') ?></p><form method="post" class="stack"><?php $form('newsletter_create'); $field('subject'); ?><label><?= $label('page_body') ?><textarea name="mail_body" rows="8" maxlength="20000" required></textarea></label><button class="button"><?= $label('prepare_newsletter') ?></button></form></section>
<section class="card"><h3><?= $label('mail_queue') ?></h3><p><?= $label('queue_help') ?></p>
<?php foreach($campaigns as $campaign): ?><div class="campaign"><strong><?= e($campaign['subject']) ?></strong><p><?= e(iconv_substr($campaign['body'],0,200,'UTF-8')) ?></p><small><?= e($campaign['created_at']) ?> UTC</small><div class="queue-status"><?php foreach(($queueStatus[$campaign['id']]??[]) as $status): ?><span class="pill"><?= $label('queue_'.$status['status']) ?> <?= (int)$status['total'] ?></span><?php endforeach ?></div><form method="post"><?php $form('newsletter_cancel',(int)$campaign['id']) ?><button class="text-button"><?= $label('cancel_pending') ?></button></form></div><?php endforeach ?>
<form method="post" data-confirm="<?= $label('send_batch_confirm') ?>"><?php $form('newsletter_send') ?><button class="button"><?= $label('send_batch') ?></button></form></section>
