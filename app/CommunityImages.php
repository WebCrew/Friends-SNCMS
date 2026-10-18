<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class CommunityImages
{
    public function __construct(private App $app) {}
    public function images(int $id): array
    {
        $result=[];
        foreach(['avatar','header'] as $slot) $result[$slot]=(int)$this->app->db->value('SELECT value FROM f_settings WHERE name=?',['community.'.$id.'.'.$slot]);
        return $result;
    }
    public function linked(int $media): bool
    {
        foreach($this->app->db->all("SELECT name FROM f_settings WHERE name LIKE 'community.%' AND value=?",[(string)$media]) as $row) {
            if (preg_match('/^community\.([1-9][0-9]*)\.(avatar|header)$/D',$row['name'],$m)
                && $this->app->db->one('SELECT id FROM f_communities WHERE id=?',[$m[1]])) return true;
        }
        return false;
    }
    public function save(int $id): void
    {
        $a=$this->app; $a->needUser();
        $c=$a->db->one('SELECT * FROM f_communities WHERE id=?',[$id]);
        if (!$c || ((int)$c['owner_id']!==(int)$a->user['id'] && $a->user['role']!=='admin')) throw new DomainException($a->t('forbidden'));
        $lock=fopen(dirname(FRIENDS_CONFIG).'/community-'.$id.'.lock','c');
        if (!$lock) throw new DomainException($a->t('upload_error'));
        try {
            if (!flock($lock,LOCK_EX)) throw new DomainException($a->t('upload_error'));
            (new Media($a))->locked(function() use($a,$id):void {
                $before=$this->images($id); $next=$before; $created=[];
                try {
                    foreach(['avatar','header'] as $slot) {
                        if (isset($_POST['remove_'.$slot])) $next[$slot]=0;
                        if (($_FILES[$slot]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
                            $next[$slot]=(new Media($a))->upload($_FILES[$slot],$slot); $created[]=$next[$slot];
                        }
                    }
                    $a->db->transaction(function() use($a,$id,$next):void {
                        foreach($next as $slot=>$media) $a->saveSetting('community.'.$id.'.'.$slot,(string)$media);
                    });
                } catch (\Throwable $error) {
                    foreach($created as $media) (new Media($a))->remove($a->db->all('SELECT * FROM f_media WHERE id=?',[$media]));
                    throw $error;
                }
                foreach($before as $slot=>$media) if ($media && $media!==$next[$slot]) (new Media($a))->remove($a->db->all('SELECT * FROM f_media WHERE id=?',[$media]));
            });
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
}
