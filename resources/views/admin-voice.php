<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
use function Friends\e;
$connection=$app->setting('voice_setup',$app->setting('ice_servers','[]')==='[]'?'direct':'advanced');
?>
<section class="card voice-guide"><span class="eyebrow"><?= $label('voice_setup_intro') ?></span><h2><?= $label('voice_setup_title') ?></h2><p><?= $label('voice_setup_help') ?></p>
 <div class="voice-options"><article><h3>1 · <?= $label('voice_direct') ?></h3><p><?= $label('voice_direct_help') ?></p></article><article><h3>2 · <?= $label('voice_stun') ?></h3><p><?= $label('voice_stun_help') ?></p><a class="text-link" href="https://developers.cloudflare.com/realtime/turn/faq/" target="_blank" rel="noopener noreferrer"><?= $label('provider_details') ?> ↗</a></article><article><h3>3 · <?= $label('voice_turn') ?></h3><p><?= $label('voice_turn_help') ?></p></article></div>
 <form method="post" class="stack voice-setup-form"><?php $form('voice_settings') ?>
 <label><?= $label('voice_connection') ?><select name="voice_setup"><?php foreach(['direct'=>'voice_direct','stun'=>'voice_stun','turn'=>'voice_turn','advanced'=>'voice_advanced'] as $mode=>$key): ?><option value="<?= $mode ?>" <?= $connection===$mode?'selected':'' ?>><?= $label($key) ?></option><?php endforeach ?></select></label>
 <fieldset class="turn-fields"><legend><?= $label('voice_turn_fields') ?></legend><p><?= $label('voice_turn_credentials') ?></p>
 <?php $field('turn_host','text',$app->setting('turn_host'),false); $field('turn_port','number',$app->setting('turn_port','3478'),false); ?>
 <label><?= $label('turn_transport') ?><select name="turn_transport"><?php foreach(['udp'=>'UDP','tcp'=>'TCP','tls'=>'TLS / TCP'] as $value=>$text): ?><option value="<?= $value ?>" <?= $app->setting('turn_transport','udp')===$value?'selected':'' ?>><?= $text ?></option><?php endforeach ?></select></label>
 <?php $field('turn_user','text',$app->setting('turn_user'),false); ?><label><?= $label('turn_password') ?><input type="password" name="turn_password" maxlength="500" autocomplete="off" value="<?= e($app->setting('turn_password')) ?>"></label>
 <small><?= $label('turn_tls_help') ?></small></fieldset>
 <details class="voice-advanced"><summary><?= $label('voice_advanced') ?></summary><p><?= $label('voice_advanced_help') ?></p><label><?= $label('ice_servers') ?><textarea name="ice_servers" rows="6" maxlength="10000" spellcheck="false"><?= e($app->setting('ice_servers','[]')) ?></textarea></label></details>
 <button class="button"><?= $label('save') ?></button><small><?= $label('voice_save_help') ?></small></form>
</section>
<section class="card"><h2><?= $label('voice_free_servers') ?></h2><p><?= $label('voice_server_cost') ?></p><div class="voice-options"><article><h3>coturn</h3><p><?= $label('coturn_help') ?></p><a class="button soft" href="https://github.com/coturn/coturn" target="_blank" rel="noopener noreferrer"><?= $label('install_guide') ?> ↗</a></article><article><h3>eturnal</h3><p><?= $label('eturnal_help') ?></p><a class="button soft" href="https://eturnal.net/documentation/" target="_blank" rel="noopener noreferrer"><?= $label('install_guide') ?> ↗</a></article></div><p><?= $label('voice_shared_hosting') ?></p></section>
<section class="card"><h2><?= $label('voice_check_title') ?></h2><ol><li><?= $label('voice_check_one') ?></li><li><?= $label('voice_check_two') ?></li><li><?= $label('voice_check_three') ?></li></ol><p><?= $label('voice_no_relay_needed') ?></p></section>
