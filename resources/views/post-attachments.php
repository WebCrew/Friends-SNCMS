<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
?>
<?php if($post['attachments']??[]): ?><div class="post-attachments"><?php foreach($post['attachments'] as $attachment): ?>
<?php if($attachment['mime']==='image/png'): ?><a class="post-image" href="<?= e($app->url('media',['id'=>$attachment['id']])) ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?= e($app->url('media',['id'=>$attachment['id']])) ?>" alt="<?= e($attachment['original_name']) ?>"></a>
<?php else: ?><a class="file-card" href="<?= e($app->url('media',['id'=>$attachment['id']])) ?>"><span>↧</span><strong><?= e($attachment['original_name']) ?></strong><small><?= max(1,(int)ceil($attachment['bytes']/1024)) ?> KB</small></a><?php endif ?>
<?php endforeach ?></div><?php endif ?>
<?php if($post['link']??null): $link=$post['link']; ?><a class="link-card" href="<?= e($link['url']) ?>" target="_blank" rel="ugc noopener noreferrer"><small><?= e(parse_url($link['url'],PHP_URL_HOST)) ?> ↗</small><strong><?= e($link['title']) ?></strong><?php if($link['description']): ?><p><?= e($link['description']) ?></p><?php endif ?></a><?php endif ?>
