<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class Mailer
{
    public function __construct(private App $app) {}
    public static function address(string $email): string
    {
        if (strlen($email)>190 || preg_match('/[\r\n]/',$email) || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Invalid email address.');
        return $email;
    }
    public static function message(string $from, string $to, string $subject, string $body, string $reply=''): string
    {
        self::address($from); self::address($to);
        if ($reply!=='') self::address($reply);
        if (preg_match('/[\r\n]/',$subject) || strlen($subject)>760) throw new DomainException('Invalid subject.');
        $headers=['Date: '.gmdate('r'),'From: '.$from,'To: '.$to,
            self::subject($subject),
            'Message-ID: <'.bin2hex(random_bytes(16)).'@'.substr(strrchr($from,'@'),1).'>',
            'MIME-Version: 1.0','Content-Type: text/plain; charset=UTF-8','Content-Transfer-Encoding: base64'];
        if ($reply!=='') $headers[]='Reply-To: '.$reply;
        return implode("\r\n",$headers)."\r\n\r\n".rtrim(chunk_split(base64_encode($body),76,"\r\n"));
    }
    public static function subject(string $subject): string
    {
        if (preg_match('/[\r\n]/',$subject) || strlen($subject)>760) throw new DomainException('Invalid subject.');
        return iconv_mime_encode('Subject',$subject,['scheme'=>'B','input-charset'=>'UTF-8','output-charset'=>'UTF-8','line-length'=>76,'line-break-chars'=>"\r\n"]);
    }
    private function secretPath(): string { return dirname(FRIENDS_CONFIG).'/smtp-secret.php'; }
    public function save(): void
    {
        $app=$this->app; $app->needAdmin();
        $transport=$app->text('mail_transport',10);
        if (!in_array($transport,['off','system','smtp'],true)) throw new DomainException($app->t('invalid_input'));
        $values=['mail_transport'=>$transport];
        foreach (['mail_from','contact_to'] as $key) {
            $value=$app->text($key,190,0); if ($value!=='') self::address($value); $values[$key]=$value;
        }
        $host=$app->text('smtp_host',190,0);
        if ($host!=='' && !preg_match('/^[a-zA-Z0-9.-]+$/D',$host)) throw new DomainException($app->t('invalid_input'));
        $port=filter_var($_POST['smtp_port']??587,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        $security=(string)($_POST['smtp_security']??'starttls');
        if (!$port || !in_array($security,['starttls','tls'],true)) throw new DomainException($app->t('invalid_input'));
        $base=rtrim($app->text('public_url',2048,0),'/');
        if ($base!=='') {
            Links::url($base);
            if (parse_url($base,PHP_URL_SCHEME)!=='https' || parse_url($base,PHP_URL_QUERY) || parse_url($base,PHP_URL_FRAGMENT)) throw new DomainException($app->t('invalid_input'));
        }
        if ($transport!=='off' && !$values['mail_from']) throw new DomainException($app->t('invalid_input'));
        if ($transport==='smtp' && !$host) throw new DomainException($app->t('invalid_input'));
        if (isset($_POST['contact_enabled']) && (!$values['contact_to'] || $transport==='off')) throw new DomainException($app->t('mail_not_ready'));
        if (isset($_POST['newsletter_enabled']) && (!$base || $transport==='off')) throw new DomainException($app->t('mail_not_ready'));
        $values += ['smtp_host'=>$host,'smtp_port'=>(string)$port,'smtp_security'=>$security,
            'smtp_user'=>$app->text('smtp_user',190,0),'public_url'=>$base,
            'contact_enabled'=>isset($_POST['contact_enabled'])?'1':'0','newsletter_enabled'=>isset($_POST['newsletter_enabled'])?'1':'0'];
        $password=$_POST['smtp_password']??'';
        if (!is_string($password) || strlen($password)>500) throw new DomainException($app->t('invalid_input'));
        if ($password!=='' || isset($_POST['clear_smtp_password'])) {
            $temp=$this->secretPath().'.'.bin2hex(random_bytes(6));
            if (file_put_contents($temp,'<?php return '.var_export($password,true).';',LOCK_EX)===false) throw new DomainException($app->t('config_error'));
            @chmod($temp,0600);
            if (!rename($temp,$this->secretPath())) { @unlink($temp); throw new DomainException($app->t('config_error')); }
        }
        foreach ($values as $key=>$value) $app->saveSetting($key,$value);
    }
    public function send(string $to, string $subject, string $body, string $reply=''): void
    {
        $app=$this->app; $from=self::address($app->setting('mail_from'));
        $message=self::message($from,$to,$subject,$body,$reply);
        if ($app->setting('mail_transport','off')==='system') {
            // PHP's configured host mail transport; never pass shell/envelope parameters.
            [$head,$encoded]=explode("\r\n\r\n",$message,2);
            $headers=preg_replace('/^(?:To|Subject):[^\r\n]*(?:\r\n[ \t][^\r\n]*)*(?:\r\n|$)/m','',$head);
            if (!mail($to,substr(self::subject($subject),9),$encoded,$headers)) throw new DomainException($app->t('mail_failed'));
            return;
        }
        if ($app->setting('mail_transport')!=='smtp' || !extension_loaded('openssl')) throw new DomainException($app->t('mail_not_ready'));
        $host=$app->setting('smtp_host'); $port=(int)$app->setting('smtp_port','587');
        if (!preg_match('/^[a-zA-Z0-9.-]+$/D',$host)) throw new DomainException($app->t('mail_not_ready'));
        $context=stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>$host,'allow_self_signed'=>false,'disable_compression'=>true]]);
        $socket=@stream_socket_client(($app->setting('smtp_security')==='tls'?'tls':'tcp').'://'.$host.':'.$port,$code,$error,8,STREAM_CLIENT_CONNECT,$context);
        if (!$socket) throw new DomainException($app->t('mail_failed'));
        stream_set_timeout($socket,8);
        $read=static function(array $codes) use($socket,$app): void {
            $size=0;
            do {
                $line=fgets($socket,2048);
                if ($line===false || ($size+=strlen($line))>32768 || !preg_match('/^[0-9]{3}[ -]/',$line)) throw new DomainException($app->t('mail_failed'));
            } while ($line[3]==='-');
            if (!in_array((int)substr($line,0,3),$codes,true)) throw new DomainException($app->t('mail_failed'));
        };
        $write=static function(string $data) use($socket,$app): void {
            while ($data!=='') {
                $written=fwrite($socket,$data);
                if (!$written) throw new DomainException($app->t('mail_failed'));
                $data=substr($data,$written);
            }
        };
        $command=static function(string $command,array $codes) use($write,$read): void { $write($command."\r\n"); $read($codes); };
        try {
            $read([220]); $command('EHLO friends.local',[250]);
            if ($app->setting('smtp_security')!=='tls') {
                $command('STARTTLS',[220]);
                if (stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT|STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)!==true) throw new DomainException($app->t('mail_failed'));
                $command('EHLO friends.local',[250]);
            }
            if ($app->setting('smtp_user')!=='') {
                $password=is_file($this->secretPath())?require $this->secretPath():'';
                $command('AUTH LOGIN',[334]); $command(base64_encode($app->setting('smtp_user')),[334]); $command(base64_encode($password),[235]);
            }
            $command('MAIL FROM:<'.$from.'>',[250]); $command('RCPT TO:<'.self::address($to).'>',[250,251]); $command('DATA',[354]);
            $write(preg_replace('/(?m)^\./','..',$message)."\r\n.\r\n"); $read([250]);
            // Delivery was accepted. A lost QUIT reply must not turn it into a retryable failure.
            @fwrite($socket,"QUIT\r\n");
        } finally { fclose($socket); }
    }
    public function campaign(): int
    {
        $app=$this->app; $app->needAdmin();
        if ($app->setting('newsletter_enabled')!=='1') throw new DomainException($app->t('mail_not_ready'));
        $subject=$app->text('subject',190); $body=$app->text('mail_body',20000);
        return $app->db->transaction(function() use($app,$subject,$body): int {
            $id=$app->db->insert('INSERT INTO f_campaigns (subject,body,created_at,actor_id) VALUES (?,?,?,?)',[$subject,$body,$app->now(),$app->user['id']]);
            $app->db->query("INSERT INTO f_mail_queue (campaign_id,user_id) SELECT ?,p.user_id FROM f_profiles p JOIN f_users u ON u.id=p.user_id WHERE p.newsletter=1 AND u.status='active'",[$id]);
            return $id;
        });
    }
    public function batch(): void
    {
        $app=$this->app; $app->needAdmin(); $app->throttle('mail_batch',5,60,false);
        if ($app->setting('newsletter_enabled')!=='1') throw new DomainException($app->t('mail_not_ready'));
        foreach ($app->db->all("SELECT q.*,c.subject,c.body FROM f_mail_queue q JOIN f_campaigns c ON c.id=q.campaign_id WHERE q.status='pending' ORDER BY q.id LIMIT 3") as $row) {
            if (!$app->db->query("UPDATE f_mail_queue SET status='sending' WHERE id=? AND status='pending'",[$row['id']])->rowCount()) continue;
            $person=$app->db->one("SELECT u.email,p.newsletter FROM f_users u JOIN f_profiles p ON p.user_id=u.id WHERE u.id=? AND u.status='active'",[$row['user_id']]);
            if (!$person || !$person['newsletter']) { $app->db->query("UPDATE f_mail_queue SET status='skipped' WHERE id=?",[$row['id']]); continue; }
            $profile=(new Features($app))->profile((int)$row['user_id']);
            $token=hash_hmac('sha256','unsubscribe|'.$row['user_id'].'|'.$profile['consent_at'],$app->setting('secret'));
            $app->db->query('UPDATE f_profiles SET unsubscribe_hash=? WHERE user_id=?',[hash('sha256',$token),$row['user_id']]);
            $url=$app->setting('public_url').'/'.$app->url('unsubscribe',['token'=>$token]);
            try {
                $this->send($person['email'],$row['subject'],$row['body']."\n\nUnsubscribe / Abmelden: ".$url);
                $app->db->query("UPDATE f_mail_queue SET status='sent' WHERE id=?",[$row['id']]);
            } catch (DomainException) {
                // Ambiguous transport failures never automatically retry and risk duplicate mail.
                $app->db->query("UPDATE f_mail_queue SET status='failed' WHERE id=?",[$row['id']]);
            }
        }
    }
}
