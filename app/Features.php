<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class Features
{
    public function __construct(private App $app) {}
    public function profile(int $id): array
    {
        return $this->app->db->one('SELECT * FROM f_profiles WHERE user_id=?',[$id])
            ?? ['user_id'=>$id,'links'=>'[]','avatar_id'=>null,'header_id'=>null,'newsletter'=>0,'consent_at'=>null,'unsubscribe_hash'=>''];
    }
    public function ensureProfile(int $id): void
    {
        $sql=$this->app->db->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql'
            ? "INSERT INTO f_profiles (user_id,links) VALUES (?,'[]') ON DUPLICATE KEY UPDATE user_id=user_id"
            : "INSERT INTO f_profiles (user_id,links) VALUES (?,'[]') ON CONFLICT(user_id) DO NOTHING";
        $this->app->db->query($sql,[$id]);
    }
    public function avatar(int $id, string $username, string $class=''): string
    {
        $profile=$this->app->setting('schema_version','3')==='3'?$this->profile($id):['avatar_id'=>null];
        return '<span class="avatar '.e($class).'">'.($profile['avatar_id']
            ?'<img src="'.e($this->app->url('media',['id'=>$profile['avatar_id']])).'" alt="" loading="lazy">'
            :e(strtoupper(substr($username,0,1)))).'</span>';
    }
    public function publicAction(string $action): bool
    {
        $app=$this->app;
        if ($action==='contact_send') {
            if ($app->setting('contact_enabled')!=='1') throw new DomainException($app->t('not_found'));
            $app->throttle('contact',3,3600);
            AntiSpam::registration($app);
            $name=$app->text('name',100); $email=Mailer::address($app->text('email',190));
            $body=$app->text('contact_body',5000);
            if (!isset($_POST['contact_consent'])) throw new DomainException($app->t('invalid_input'));
            (new Mailer($app))->send($app->setting('contact_to'),'Contact / Kontakt · '.$app->setting('site_name'),
                $name."\n".$email."\n\n".$body,$email);
            return true;
        }
        if ($action==='unsubscribe') {
            $app->throttle('unsubscribe',30,3600);
            $token=$app->text('token',64,64);
            if (!ctype_xdigit($token)) throw new DomainException($app->t('invalid_input'));
            $person=$app->db->one('SELECT user_id FROM f_profiles WHERE unsubscribe_hash=?',[hash('sha256',$token)]);
            if ($person) {
                $app->db->query('UPDATE f_profiles SET newsletter=0 WHERE user_id=?',[$person['user_id']]);
                $app->db->query("UPDATE f_mail_queue SET status='skipped' WHERE user_id=? AND status='pending'",[$person['user_id']]);
            }
            return true; // Same response for expired and unknown tokens.
        }
        return false;
    }
    public function act(string $action, bool $locked=false): bool
    {
        $app=$this->app; $uid=(int)$app->user['id'];
        if (!$locked && in_array($action,['post','profile'],true)) return (new Media($app))->locked(fn(): bool=>$this->act($action,true));
        if ($action==='community_images') { (new CommunityImages($app))->save((int)($_POST['id']??0)); return true; }
        if ($action==='post') { $this->post(); return true; }
        if ($action==='profile') {
            $name=$app->text('name',100); $bio=$app->text('bio',1000,0);
            $links=[];
            foreach (preg_split('/\R/u',$app->text('profile_links',2500,0)) as $line) {
                if (!trim($line)) continue;
                $parts=explode('|',$line,2);
                $url=Links::url(trim($parts[count($parts)-1]));
                $title=count($parts)===2?trim($parts[0]):(string)parse_url($url,PHP_URL_HOST);
                if (strlen($title)>80 || count($links)>=6) throw new DomainException($app->t('invalid_input'));
                $links[]=['label'=>$title,'url'=>$url];
            }
            $this->ensureProfile($uid); $before=$this->profile($uid); $new=[]; $old=[];
            try {
                foreach (['avatar','header'] as $slot) {
                    $id=$before[$slot.'_id'];
                    if (isset($_POST['remove_'.$slot])) $id=null;
                    if (($_FILES[$slot]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
                        $id=(new Media($app))->upload($_FILES[$slot],$slot); $new[]=$id;
                    }
                    if ($before[$slot.'_id'] && $before[$slot.'_id']!==$id) $old[]=$before[$slot.'_id'];
                    $before[$slot.'_id']=$id;
                }
                $locale=($_POST['locale']??'')==='en'?'en':'de';
                $consent=$app->setting('newsletter_enabled')==='1' && isset($_POST['newsletter']);
                // Turning the feature off does not silently withdraw or create a member's consent.
                if ($app->setting('newsletter_enabled')!=='1') $consent=(bool)$before['newsletter'];
                $consentAt=$consent?($before['newsletter']?$before['consent_at']:$app->now()):$before['consent_at'];
                $app->db->transaction(function() use($app,$name,$bio,$locale,$uid,$links,$before,$consent,$consentAt): void {
                    $app->db->query('UPDATE f_users SET name=?,bio=?,locale=? WHERE id=?',[$name,$bio,$locale,$uid]);
                    $app->db->query('UPDATE f_profiles SET links=?,avatar_id=?,header_id=?,newsletter=?,consent_at=? WHERE user_id=?',
                        [json_encode($links,JSON_THROW_ON_ERROR),$before['avatar_id'],$before['header_id'],(int)$consent,$consentAt,$uid]);
                    if (!$consent) $app->db->query("UPDATE f_mail_queue SET status='skipped' WHERE user_id=? AND status='pending'",[$uid]);
                });
                $_SESSION['locale']=$locale;
            } catch (\Throwable $error) {
                foreach ($new as $id) (new Media($app))->remove($app->db->all('SELECT * FROM f_media WHERE id=?',[$id]));
                throw $error;
            }
            foreach ($old as $id) (new Media($app))->remove($app->db->all('SELECT * FROM f_media WHERE id=?',[$id]));
            return true;
        }
        $adminActions=['voice_settings','admin_uploads','admin_member','document_save','document_delete','mail_settings','mail_test','newsletter_create','newsletter_send','newsletter_cancel','package_install','theme_toggle'];
        if (!in_array($action,$adminActions,true)) return false;
        $app->needAdmin();
        $id=(int)($_POST['id']??0);
        switch ($action) {
            case 'voice_settings': VoiceSetup::save($app); break;
            case 'admin_uploads':
                $mb=filter_var($_POST['upload_mb']??5,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>intdiv(PHP_INT_MAX,1048576)]]);
                $daily=filter_var($_POST['upload_daily_mb']??100,FILTER_VALIDATE_INT,['options'=>['min_range'=>10,'max_range'=>500]]);
                $attachments=filter_var($_POST['post_attachments']??$app->setting('post_attachments','4'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if (!$mb || !$daily || !$attachments) throw new DomainException($app->t('invalid_input'));
                foreach (['feed_emojis','feed_images','feed_files','link_previews','profile_images'] as $key) $app->saveSetting($key,isset($_POST[$key])?'1':'0');
                $app->saveSetting('post_attachments',(string)$attachments); $app->saveSetting('upload_mb',(string)$mb); $app->saveSetting('upload_daily_mb',(string)$daily); break;
            case 'admin_member':
                $person=$app->db->one('SELECT * FROM f_users WHERE id=?',[$id]);
                if (!$person) throw new DomainException($app->t('not_found'));
                $name=$app->text('name',100); $bio=$app->text('bio',1000,0); $email=Mailer::address($app->text('email',190));
                if ($app->db->one('SELECT id FROM f_users WHERE email=? AND id<>?',[$email,$id])) throw new DomainException($app->t('account_exists'));
                $role=($_POST['role']??'')==='admin'?'admin':'member';
                $status=in_array($_POST['status']??'', ['active','pending','suspended'],true)?$_POST['status']:'active';
                // Keep every existing administrator protected from accidental lockout/demotion.
                if ($person['role']==='admin' && ($role!=='admin' || $status!=='active')) throw new DomainException($app->t('forbidden'));
                if ($role!==$person['role'] && !password_verify((string)($_POST['current_password']??''),$app->user['password'])) throw new DomainException($app->t('bad_login'));
                $locale=($_POST['locale']??'')==='en'?'en':'de';
                $app->db->transaction(function() use($app,$name,$email,$bio,$role,$status,$locale,$id,$person): void {
                    $changed=$email!==$person['email'] || $role!==$person['role'] || $status!==$person['status'];
                    $app->db->query('UPDATE f_users SET name=?,email=?,bio=?,role=?,status=?,locale=?,auth_version=auth_version+? WHERE id=?',[$name,$email,$bio,$role,$status,$locale,(int)$changed,$id]);
                    if ($email!==$person['email']) {
                        $app->db->query('UPDATE f_profiles SET newsletter=0,unsubscribe_hash=? WHERE user_id=?',['',$id]);
                        $app->db->query("UPDATE f_mail_queue SET status='skipped' WHERE user_id=? AND status='pending'",[$id]);
                    }
                }); break;
            case 'document_save':
                $slug=$app->text('slug',80);
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new DomainException($app->t('invalid_input'));
                $args=[$slug,$app->text('title_de',190),$app->text('title_en',190),$app->text('body_de',50000),$app->text('body_en',50000),isset($_POST['published'])?1:0,$app->now()];
                if ($app->db->one('SELECT id FROM f_documents WHERE slug=? AND id<>?',[$slug,$id])) throw new DomainException($app->t('invalid_input'));
                if ($id) {
                    if (!$app->db->one('SELECT id FROM f_documents WHERE id=?',[$id])) throw new DomainException($app->t('not_found'));
                    $app->db->query('UPDATE f_documents SET slug=?,title_de=?,title_en=?,body_de=?,body_en=?,published=?,updated_at=? WHERE id=?',[...$args,$id]);
                } else $id=$app->db->insert('INSERT INTO f_documents (slug,title_de,title_en,body_de,body_en,published,updated_at) VALUES (?,?,?,?,?,?,?)',$args);
                break;
            case 'document_delete': $app->db->query('UPDATE f_documents SET published=0 WHERE id=?',[$id]); break;
            case 'mail_settings': (new Mailer($app))->save(); break;
            case 'mail_test':
                $app->throttle('mail_test',3,3600,false);
                (new Mailer($app))->send($app->user['email'],'Friends-SNCMS test','Mail configuration test / Test der E-Mail-Einstellungen.'); break;
            case 'newsletter_create': $id=(new Mailer($app))->campaign(); break;
            case 'newsletter_send': (new Mailer($app))->batch(); break;
            case 'newsletter_cancel': $app->db->query("UPDATE f_mail_queue SET status='skipped' WHERE campaign_id=? AND status='pending'",[$id]); break;
            case 'package_install': (new Packages($app))->install((string)($_POST['type']??'')); break;
            case 'theme_toggle':
                $theme=$app->text('theme',40);
                if (!isset($app->extensions->discover('themes')[$theme]) || $theme==='friends') throw new DomainException($app->t('invalid_input'));
                $enabled=$app->setting('theme.'.$theme,'1')==='1';
                if ($enabled && $app->setting('theme')===$theme) $app->saveSetting('theme','friends');
                $app->saveSetting('theme.'.$theme,$enabled?'0':'1'); break;
        }
        $app->db->query('INSERT INTO f_audit (actor_id,action,target_id,created_at) VALUES (?,?,?,?)',[$uid,$action,$id,$app->now()]);
        return true;
    }
    private function post(): void
    {
        $app=$this->app; $uid=(int)$app->user['id'];
        $body=$app->text('body',5000,0);
        $legacy=$app->text('post_link',2048,0); // Accept forms opened before the automatic-link update.
        if ($legacy!=='') $legacy=Links::url($legacy);
        $url=$app->setting('link_previews','1')==='1'?Links::first($body):'';
        if ($url==='') $url=$legacy;
        $files=[]; $input=$_FILES['attachments']??[];
        foreach (($input['error']??[]) as $i=>$error) {
            if ($error===UPLOAD_ERR_NO_FILE) continue;
            if (count($files)>=max(1,(int)$app->setting('post_attachments','4'))) throw new DomainException(sprintf($app->t('attachment_limit'),max(1,(int)$app->setting('post_attachments','4'))));
            $file=[]; foreach (['name','tmp_name','size','type','error'] as $key) $file[$key]=$input[$key][$i]??null;
            $files[]=$file;
        }
        if ($body==='' && !$files && $url==='') throw new DomainException($app->t('invalid_input'));
        $community=(int)($_POST['community_id']??0);
        if ($community) {
            $c=$app->db->one('SELECT * FROM f_communities WHERE id=?',[$community]);
            if (!$c || ($c['kind']==='page' && (int)$c['owner_id']!==$uid)
                || !$app->db->one('SELECT user_id FROM f_memberships WHERE community_id=? AND user_id=?',[$community,$uid])) throw new DomainException($app->t('forbidden'));
        }
        AntiSpam::content($app,'post',$body.($legacy!=='' && !str_contains($body,$legacy)?' '.$legacy:'').' '.implode(' ',array_column($files,'name')));
        $preview=null;
        if ($url!=='') {
            if ($app->setting('link_previews','1')!=='1') throw new DomainException($app->t('uploads_disabled'));
            try { $preview=$this->linkPreview($url); }
            catch (DomainException) { $preview=Links::fallback($url); } // Never lose a valid post because preview requests were exhausted.
        }
        $created=[];
        try {
            $id=$app->db->transaction(function() use($app,$uid,$body,$community,$files,$preview,&$created): int {
                $id=$app->db->insert('INSERT INTO f_posts (user_id,community_id,body,visibility,created_at) VALUES (?,?,?,?,?)',[$uid,$community?:null,$body,($_POST['visibility']??'')==='private'?'private':'members',$app->now()]);
                foreach ($files as $file) { $media=(new Media($app))->upload($file,'post',$id); $created[]=$app->db->one('SELECT * FROM f_media WHERE id=?',[$media]); }
                if ($preview) $app->db->query('INSERT INTO f_post_links (post_id,url,title,description) VALUES (?,?,?,?)',[$id,$preview['url'],$preview['title'],$preview['description']]);
                return $id;
            });
        } catch (\Throwable $error) {
            // Rows roll back; remove filesystem writes as well.
            foreach ($created as $row) @unlink((new Media($app))->directory().'/'.$row['filename']);
            throw $error;
        }
        $app->extensions->emit('post.created',$id);
    }
    private function linkPreview(string $url): array
    {
        $key=hash('sha256',$url); $cached=$_SESSION['link_previews'][$key]??null;
        if (is_array($cached) && ($cached['expires']??0)>time() && ($cached['result']['url']??null)===$url) return $cached['result'];
        $this->app->throttle('preview:'.$this->app->user['id'],30,3600,false);
        $result=Links::preview($url);
        if (count($_SESSION['link_previews']??[])>=10) array_shift($_SESSION['link_previews']);
        $_SESSION['link_previews'][$key]=['expires'=>time()+900,'result'=>$result];
        return $result;
    }
    public function previewApi(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $app=$this->app; $app->needUser();
            if ($_SERVER['REQUEST_METHOD']!=='POST' || !hash_equals($app->csrf(),(string)($_POST['csrf']??''))) throw new DomainException($app->t('csrf_error'));
            if ($app->setting('link_previews','1')!=='1') throw new DomainException($app->t('uploads_disabled'));
            $url=Links::url($app->text('url',2048)); $result=$this->linkPreview($url);
            echo json_encode($result,JSON_THROW_ON_ERROR);
        } catch (DomainException $error) { http_response_code(400); echo json_encode(['error'=>$error->getMessage()]); }
    }
}
