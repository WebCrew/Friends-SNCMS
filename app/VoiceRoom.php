<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;

use DomainException;
use PDO;
use Throwable;

/** One opt-in community room. Media flows between browsers, never through PHP. */
final class VoiceRoom
{
    public const CAPACITY = 6;
    public const TIMEOUT = 35;

    public function __construct(private App $app) {}

    private function prune(): void
    {
        $this->app->db->query(
            "DELETE FROM f_voice_members WHERE last_seen<? OR user_id IN (SELECT id FROM f_users WHERE status<>'active') OR auth_version<>(SELECT u.auth_version FROM f_users u WHERE u.id=f_voice_members.user_id)",
            [time() - self::TIMEOUT]
        );
        $this->app->db->query('DELETE FROM f_voice_signals WHERE created_at<?', [time() - 180]);
    }

    private function members(): array
    {
        return $this->app->db->all('SELECT m.user_id,m.session_id,m.muted,u.name FROM f_voice_members m JOIN f_users u ON u.id=m.user_id ORDER BY m.user_id');
    }

    private function ownSession(): array
    {
        $token = $_POST['voice_token'] ?? '';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            throw new DomainException($this->app->t('voice_session_lost'));
        }
        $member = $this->app->db->one(
            'SELECT * FROM f_voice_members WHERE user_id=? AND token_hash=?',
            [$this->app->user['id'], hash('sha256', $token)]
        );
        if (!$member) throw new DomainException($this->app->t('voice_session_lost'));
        return $member;
    }

    private function payload(string $kind): string
    {
        $raw = $this->app->text('payload', $kind === 'candidate' ? 8192 : 60000);
        if (strlen($raw) > ($kind === 'candidate' ? 8192 : 60000)) {
            throw new DomainException($this->app->t('invalid_input'));
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) throw new DomainException($this->app->t('invalid_input'));
        if ($kind === 'candidate') {
            if (!is_string($data['candidate'] ?? null)
                || !str_starts_with($data['candidate'], 'candidate:')
                || strlen($data['candidate']) > 4000
                || array_diff(array_keys($data), ['candidate', 'sdpMid', 'sdpMLineIndex', 'usernameFragment'])) {
                throw new DomainException($this->app->t('invalid_input'));
            }
            foreach (['sdpMid', 'usernameFragment'] as $field) {
                if (isset($data[$field]) && (!is_string($data[$field]) || strlen($data[$field]) > 256)) {
                    throw new DomainException($this->app->t('invalid_input'));
                }
            }
            if (isset($data['sdpMLineIndex']) && (!is_int($data['sdpMLineIndex']) || $data['sdpMLineIndex'] < 0 || $data['sdpMLineIndex'] > 64)) {
                throw new DomainException($this->app->t('invalid_input'));
            }
        } else {
            $sdp = $data['sdp'] ?? null;
            if (($data['type'] ?? '') !== $kind || !is_string($sdp)
                || array_diff(array_keys($data), ['type', 'sdp'])
                || !str_starts_with($sdp, 'v=0') || !str_contains($sdp, 'm=audio')
                || str_contains($sdp, 'm=video') || str_contains($sdp, 'm=application')) {
                throw new DomainException($this->app->t('invalid_input'));
            }
        }
        return $raw;
    }

    public function handle(string $action): array
    {
        $a = $this->app;
        $a->needUser();
        $db = $a->db;
        $uid = (int) $a->user['id'];
        if (!$db->one("SELECT id FROM f_users WHERE id=? AND status='active' AND auth_version=?", [$uid, $a->user['auth_version']])) {
            throw new DomainException($a->t('login_required'));
        }
        $a->throttle('voice-api:' . $uid, 420, 60, false);
        $this->prune();

        if ($action === 'state') {
            $a->throttle('voice-state:' . $uid, 60, 60, false);
            return ['members' => $this->members(), 'capacity' => self::CAPACITY];
        }

        if ($action === 'join') {
            $a->throttle('voice-join:' . $uid, 8, 60, false);
            $ice = Chat::validateIce($a->setting('ice_servers', '[]'));
            $token = bin2hex(random_bytes(32));
            $session = bin2hex(random_bytes(16));
            $db->transaction(function () use ($db, $uid, $token, $session, $a): void {
                // Existing settings row serializes capacity checks, including the empty room.
                if ($db->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                    $db->one("SELECT name FROM f_settings WHERE name='site_name' FOR UPDATE");
                }
                if ($db->one('SELECT user_id FROM f_voice_members WHERE user_id=?', [$uid])) {
                    throw new DomainException($a->t('voice_already_joined'));
                }
                if ((int) $db->value('SELECT COUNT(*) FROM f_voice_members') >= self::CAPACITY) {
                    throw new DomainException($a->t('voice_full'));
                }
                $db->query(
                    'INSERT INTO f_voice_members (user_id,session_id,token_hash,auth_version,muted,last_seen) VALUES (?,?,?,?,0,?)',
                    [$uid, $session, hash('sha256', $token), $a->user['auth_version'], time()]
                );
            });
            return ['voice_token' => $token, 'session_id' => $session, 'members' => $this->members(), 'ice_servers' => $ice, 'capacity' => self::CAPACITY];
        }

        $own = $this->ownSession();
        if ($action === 'leave') {
            $db->query('DELETE FROM f_voice_members WHERE user_id=? AND session_id=?', [$uid, $own['session_id']]);
            return ['ok' => true];
        }

        if ($action === 'sync') {
            $a->throttle('voice-sync:' . $uid, 90, 60, false);
            $db->query('UPDATE f_voice_members SET last_seen=?,muted=? WHERE user_id=? AND session_id=?', [time(), ($_POST['muted'] ?? '0') === '1' ? 1 : 0, $uid, $own['session_id']]);
            $after = max(0, (int) ($_POST['after'] ?? 0));
            $signals = $db->all(
                'SELECT id,sender_session,kind,payload FROM f_voice_signals WHERE recipient_session=? AND id>? ORDER BY id LIMIT 100',
                [$own['session_id'], $after]
            );
            return ['members' => $this->members(), 'signals' => $signals];
        }

        if ($action !== 'signal') throw new DomainException($a->t('invalid_input'));
        $a->throttle('voice-signal:' . $uid, 300, 60, false);
        $target = $a->text('target_session', 32, 32);
        $kind = $a->text('kind', 9);
        if (!preg_match('/^[a-f0-9]{32}$/D', $target) || !in_array($kind, ['offer', 'answer', 'candidate'], true)) {
            throw new DomainException($a->t('invalid_input'));
        }
        $recipient = $db->one('SELECT user_id FROM f_voice_members WHERE session_id=? AND user_id<>?', [$target, $uid]);
        if (!$recipient || ($kind === 'offer' && $uid > (int) $recipient['user_id']) || ($kind === 'answer' && $uid < (int) $recipient['user_id'])) {
            throw new DomainException($a->t('forbidden'));
        }
        $payload = $this->payload($kind);
        if ((int) $db->value('SELECT COUNT(*) FROM f_voice_signals WHERE sender_session=? AND recipient_session=?', [$own['session_id'], $target]) >= 128) {
            throw new DomainException($a->t('rate_limit'));
        }
        $db->query(
            'INSERT INTO f_voice_signals (sender_session,recipient_session,kind,payload,created_at) VALUES (?,?,?,?,?)',
            [$own['session_id'], $target, $kind, $payload, time()]
        );
        return ['ok' => true];
    }

    public function serve(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->app->needUser();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['csrf'] ?? null)
                || !hash_equals($this->app->csrf(), $_POST['csrf'])) {
                throw new DomainException($this->app->t('csrf_error'));
            }
            $action = $this->app->text('voice_action', 10);
            echo json_encode($this->handle($action), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (DomainException $error) {
            http_response_code($this->app->user ? 400 : 401);
            echo json_encode(['error' => $error->getMessage()]);
        } catch (Throwable $error) {
            error_log((string) $error);
            http_response_code(500);
            echo json_encode(['error' => $this->app->t('database_error')]);
        }
    }
}
