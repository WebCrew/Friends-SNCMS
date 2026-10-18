<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
final class Admin
{
    public function __construct(private App $app) {}
    public function data(): array
    {
        $app=$this->app; $app->needAdmin(); $db=$app->db;
        $section=(string)($_GET['section']??'overview');
        $data=['counts'=>[],'members'=>[],'memberToEdit'=>null,'documentToEdit'=>null,'documents'=>[],'campaigns'=>[],'queueStatus'=>[],'reports'=>[],'audit'=>[],'search'=>''];
        if ($section==='overview') foreach(['users','posts','communities','reports'] as $table) $data['counts'][$table]=(int)$db->value('SELECT COUNT(*) FROM f_'.$table);
        if ($section==='member_management') {
            $data['search']=trim(substr((string)($_GET['q']??''),0,100));
            $data['members']=$db->all('SELECT id,name,username,role,status FROM f_users WHERE name LIKE ? OR username LIKE ? ORDER BY id DESC LIMIT 100',['%'.$data['search'].'%','%'.$data['search'].'%']);
            $data['memberToEdit']=$db->one('SELECT id,name,username,email,bio,role,status,locale FROM f_users WHERE id=?',[(int)($_GET['edit']??0)]);
        }
        if ($section==='legal_pages') {
            $data['documentToEdit']=$db->one('SELECT * FROM f_documents WHERE id=?',[(int)($_GET['edit']??0)]);
            $data['documents']=$db->all('SELECT * FROM f_documents ORDER BY slug');
        }
        if ($section==='mail_settings') {
            $data['campaigns']=$db->all('SELECT * FROM f_campaigns ORDER BY id DESC LIMIT 20');
            if ($data['campaigns']) {
                $ids=array_column($data['campaigns'],'id'); $marks=implode(',',array_fill(0,count($ids),'?'));
                foreach ($db->all("SELECT campaign_id,status,COUNT(*) AS total FROM f_mail_queue WHERE campaign_id IN ($marks) GROUP BY campaign_id,status",$ids) as $row) $data['queueStatus'][$row['campaign_id']][]=$row;
            }
        }
        if ($section==='open_reports') $data['reports']=$db->all("SELECT r.*,p.body,u.name FROM f_reports r JOIN f_posts p ON p.id=r.post_id JOIN f_users u ON u.id=r.user_id WHERE r.status='open' ORDER BY r.id DESC LIMIT 50");
        if ($section==='audit') $data['audit']=$db->all('SELECT a.*,u.name FROM f_audit a JOIN f_users u ON u.id=a.actor_id ORDER BY a.id DESC LIMIT 100');
        return $data;
    }
}
