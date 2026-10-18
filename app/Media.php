<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class Media
{
    private static array $heldLocks=[];
    public function __construct(private App $app) {}
    public function directory(): string { return dirname(FRIENDS_CONFIG) . '/files'; }
    public static function image(string $bytes): array
    {
        if (!extension_loaded('gd')) throw new DomainException('GD is required for image uploads.');
        $info = @getimagesizefromstring($bytes);
        if (!$info || !in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true)
            || $info[0]>8000 || $info[1]>8000 || $info[0]*$info[1]>12000000) throw new DomainException('Invalid image or too many pixels.');
        $source = @imagecreatefromstring($bytes);
        if (!$source) throw new DomainException('Invalid image.');
        $width = min(2000,$info[0]); $height = max(1,(int) round($info[1]*$width/$info[0]));
        $target = imagecreatetruecolor($width,$height);
        imagealphablending($target,false); imagesavealpha($target,true);
        imagecopyresampled($target,$source,0,0,0,0,$width,$height,$info[0],$info[1]);
        ob_start(); imagepng($target,null,6); $result=ob_get_clean();
        imagedestroy($target); imagedestroy($source);
        return ['data'=>$result,'mime'=>'image/png','extension'=>'png'];
    }
    public function upload(array $file, string $slot, ?int $post = null): int
    {
        return $this->locked(fn(): int=>$this->store($file,$slot,$post));
    }
    public function locked(callable $operation): mixed
    {
        $this->app->needUser();
        $path=dirname(FRIENDS_CONFIG).'/upload-'.(int)$this->app->user['id'].'.lock';
        if (isset(self::$heldLocks[$path])) return $operation();
        $lock=fopen($path,'c');
        if (!$lock) throw new DomainException($this->app->t('upload_error'));
        try {
            if (!flock($lock,LOCK_EX)) throw new DomainException($this->app->t('upload_error'));
            self::$heldLocks[$path]=true;
            return $operation();
        } finally { unset(self::$heldLocks[$path]); flock($lock,LOCK_UN); fclose($lock); }
    }
    private function store(array $file, string $slot, ?int $post): int
    {
        $app=$this->app; $app->needUser();
        $imageSlot=in_array($slot,['avatar','header'],true);
        if (!in_array($slot,['post','avatar','header'],true)) throw new DomainException($app->t('invalid_input'));
        if ($imageSlot && $app->setting('profile_images','1')!=='1') throw new DomainException($app->t('uploads_disabled'));
        $max=max(1,min(intdiv(PHP_INT_MAX,1048576),(int)$app->setting('upload_mb','5')))*1048576;
        if (($file['error']??-1)!==UPLOAD_ERR_OK || !is_string($file['tmp_name']??null)
            || !is_uploaded_file($file['tmp_name']) || filesize($file['tmp_name'])>$max) throw new DomainException($app->t('upload_error'));
        $uid=(int)$app->user['id'];
        $app->throttle('upload:'.$uid,40,3600,false);
        $today=(int)$app->db->value('SELECT COALESCE(SUM(bytes),0) FROM f_media WHERE user_id=? AND created_at>=?',[$uid,gmdate('Y-m-d 00:00:00')]);
        $total=(int)$app->db->value('SELECT COALESCE(SUM(bytes),0) FROM f_media WHERE user_id=?',[$uid]);
        if ($today+filesize($file['tmp_name'])>max(10,min(500,(int)$app->setting('upload_daily_mb','100')))*1048576
            || $total+filesize($file['tmp_name'])>250*1048576) throw new DomainException($app->t('upload_quota'));
        $data=file_get_contents($file['tmp_name']);
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($data);
        if (str_starts_with($mime,'image/')) {
            if (!$imageSlot && $app->setting('feed_images','1')!=='1') throw new DomainException($app->t('uploads_disabled'));
            $processed=self::image($data); $data=$processed['data']; $mime=$processed['mime']; $extension='png';
        } else {
            if ($imageSlot || $app->setting('feed_files','1')!=='1') throw new DomainException($app->t('uploads_disabled'));
            $allowed=['application/pdf'=>'pdf','text/plain'=>'txt','application/zip'=>'zip'];
            $extension=$allowed[$mime]??'';
            if (!$extension || ($extension==='pdf' && !str_starts_with($data,'%PDF-'))
                || ($extension==='zip' && !str_starts_with($data,"PK\x03\x04"))
                || ($extension==='txt' && (str_contains($data,"\0") || !preg_match('//u',$data)))) throw new DomainException($app->t('upload_error'));
        }
        if (strlen($data)>$max) throw new DomainException($app->t('upload_error'));
        if ($today+strlen($data)>max(10,min(500,(int)$app->setting('upload_daily_mb','100')))*1048576
            || $total+strlen($data)>250*1048576) throw new DomainException($app->t('upload_quota'));
        if (!is_dir($this->directory()) && !mkdir($this->directory(),0700,true)) throw new DomainException($app->t('upload_error'));
        $filename=bin2hex(random_bytes(24)).'.'.$extension;
        $name=preg_replace('/[^\pL\pN ._()-]/u','_',basename((string)($file['name']??'file'))) ?: 'file';
        $name=iconv_substr($name,0,150,'UTF-8')?:'file';
        $path=$this->directory().'/'.$filename;
        if (file_put_contents($path,$data,LOCK_EX)!==strlen($data)) { @unlink($path); throw new DomainException($app->t('upload_error')); }
        @chmod($path,0600);
        try { return $app->db->insert('INSERT INTO f_media (user_id,post_id,slot,filename,original_name,mime,bytes,created_at) VALUES (?,?,?,?,?,?,?,?)',[$uid,$post,$slot,$filename,$name,$mime,strlen($data),$app->now()]); }
        catch (\Throwable $error) { @unlink($path); throw $error; }
    }
    public function remove(array $rows): void
    {
        foreach ($rows as $row) {
            if (preg_match('/^[a-f0-9]{48}\.(png|pdf|txt|zip)$/D',$row['filename'])) @unlink($this->directory().'/'.$row['filename']);
            $this->app->db->query('DELETE FROM f_media WHERE id=?',[$row['id']]);
        }
    }
    public function serve(): void
    {
        try {
            $app=$this->app; $app->needUser();
            $row=$app->db->one("SELECT m.* FROM f_media m JOIN f_users u ON u.id=m.user_id WHERE m.id=? AND u.status='active'",[(int)($_GET['id']??0)]);
            if (!$row) throw new DomainException($app->t('not_found'));
            if ($row['post_id']) $app->visiblePost((int)$row['post_id']);
            elseif (!(new CommunityImages($app))->linked((int)$row['id']) && !$app->db->one('SELECT user_id FROM f_profiles WHERE user_id=? AND (avatar_id=? OR header_id=?)',[$row['user_id'],$row['id'],$row['id']])) throw new DomainException($app->t('not_found'));
            if (!preg_match('/^[a-f0-9]{48}\.(png|pdf|txt|zip)$/D',$row['filename'])) throw new DomainException($app->t('not_found'));
            $path=$this->directory().'/'.$row['filename'];
            if (!is_file($path)) throw new DomainException($app->t('not_found'));
            header('Content-Type: '.$row['mime']);
            header('Content-Length: '.filesize($path));
            header("Content-Security-Policy: default-src 'none'; sandbox");
            $inline=$row['mime']==='image/png';
            header("Content-Disposition: ".($inline?'inline':'attachment')."; filename=\"file." . pathinfo($row['filename'],PATHINFO_EXTENSION)."\"; filename*=UTF-8''".rawurlencode($row['original_name']));
            session_write_close(); readfile($path);
        } catch (DomainException) { http_response_code(404); }
    }
}
