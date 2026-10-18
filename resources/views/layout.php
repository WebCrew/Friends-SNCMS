<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
$nav=['feed'=>'◉','people'=>'♧','groups'=>'▦','pages'=>'⚑','messages'=>'✉','notifications'=>'♧'];
$unread=$app->user?(int)$app->db->value('SELECT COUNT(*) FROM f_notifications WHERE user_id=? AND seen=0',[$app->user['id']]):0;
?>
<!doctype html>
<html lang="<?= e($app->locale) ?>" data-palette="<?= e($app->setting('theme','friends')) ?>">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
 <meta name="author" content="Andreas Holzer @WebCrew (GitHub)"><meta name="copyright" content="Copyright © 2026 Andreas Holzer">
 <meta name="description" content="Friends-SNCMS — Independent community software by Andreas Holzer @WebCrew (GitHub)">
 <title><?= e($app->t($app->route)) ?> · <?= e($app->setting('site_name','Friends-SNCMS')) ?></title>
 <link rel="icon" href="<?= e(FRIENDS_ASSET_PREFIX) ?>mark.svg" type="image/svg+xml"><link rel="stylesheet" href="<?= e(FRIENDS_ASSET_PREFIX) ?>app.css"><link rel="stylesheet" href="<?= e(FRIENDS_ASSET_PREFIX) ?>features.css"><link rel="stylesheet" href="<?= e(FRIENDS_ASSET_PREFIX) ?>refinements.css?v=1.0.0"><?php if($app->db && $app->setting('schema_version','3')==='3'): ?><link rel="stylesheet" href="<?= e($app->url('theme_css')) ?>"><?php endif ?><script type="module" src="<?= e(FRIENDS_ASSET_PREFIX) ?>link-preview.js?v=1.0.0"></script><script src="<?= e(FRIENDS_ASSET_PREFIX) ?>app.js" defer></script><script src="<?= e(FRIENDS_ASSET_PREFIX) ?>features.js?v=1.0.0" defer></script><?php if($app->user): ?><script src="<?= e(FRIENDS_ASSET_PREFIX) ?>chat.js" defer></script><script src="<?= e(FRIENDS_ASSET_PREFIX) ?>voice-room.js" defer></script><?php endif ?>
</head>
<body>
<?php if (getenv('FRIENDS_PREVIEW')==='1'): ?><div class="preview-banner"><?= e($app->t('preview')) ?></div><?php endif ?>
<header class="topbar">
 <a class="brand" href="<?= e($app->url()) ?>"><span class="brand-mark">f<span>●</span></span><span>friends<span class="brand-sub">SNCMS</span></span></a>
 <?php if ($app->user): ?><form class="search" method="get"><input type="hidden" name="r" value="feed"><span aria-hidden="true">⌕</span><input aria-label="<?= e($app->t('search')) ?>" name="q" maxlength="100" placeholder="<?= e($app->t('search')) ?>" value="<?= e($_GET['q']??'') ?>"><button class="icon-button" aria-label="<?= e($app->t('search_button')) ?>">↵</button></form><?php else: ?><span class="tagline"><?= e($app->t('tagline')) ?></span><?php endif ?>
 <div class="top-actions"><button type="button" class="icon-button mode-button" aria-label="<?= e($app->t('appearance')) ?>" title="<?= e($app->t('appearance')) ?>">◐</button>
 <form method="post" class="language-form"><?= $app->field() ?><input type="hidden" name="action" value="language"><button name="locale" value="<?= $app->locale==='de'?'en':'de' ?>" class="text-button" aria-label="<?= e($app->t('language')) ?>"><?= $app->locale==='de'?'EN':'DE' ?></button></form>
 <?php if ($app->user): ?><a class="profile-shortcut" aria-label="<?= e($app->t('profile')) ?>" href="<?= e($app->url('profile')) ?>"><?= (new Friends\Features($app))->avatar((int)$app->user['id'],$app->user['username'],'small') ?></a><?php endif ?></div>
