<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
?>
<section class="card composer"><form method="post" enctype="multipart/form-data" class="feed-composer" data-max-attachments="<?= max(1,(int)$app->setting('post_attachments','4')) ?>" data-attachment-error="<?= e(sprintf($app->t('attachment_limit'),max(1,(int)$app->setting('post_attachments','4')))) ?>" data-preview-api="<?= e($app->url('link_preview')) ?>" data-error="<?= $label('network_error') ?>">
<?php $form('post') ?><input type="hidden" name="community_id" value="<?= (int)($community['id']??0) ?>">
<div class="composer-top"><?= (new Friends\Features($app))->avatar((int)$app->user['id'],$app->user['username'],'small') ?><textarea name="body" maxlength="5000" rows="3" placeholder="<?= $label('composer') ?>" aria-label="<?= $label('composer') ?>"></textarea></div>
<div class="feed-tools" role="group" aria-label="<?= $label('post_tools') ?>">
<?php if($app->setting('feed_emojis','1')==='1'): ?><details class="feed-emoji-picker"><summary class="composer-tool" aria-label="<?= $label('emojis') ?>" title="<?= $label('emojis') ?>"><span aria-hidden="true">☺</span><span><?= $label('emojis') ?></span></summary><div class="emoji-grid"><?php foreach(['😀','😊','😂','🥰','😎','🤔','😢','❤️','👍','👏','🎉','🔥','☕','🌍','✨','🙏'] as $emoji): ?><button type="button" class="feed-emoji" aria-label="<?= e($emoji) ?>"><?= $emoji ?></button><?php endforeach ?></div></details><?php endif ?>
<?php if($app->setting('feed_images','1')==='1' || $app->setting('feed_files','1')==='1'): ?><label class="attachment-picker composer-tool" title="<?= $label('attachments') ?>"><span aria-hidden="true">⊕</span><span><?= $label('attachments') ?></span><input type="file" name="attachments[]" multiple accept="<?= $app->setting('feed_images','1')==='1'?'image/jpeg,image/png,image/webp,':'' ?><?= $app->setting('feed_files','1')==='1'?'.pdf,.txt,.zip':'' ?>"></label><?php endif ?>

</div>
<?php if($app->setting('link_previews','1')==='1'): ?><div class="auto-link-preview" hidden data-loading="<?= $label('preview_loading') ?>" data-fallback="<?= $label('preview_fallback') ?>"><div class="link-result"></div><small class="preview-status" role="status"></small></div><?php endif ?>
<div class="attachment-previews" aria-live="polite"></div>
<div class="composer-bottom"><label class="audience"><span aria-hidden="true">◎</span><select aria-label="<?= $label('privacy') ?>" name="visibility"><option value="members"><?= $label('members') ?></option><option value="private"><?= $label('private') ?></option></select></label><button class="button"><?= $label('publish') ?> ↗</button></div>
</form></section>
