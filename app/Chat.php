<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class Chat
{
    public function __construct(private App $app) {}
    public static function validateIce(string $json): array
    {
        $servers=json_decode($json,true);
        if (!is_array($servers) || !array_is_list($servers) || count($servers)>8) throw new DomainException('ICE: invalid server list / ungültige Serverliste.');
        foreach($servers as $server) {
            if (!is_array($server) || array_diff(array_keys($server),['urls','username','credential'])) throw new DomainException('ICE: invalid fields / ungültige Felder.');
            $urls=$server['urls']??[]; if (is_string($urls)) $urls=[$urls];
            if (!is_array($urls) || !$urls || count($urls)>8) throw new DomainException('ICE: missing URLs / URLs fehlen.');
            foreach($urls as $url) if (!is_string($url) || strlen($url)>500 || !preg_match('/^(stun|stuns|turn|turns):[a-zA-Z0-9.\-\[\]:]+(?:\?transport=(?:udp|tcp))?$/D',$url)) throw new DomainException('ICE: invalid URL / ungültige URL.');
            foreach(['username','credential'] as $key) if (isset($server[$key]) && (!is_string($server[$key]) || strlen($server[$key])>500)) throw new DomainException('ICE: invalid credentials / ungültige Zugangsdaten.');
        }
        return $servers;
    }
    public function contacts(): array
    {
        $this->app->needUser(); $uid=(int)$this->app->user['id'];
        return $this->app->db->all("SELECT u.id,u.name,u.username FROM f_users u JOIN f_friendships f ON (u.id=f.low_id OR u.id=f.high_id) WHERE (f.low_id=? OR f.high_id=?) AND f.status='accepted' AND u.id<>? AND u.status='active' ORDER BY u.name LIMIT 100",[$uid,$uid,$uid]);
    }
    private function peer(int $id): array
    {
        $this->app->needUser();
        $peer=$this->app->db->one("SELECT id,name FROM f_users WHERE id=? AND status='active'",[$id]);
        if (!$peer || !$this->app->friends((int)$this->app->user['id'],$id)) throw new DomainException($this->app->t('friends_only'));
        return $peer;
    }
    public function handle(string $action): array
    {
        $a=$this->app; $a->needUser(); $db=$a->db; $uid=(int)$a->user['id'];
        if ($action==='sync') {
            $a->throttle('chat-poll:'.$uid,240,60,false);
            $peer=(int)($_GET['peer']??0); $messages=[];
            if ($peer) {
                $this->peer($peer);
                $after=max(0,(int)($_GET['after']??0));
                $messages=($_GET['history']??'')==='1' && !$after
                    ?$db->all('SELECT * FROM (SELECT id,sender_id,recipient_id,body,kind,created_at FROM f_messages WHERE ((sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?)) ORDER BY id DESC LIMIT 100) recent ORDER BY id',[$uid,$peer,$peer,$uid])
                    :$db->all('SELECT id,sender_id,recipient_id,body,kind,created_at FROM f_messages WHERE id>? AND ((sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?)) ORDER BY id LIMIT 100',[$after,$uid,$peer,$peer,$uid]);
                foreach($messages as &$message) if ($message['kind']==='audio') $message['media_url']=$a->url('chat_media',['id'=>$message['id']]);
            }
            return ['messages'=>$messages];
        }
        $a->throttle('chat:'.$uid,180,60,false);
        if ($action==='text') {
            $id=(int)($_POST['peer']??0); $this->peer($id);
            $_POST['id']=$id;
            $a->act('message'); // Same ownership, length and notification rules as regular forms.
            unset($_SESSION['success']);
            return ['ok'=>true];
        }
        if ($action==='audio') {
            $id=(int)($_POST['peer']??0); $this->peer($id);
            $a->throttle('audio:'.$uid,20,86400,false);
            $file=$_FILES['audio']??null;
            if (!$file || $file['error']!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size']<32 || $file['size']>8*1024*1024 || !class_exists(\finfo::class)) throw new DomainException($a->t('audio_upload_error'));
            $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $types=['audio/webm'=>'webm','video/webm'=>'webm','audio/ogg'=>'ogg','application/ogg'=>'ogg','audio/mp4'=>'m4a','video/mp4'=>'m4a','audio/x-m4a'=>'m4a'];
            if (!isset($types[$mime])) throw new DomainException($a->t('audio_upload_error'));
            $header=file_get_contents($file['tmp_name'],false,null,0,16); $extension=$types[$mime];
            if (($extension==='webm' && !str_starts_with($header,"\x1a\x45\xdf\xa3")) || ($extension==='ogg' && !str_starts_with($header,'OggS')) || ($extension==='m4a' && substr($header,4,4)!=='ftyp')) throw new DomainException($a->t('audio_upload_error'));
            $directory=dirname(FRIENDS_CONFIG).'/chat-media';
            if (!is_dir($directory) && !mkdir($directory,0700,true)) throw new DomainException($a->t('audio_upload_error'));
            $name=bin2hex(random_bytes(24)).'.'.$extension; $path=$directory.'/'.$name;
            if (!move_uploaded_file($file['tmp_name'],$path)) throw new DomainException($a->t('audio_upload_error'));
            @chmod($path,0600);
            try {
                $db->transaction(function() use($db,$uid,$id,$file,$name,$mime,$a):void {
                    if ($db->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql') $db->one('SELECT id FROM f_users WHERE id=? FOR UPDATE',[$uid]);
                    if ((int)$db->value('SELECT COALESCE(SUM(media_bytes),0) FROM f_messages WHERE sender_id=?',[$uid])+$file['size']>100*1024*1024) throw new DomainException($a->t('audio_upload_error'));
                    $db->query('INSERT INTO f_messages (sender_id,recipient_id,body,kind,media,mime,media_bytes,created_at) VALUES (?,?,?,?,?,?,?,?)',[$uid,$id,'','audio',$name,$mime,$file['size'],$a->now()]);
                    $a->notify($id,'message',$uid);
                });
            }
            catch(\Throwable $error) { unlink($path); throw $error; }
            return ['ok'=>true];
        }
        throw new DomainException($a->t('invalid_input'));
    }
    public function serve(): void
    {
        try {
            $this->app->needUser();
            if ($this->app->route==='chat_media') { $this->media(); return; }
            if ($_SERVER['REQUEST_METHOD']==='POST') {
                if (!hash_equals($this->app->csrf(),(string)($_POST['csrf']??''))) throw new DomainException($this->app->t('csrf_error'));
                $action=(string)($_POST['chat_action']??'');
                if ($action==='sync') throw new DomainException($this->app->t('invalid_input'));
            } elseif ($_SERVER['REQUEST_METHOD']==='GET') $action='sync';
            else throw new DomainException($this->app->t('invalid_input'));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->handle($action),JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);
        } catch(DomainException $error) { http_response_code($this->app->user?400:401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error'=>$error->getMessage()]); }
        catch(\Throwable $error) { error_log((string)$error); http_response_code(500); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error'=>$this->app->t('database_error')]); }
    }
    private function media(): void
    {
        $uid=(int)$this->app->user['id'];
        $message=$this->app->db->one("SELECT * FROM f_messages WHERE id=? AND kind='audio' AND (sender_id=? OR recipient_id=?)",[(int)($_GET['id']??0),$uid,$uid]);
        if (!$message) throw new DomainException($this->app->t('forbidden'));
        $this->peer((int)$message[$uid===(int)$message['sender_id']?'recipient_id':'sender_id']);
        if (!preg_match('/^[a-f0-9]{48}\.(webm|ogg|m4a)$/D',$message['media'])) throw new DomainException($this->app->t('not_found'));
        $path=dirname(FRIENDS_CONFIG).'/chat-media/'.$message['media'];
        if (!is_file($path)) throw new DomainException($this->app->t('not_found'));
        $size=filesize($path); $start=0; $end=$size-1;
        header('Content-Type: '.$message['mime']); header('Accept-Ranges: bytes'); header('Content-Disposition: inline');
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (!preg_match('/^bytes=(\d*)-(\d*)$/D',$_SERVER['HTTP_RANGE'],$range) || ($range[1]==='' && $range[2]==='')) { http_response_code(416); header('Content-Range: bytes */'.$size); return; }
            if ($range[1]==='') $start=max(0,$size-(int)$range[2]);
            else { $start=(int)$range[1]; if ($range[2]!=='') $end=min($end,(int)$range[2]); }
            if ($start>$end || $start>=$size) { http_response_code(416); header('Content-Range: bytes */'.$size); return; }
            http_response_code(206); header("Content-Range: bytes $start-$end/$size");
        }
        header('Content-Length: '.($end-$start+1)); session_write_close();
        $file=fopen($path,'rb'); fseek($file,$start); $left=$end-$start+1;
        while($left>0 && !feof($file)) { $chunk=fread($file,min(65536,$left)); if ($chunk===false || $chunk==='') break; echo $chunk; $left-=strlen($chunk); }
        fclose($file);
    }
}