</header>
<div class="shell <?= !$app->user?'guest':'' ?>">
<?php if ($app->user): ?><aside class="sidebar"><a class="identity" href="<?= e($app->url('profile')) ?>"><?= (new Friends\Features($app))->avatar((int)$app->user['id'],$app->user['username']) ?><span><strong><?= e($app->user['name']) ?></strong><small>@<?= e($app->user['username']) ?></small></span></a><p class="nav-label"><?= e($app->t('community')) ?></p><nav aria-label="<?= e($app->t('community')) ?>">
 <?php foreach($nav as $route=>$icon): ?><a class="nav-item <?= $app->route===$route?'selected':'' ?>" href="<?= e($app->url($route)) ?>"><span class="nav-icon" aria-hidden="true"><?= $icon ?></span><?= e($app->t($route)) ?><?php if($route==='notifications' && $unread): ?><span class="badge"><?= $unread ?></span><?php endif ?></a><?php endforeach ?>
 <p class="nav-label"><?= e($app->t('personal')) ?></p><a class="nav-item <?= $app->route==='settings'?'selected':'' ?>" href="<?= e($app->url('settings')) ?>"><span class="nav-icon">⚙</span><?= e($app->t('settings')) ?></a>
 <?php if($app->user['role']==='admin'): ?><a class="nav-item <?= $app->route==='admin'?'selected':'' ?>" href="<?= e($app->url('admin')) ?>"><span class="nav-icon">▤</span><?= e($app->t('admin')) ?></a><?php endif ?>
 </nav><form method="post" class="logout"><?= $app->field() ?><button name="action" value="logout" class="text-button">↗ <?= e($app->t('logout')) ?></button></form><div class="sidebar-foot"><strong>Friends-SNCMS <span class="version">1.0.0</span></strong><p><?= e($app->t('project')) ?></p><a href="https://github.com/WebCrew" rel="noreferrer">Andreas Holzer · @WebCrew</a></div></aside><?php endif ?>
<main id="main">
<?php if($error): ?><div role="alert" class="notice error"><?= e($error) ?></div><?php endif ?>
<?php if($success): ?><div role="status" class="notice success"><?= e($success) ?></div><?php endif ?>
<?php require FRIENDS_ROOT.'/resources/views/content.php' ?>
</main>
<?php if($app->user && in_array($view,['feed','profile','community'],true)): ?><aside class="rightbar"><section class="card mini-card"><span class="eyebrow">FRIENDS-SNCMS</span><h2><?= e($app->t('tagline')) ?></h2><p><?= e($app->t('intro_text')) ?></p><a class="button soft" href="<?= e($app->url('people')) ?>"><?= e($app->t('people')) ?> ↗</a></section><section class="card mini-card"><h3><?= e($app->t('groups')) ?></h3><?php foreach($app->db->all("SELECT id,name FROM f_communities WHERE kind='group' ORDER BY id DESC LIMIT 4") as $group): ?><a class="community-link" href="<?= e($app->url('community',['id'=>$group['id']])) ?>"><span class="tile">▦</span><?= e($group['name']) ?><span>↗</span></a><?php endforeach ?><a class="text-link" href="<?= e($app->url('groups')) ?>"><?= e($app->t('more')) ?> →</a></section><p class="fineprint"><?= e($app->t('foundation')) ?></p></aside><?php endif ?>
</div><footer class="footer"><?= e($app->t('copyright')) ?> · Guided-CMS <?= e($app->t('in_development')) ?><?php if($app->db && $app->setting('schema_version','3')==='3'): ?><nav class="footer-links" aria-label="<?= e($app->t('legal_pages')) ?>"><?php foreach($app->db->all('SELECT slug,title_de,title_en FROM f_documents WHERE published=1 ORDER BY slug LIMIT 30') as $page): ?><a href="<?= e($app->url('static',['slug'=>$page['slug']])) ?>"><?= e($page['title_'.$app->locale]) ?></a><?php endforeach ?><?php if($app->setting('contact_enabled')==='1'): ?><a href="<?= e($app->url('contact')) ?>"><?= e($app->t('contact')) ?></a><?php endif ?></nav><?php endif ?></footer>
<?php if($app->user): $chatKeys=['mute','unmute','microphone_error','audio_unsupported','recording','record_audio','stop_recording','send_audio','discard','audio_upload_error','network_error','finish_audio','voice_room','voice_join','voice_leave','voice_not_joined','voice_connected','voice_connecting','voice_failed','voice_requesting','voice_you','voice_muted','voice_empty','voice_reconnect_audio']; $chatStrings=[]; foreach($chatKeys as $key) $chatStrings[$key]=$app->t($key); ?>
<div id="chat-root" data-messages="<?= e($app->url('messages')) ?>" data-api="<?= e($app->url('chat_api')) ?>" data-voice-api="<?= e($app->url('voice_api')) ?>" data-token="<?= e($app->csrf()) ?>" data-user="<?= (int)$app->user['id'] ?>" data-peer="<?= (int)($contact['id']??0) ?>" data-strings="<?= e(json_encode($chatStrings,JSON_THROW_ON_ERROR)) ?>">
 <p class="chat-toast" role="status" hidden></p>
</div><?php require __DIR__.'/chat-dock.php'; ?>
<?php endif ?>
</body></html>

