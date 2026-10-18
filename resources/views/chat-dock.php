<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
$dockContacts=(new Friends\Chat($app))->contacts();
?>
<aside class="chat-dock" aria-label="<?= $label('messages') ?>">
 <?php if($dockContacts): ?><nav class="dock-rail" aria-label="<?= $label('quick_chat') ?>"><?php foreach($dockContacts as $person): ?><button type="button" class="dock-quick-contact" data-peer="<?= (int)$person['id'] ?>" data-name="<?= e($person['name']) ?>" aria-label="<?= e($person['name']) ?>" title="<?= e($person['name']) ?>"><?= (new Friends\Features($app))->avatar((int)$person['id'],$person['username'],'small') ?></button><?php endforeach ?></nav><?php endif ?>
 <section class="dock-friends card" id="dock-friends" hidden aria-labelledby="dock-friends-title">
  <header class="dock-heading"><h2 id="dock-friends-title"><?= $label('messages') ?></h2><button type="button" class="icon-button dock-friends-close" aria-label="<?= $label('close_chat') ?>">×</button></header>
  <label class="dock-search"><?= $label('search_people') ?><input type="search" maxlength="100" class="dock-filter" autocomplete="off"></label>
  <div class="dock-contacts"><?php foreach($dockContacts as $person): ?><button type="button" class="dock-contact" data-peer="<?= (int)$person['id'] ?>" data-name="<?= e($person['name']) ?>"><?= (new Friends\Features($app))->avatar((int)$person['id'],$person['username'],'small') ?><span><?= e($person['name']) ?><small>@<?= e($person['username']) ?></small></span><span aria-hidden="true">↗</span></button><?php endforeach ?></div>
  <p class="dock-no-results" hidden><?= $label('empty') ?></p><?php if(!$dockContacts): ?><p class="dock-empty"><?= $label('no_contacts') ?></p><?php endif ?>
  <a class="dock-full" href="<?= e($app->url('messages')) ?>"><?= $label('open_messenger') ?> ↗</a>
 </section>
 <section class="dock-window card" hidden aria-label="<?= $label('conversation') ?>">
  <header class="dock-heading"><button type="button" class="dock-title text-button" aria-expanded="true"><span class="dock-profile-avatar" aria-hidden="true"></span><span class="dock-name"></span></button><div><button type="button" class="icon-button dock-minimize" aria-label="<?= $label('minimize_chat') ?>">−</button><a class="icon-button dock-expand" href="<?= e($app->url('messages')) ?>" aria-label="<?= $label('open_messenger') ?>">↗</a><button type="button" class="icon-button dock-close" aria-label="<?= $label('close_chat') ?>">×</button></div></header>
  <div class="dock-body"></div>
 </section>
 <template id="dock-conversation"><div class="dock-conversation"><div class="message-list" aria-live="polite"></div><?php $chatPeer=0; require __DIR__.'/chat-controls.php'; ?></div></template>
 <button type="button" class="chat-launcher" aria-label="<?= $label('messages') ?>" aria-controls="dock-friends" aria-expanded="false"><span aria-hidden="true">☏</span><span class="launcher-label"><?= $label('messages') ?></span></button>
 <noscript><a href="<?= e($app->url('messages')) ?>"><?= $label('open_messenger') ?></a></noscript>
</aside>
