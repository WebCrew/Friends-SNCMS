<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class AntiSpam
{
    public static function fields(App $app): string
    {
        $token=bin2hex(random_bytes(16));
        foreach($_SESSION['signup_tickets']??[] as $key=>$issued) if ($issued<time()-3600) unset($_SESSION['signup_tickets'][$key]);
        if (count($_SESSION['signup_tickets']??[])>=10) array_shift($_SESSION['signup_tickets']);
        $_SESSION['signup_tickets'][$token]=time();
        return '<input type="hidden" name="signup_ticket" value="'.$token.'"><div class="bot-trap" aria-hidden="true"><label>'.$app->t('leave_empty').'<input type="text" name="contact_fax" value="" tabindex="-1" autocomplete="off"></label></div>';
    }
    public static function registration(App $app): void
    {
        if ($app->setting('spam_protection','1')!=='1') return;
        $ticket=(string)($_POST['signup_ticket']??''); $issued=$_SESSION['signup_tickets'][$ticket]??0;
        if (trim((string)($_POST['contact_fax']??''))!=='' || !$issued || time()-$issued<2 || time()-$issued>3600) throw new DomainException($app->t('spam_form'));
        unset($_SESSION['signup_tickets'][$ticket]);
    }
    public static function content(App $app,string $kind,string $body): void
    {
        if ($app->setting('spam_protection','1')!=='1') return;
        $uid=(int)$app->user['id'];
        $new=strtotime($app->user['created_at'].' UTC')>time()-86400;
        $limits=['post'=>[6,30],'comment'=>[20,120],'message'=>[30,300],'community'=>[3,10]];
        if (!isset($limits[$kind])) throw new \LogicException('Unknown spam scope');
        $links=preg_match_all('~(?:https?://|www\.)[^\s]+~iu',$body);
        if ($links>($new?2:5) && $app->user['role']!=='admin') throw new DomainException($app->t('spam_links'));
        if ($app->user['role']!=='admin') $app->throttle('volume:'.$kind.':'.$uid,$limits[$kind][$new?0:1],3600,false);
        $normalized=strtolower(preg_replace('/\s+/u',' ',trim($body)));
        $hash=hash('sha256',$normalized);
        // Per-account deduplication is independent of IP, so switching IPs cannot resend a copy.
        $bucket=hash_hmac('sha256','duplicate:'.$kind.':'.$uid.':'.$hash,$app->setting('secret'));
        $db=$app->db; $db->query('DELETE FROM f_throttles WHERE expires_at<?',[time()]);
        try { $db->query('INSERT INTO f_throttles (bucket,attempts,expires_at) VALUES (?,1,?)',[$bucket,time()+90]); }
        catch(\PDOException $error) {
            if (in_array((string)$error->getCode(),['23000','23505','19'],true)) throw new DomainException($app->t('spam_duplicate'));
            throw $error;
        }
    }
}
