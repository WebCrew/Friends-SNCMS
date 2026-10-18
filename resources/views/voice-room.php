<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
?>
<section class="card voice-room" aria-labelledby="voice-heading">
 <div class="voice-heading"><div><span class="eyebrow">FRIENDS LOUNGE</span><h2 id="voice-heading"><?= $label('voice_room') ?></h2></div><span class="pill"><?= Friends\VoiceRoom::CAPACITY ?> <?= $label('voice_places') ?></span></div>
 <p><?= $label('voice_explanation') ?></p>
 <div class="voice-members" aria-live="polite"></div>
 <p class="voice-status" role="status"><?= $label('voice_not_joined') ?></p>
 <div class="inline-form"><button type="button" class="button voice-join"><?= $label('voice_join') ?></button><button type="button" class="button soft voice-mute" hidden><?= $label('mute') ?></button><button type="button" class="button soft voice-leave" hidden><?= $label('voice_leave') ?></button></div>
 <div class="voice-audio"></div>
</section>
