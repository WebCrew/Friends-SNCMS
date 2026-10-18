<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class AccountMail
{
    public function __construct(private App $app) {}
    public function ready(): string
    {
        $a=$this->app; $base=rtrim($a->setting('public_url'),'/');
        if (!in_array($a->setting('mail_transport'),['system','smtp'],true) || !$a->setting('mail_from') || !$base) throw new DomainException($a->t('account_mail_setup'));
        Links::url($base);
        if (parse_url($base,PHP_URL_SCHEME)!=='https' || parse_url($base,PHP_URL_QUERY) || parse_url($base,PHP_URL_FRAGMENT)) throw new DomainException($a->t('account_mail_setup'));
        return $base;
    }
    // Bind signed links to the password, email, status and session version: no schema update needed.
    public function token(array $u,string $purpose,int $expires): string
    {
        $payload=$u['id'].'.'.$expires.'.'.bin2hex(random_bytes(16));
        return $payload.'.'.$this->signature($u,$purpose,$payload);
    }
    private function signature(array $u,string $purpose,string $payload): string
    {
        return hash_hmac('sha256',json_encode([$purpose,$payload,$u['password'],$u['email'],$u['status'],(int)$u['auth_version']],JSON_THROW_ON_ERROR),$this->app->setting('secret'));
    }
    public function account(string $token,string $purpose): array
    {
        if (!in_array($purpose,['verify','reset'],true) || !preg_match('/^([1-9][0-9]{0,18})\.([0-9]{10})\.([a-f0-9]{32})\.([a-f0-9]{64})$/D',$token,$m) || (int)$m[2]<=time() || (int)$m[2]>time()+86400) throw new DomainException($this->app->t('account_link_invalid'));
        $u=$this->app->db->one('SELECT * FROM f_users WHERE id=?',[$m[1]]);
        if (!$u || $u['status']!==($purpose==='verify'?'pending':'active') || !hash_equals($this->signature($u,$purpose,$m[1].'.'.$m[2].'.'.$m[3]),$m[4])) throw new DomainException($this->app->t('account_link_invalid'));
        return $u;
    }
    private function send(array $u,string $purpose): void
    {
        $a=$this->app; $base=$this->ready();
        $strings=require FRIENDS_ROOT.'/resources/lang/'.(in_array($u['locale'],['de','en'],true)?$u['locale']:'en').'.php';
        $url=$base.'/'.$a->url($purpose==='verify'?'verify':'reset',['token'=>$this->token($u,$purpose,time()+($purpose==='verify'?86400:1800))]);
        (new Mailer($a))->send($u['email'],$strings[$purpose.'_subject'],$strings[$purpose.'_body']."\n\n".$url);
    }
    public function verification(int $id): void { $this->send($this->app->db->one('SELECT * FROM f_users WHERE id=?',[$id]),'verify'); }
    public function act(string $action): bool
    {
        if (!in_array($action,['forgot','resend_verification','reset','verify'],true)) return false;
        $a=$this->app; $a->throttle('account-mail',10,900);
        if ($action==='forgot' || $action==='resend_verification') {
            $this->ready(); $email=Mailer::address($a->text('email',190));
            $a->throttle('account-mail:'.strtolower($email),3,3600,false);
            $u=$a->db->one('SELECT * FROM f_users WHERE email=?',[$email]);
            if ($u && $u['status']===($action==='forgot'?'active':'pending')) {
                try { $this->send($u,$action==='forgot'?'reset':'verify'); }
                catch (DomainException) { error_log('Friends-SNCMS: account email delivery failed.'); }
            }
            $_SESSION['success']=$a->t('account_mail_sent'); return true;
        }
        $this->complete($action,$a->text('token',200),$_POST);
        $_SESSION['success']=$a->t($action==='reset'?'password_reset_done':'verify_done'); $a->redirect('login');
    }
    public function complete(string $action,string $token,array $input): void
    {
        $a=$this->app; $u=$this->account($token,$action);
        if ($action==='reset') {
            $password=$input['password']??null; $confirm=$input['password_confirm']??null;
            if (!is_string($password) || strlen($password)<12 || strlen($password)>72) throw new DomainException($a->t('password_length'));
            if (!is_string($confirm) || !hash_equals($password,$confirm)) throw new DomainException($a->t('password_mismatch'));
            $changed=$a->db->query("UPDATE f_users SET password=?,auth_version=auth_version+1 WHERE id=? AND password=? AND auth_version=? AND status='active'",[password_hash($password,PASSWORD_DEFAULT),$u['id'],$u['password'],$u['auth_version']])->rowCount();
        } else {
            $changed=$a->db->query("UPDATE f_users SET status='active',auth_version=auth_version+1 WHERE id=? AND password=? AND auth_version=? AND status='pending'",[$u['id'],$u['password'],$u['auth_version']])->rowCount();
        }
        if ($changed!==1) throw new DomainException($a->t('account_link_invalid'));
        if ($action==='verify') $a->extensions->emit('user.created',(int)$u['id']);
    }
}
