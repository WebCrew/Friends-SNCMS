<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$directory = sys_get_temp_dir() . '/friends-room-test-' . bin2hex(random_bytes(6));
mkdir($directory);
putenv('FRIENDS_CONFIG=' . $directory . '/config.php');
require dirname(__DIR__) . '/app/bootstrap.php';

use Friends\{App, Db, VoiceRoom};

function runRoomChecks(string $directory): void
{
    $config = ['dsn' => 'sqlite:' . $directory . '/room.sqlite'];
    $db = new Db($config); $db->migrate();
    file_put_contents(FRIENDS_CONFIG, '<?php return ' . var_export($config, true) . ';');
    foreach (['site_name' => 'Room fixture', 'secret' => bin2hex(random_bytes(32)), 'theme' => 'friends'] as $key => $value) {
        $db->query('INSERT INTO f_settings (name,value) VALUES (?,?)', [$key, $value]);
    }
    for ($uid = 1; $uid <= 7; $uid++) {
        $db->query('INSERT INTO f_users (username,email,password,name,bio,created_at) VALUES (?,?,?,?,?,?)', ['person' . $uid, 'person' . $uid . '@example.test', 'not-a-login-fixture', 'Person ' . $uid, '', gmdate('Y-m-d H:i:s')]);
    }
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $binding = hash('sha256', (string) $db->value("SELECT value FROM f_settings WHERE name='secret'"));
    $rooms = [];
    for ($uid = 0; $uid <= 7; $uid++) {
        $_SESSION = ['user_id' => $uid, 'locale' => 'en', 'installation' => $binding]; $_GET = ['r' => 'messages'];
        $rooms[$uid] = new VoiceRoom(new App());
    }
    $count = 0;
    $check = static function (bool $condition, string $label) use (&$count): void {
        if (!$condition) throw new RuntimeException('FAIL: ' . $label);
        $count++; echo 'PASS ' . $label . "\n";
    };
    $act = static function (int $uid, string $action, array $fields = []) use ($rooms): array {
        $_POST = $fields; return $rooms[$uid]->handle($action);
    };
    $denied = static function (callable $callback, string $label) use ($check): void {
        try { $callback(); } catch (DomainException) { $check(true, $label); return; }
        $check(false, $label);
    };
    $description = ['type' => 'offer', 'sdp' => "v=0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111\r\n"];
    $candidate = ['candidate' => 'candidate:123 1 UDP 1 127.0.0.1 1234 typ host', 'sdpMid' => '0', 'sdpMLineIndex' => 0];

    $denied(fn() => $act(0, 'state'), 'guest cannot access room state');
    $state = $act(1, 'state');
    $check(!$state['members'] && $state['capacity'] === 6, 'empty shared room has bounded capacity');
    $a = $act(1, 'join');
    $check(count($a['members']) === 1 && strlen($a['voice_token']) === 64, 'explicit join creates one room session');
    $check($db->value('SELECT token_hash FROM f_voice_members WHERE user_id=1') === hash('sha256', $a['voice_token']), 'only hashed room token is stored');
    $check(!array_key_exists('token_hash', $a['members'][0]) && !array_key_exists('voice_token', $a['members'][0]), 'member listing does not expose session credentials');
    $denied(fn() => $act(1, 'join'), 'same account cannot join twice in separate tabs');
    $denied(fn() => $act(2, 'sync', ['voice_token' => $a['voice_token']]), 'another account cannot reuse room token');
    $denied(fn() => $act(1, 'sync', ['voice_token' => str_repeat('0', 64)]), 'invalid room token rejected');
    $b = $act(2, 'join');
    $check(count($b['members']) === 2 && !$db->value('SELECT COUNT(*) FROM f_friendships'), 'shared room accepts members without a private friend invitation');
    $send = static fn(int $uid, array $from, array $to, string $kind, array $payload): array => $act($uid, 'signal', ['voice_token' => $from['voice_token'], 'target_session' => $to['session_id'], 'kind' => $kind, 'payload' => json_encode($payload)]);
    $send(1, $a, $b, 'offer', $description);
    $sync = $act(2, 'sync', ['voice_token' => $b['voice_token']]);
    $check(count($sync['signals']) === 1 && $sync['signals'][0]['kind'] === 'offer', 'room offer reaches the intended joined member');
    $check(!$act(1, 'sync', ['voice_token' => $a['voice_token']])['signals'], 'sender does not receive own signaling');
    $denied(fn() => $act(3, 'sync', ['voice_token' => $a['voice_token']]), 'unjoined member cannot read signaling');
    $denied(fn() => $send(2, $b, $a, 'offer', $description), 'offer direction prevents simultaneous negotiation glare');
    $answer = $description; $answer['type'] = 'answer';
    $send(2, $b, $a, 'answer', $answer);
    $check($act(1, 'sync', ['voice_token' => $a['voice_token']])['signals'][0]['kind'] === 'answer', 'room answer reaches the initiating browser');
    $send(1, $a, $b, 'candidate', $candidate);
    $sync = $act(2, 'sync', ['voice_token' => $b['voice_token'], 'after' => $sync['signals'][0]['id']]);
    $check(count($sync['signals']) === 1 && $sync['signals'][0]['kind'] === 'candidate', 'signaling cursor delivers only new ICE data');
    $invalid = $candidate + ['script' => 'bad'];
    $denied(fn() => $send(1, $a, $b, 'candidate', $invalid), 'unexpected candidate fields rejected');
    $invalidIndex = $candidate; $invalidIndex['sdpMLineIndex'] = ['invalid'];
    $denied(fn() => $send(1, $a, $b, 'candidate', $invalidIndex), 'non-integer candidate index rejected');
    $invalidMid = $candidate; $invalidMid['sdpMid'] = str_repeat('x', 257);
    $denied(fn() => $send(1, $a, $b, 'candidate', $invalidMid), 'oversized candidate identifier rejected');
    $video = $description; $video['sdp'] .= "m=video 9 RTP/AVP 0\r\n";
    $denied(fn() => $send(1, $a, $b, 'offer', $video), 'video media rejected by audio-only room');
    $dataChannel = $description; $dataChannel['sdp'] .= "m=application 9 UDP/DTLS/SCTP webrtc-datachannel\r\n";
    $denied(fn() => $send(1, $a, $b, 'offer', $dataChannel), 'data channel rejected by audio-only room');
    $c = $act(3, 'join');
    $send(1, $a, $c, 'offer', $description); $send(2, $b, $c, 'offer', $description);
    $check(count($act(3, 'sync', ['voice_token' => $c['voice_token']])['signals']) === 2, 'third member receives both peer negotiations');
    $muted = $act(2, 'sync', ['voice_token' => $b['voice_token'], 'muted' => '1']);
    $check((int) array_column($muted['members'], 'muted', 'user_id')[2] === 1, 'local mute status is reflected in participant list');
    $act(3, 'leave', ['voice_token' => $c['voice_token']]);
    $check(!$db->value('SELECT COUNT(*) FROM f_voice_signals WHERE sender_session=? OR recipient_session=?', [$c['session_id'], $c['session_id']]), 'leaving removes related signaling by cascade');
    $denied(fn() => $act(3, 'sync', ['voice_token' => $c['voice_token']]), 'left session cannot resume signaling');
    $c2 = $act(3, 'join');
    $check($c2['session_id'] !== $c['session_id'] && $c2['voice_token'] !== $c['voice_token'], 'rejoin creates fresh identity and token');
    $denied(fn() => $send(1, $a, $c, 'candidate', $candidate), 'signals cannot target an old browser session');
    for ($uid = 4; $uid <= 6; $uid++) $joined[$uid] = $act($uid, 'join');
    $denied(fn() => $act(7, 'join'), 'seventh participant is rejected');
    $db->query('UPDATE f_voice_members SET last_seen=? WHERE user_id=2', [time() - VoiceRoom::TIMEOUT - 1]);
    $seventh = $act(7, 'join');
    $check(count($seventh['members']) === 6 && !$db->one('SELECT user_id FROM f_voice_members WHERE user_id=2'), 'stale heartbeat releases a room place');
    $denied(fn() => $act(2, 'sync', ['voice_token' => $b['voice_token']]), 'expired browser session rejected');
    $db->query("UPDATE f_users SET status='suspended' WHERE id=3"); $act(4, 'state');
    $check(!$db->one('SELECT user_id FROM f_voice_members WHERE user_id=3'), 'suspended member removed from active room');
    $denied(fn() => $act(3, 'join'), 'cached pre-suspension account cannot rejoin');
    $db->query('UPDATE f_users SET auth_version=auth_version+1 WHERE id=1'); $act(4, 'state');
    $check(!$db->one('SELECT user_id FROM f_voice_members WHERE user_id=1'), 'password/session revocation removes stale voice membership');
    $denied(fn() => $act(1, 'state'), 'old authentication version cannot use voice API');
    $send(4, $joined[4], $joined[5], 'candidate', $candidate);
    $db->query('UPDATE f_voice_signals SET created_at=?', [time() - 181]); $act(6, 'state');
    $check(!(int) $db->value('SELECT COUNT(*) FROM f_voice_signals'), 'temporary signaling expires without retaining audio data');
    echo "\n$count voice-room checks passed. Synthetic signaling only; real network audio remains a separate acceptance test.\n";
}

try { runRoomChecks($directory); }
finally {
    gc_collect_cycles();
    foreach (glob($directory . '/*') as $file) @unlink($file);
    @rmdir($directory);
}
