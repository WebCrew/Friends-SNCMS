<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class App
{
    public ?Db $db = null;
    public ?array $user = null;
    public string $locale;
    public string $route;
    public Extensions $extensions;
    private array $strings;
    private array $settings = [];
    public function __construct()
    {
        $this->locale = in_array($_SESSION['locale'] ?? '', ['de', 'en'], true) ? $_SESSION['locale'] : 'de';
        if (is_file(FRIENDS_CONFIG)) {
            $this->db = new Db(require FRIENDS_CONFIG);
            $this->settings = array_column($this->db->all('SELECT name,value FROM f_settings'), 'value', 'name');
            $this->user = $this->db->one("SELECT * FROM f_users WHERE id=? AND status='active'", [(int) ($_SESSION['user_id'] ?? 0)]);
            if ($this->user) {
                $installation = $_SESSION['installation'] ?? null;
                $sameInstallation = is_string($installation) && hash_equals(hash('sha256', $this->setting('secret')), $installation);
                $sameVersion = (int) ($_SESSION['auth_version'] ?? 1) === (int) $this->user['auth_version'];
                $idleExpired = time() - (int) ($_SESSION['last_seen'] ?? time()) > 1800;
                $lifetimeExpired = time() - (int) ($_SESSION['signed_in_at'] ?? time()) > 43200;
                if (!$sameInstallation || !$sameVersion || $idleExpired || $lifetimeExpired) $this->user = null;
            }
            if (!$this->user) unset($_SESSION['user_id']);
            else { $_SESSION['last_seen']=time(); $_SESSION['signed_in_at']??=time(); }
            if (!isset($_SESSION['locale'])) $this->locale = $this->user['locale'] ?? $this->setting('locale', 'de');
        }
        $this->strings = require FRIENDS_ROOT . '/resources/lang/' . $this->locale . '.php';
        $this->route = (string) ($_GET['r'] ?? ($this->db ? 'feed' : 'install'));
        $this->extensions = new Extensions();
        if ($this->db) $this->extensions->boot($this);
    }
    public function t(string $key): string { return $this->strings[$key] ?? $key; }
    public function setting(string $key, string $fallback = ''): string { return $this->settings[$key] ?? $fallback; }
    public function saveSetting(string $key, string $value): void
    {
        if ($this->db->one('SELECT name FROM f_settings WHERE name=?', [$key])) $this->db->query('UPDATE f_settings SET value=? WHERE name=?', [$value, $key]);
        else $this->db->query('INSERT INTO f_settings (name,value) VALUES (?,?)', [$key, $value]);
        $this->settings[$key] = $value;
    }
    public function url(string $route = 'feed', array $params = []): string { return 'index.php?' . http_build_query(['r' => $route] + $params); }
    public function redirect(string $route = 'feed', array $params = []): never { header('Location: ' . $this->url($route, $params), true, 303); exit; }
    public function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
    public function field(): string { return '<input type="hidden" name="csrf" value="' . e($this->csrf()) . '">'; }
    public function now(): string { return gmdate('Y-m-d H:i:s'); }
    public function text(string $key, int $max, int $min = 1): string
    {
        $raw=$_POST[$key]??'';
        if (!is_string($raw)) throw new DomainException($this->t('invalid_input'));
        $value = trim($raw);
        $length = preg_match_all('/./us', $value);
        if ($length === false || $length < $min || $length > $max) throw new DomainException($this->t('invalid_input') . ' (' . $key . ': ' . $min . '–' . $max . ')');
        return $value;
    }
    public function needUser(): void { if (!$this->user) throw new DomainException($this->t('login_required')); }
    public function needAdmin(): void { $this->needUser(); if ($this->user['role'] !== 'admin') throw new DomainException($this->t('forbidden')); }
    public function visiblePost(int $id): array
    {
        $post = $this->db->one("SELECT p.* FROM f_posts p JOIN f_users u ON u.id=p.user_id WHERE p.id=? AND u.status='active' AND (p.visibility='members' OR p.user_id=?)", [$id, $this->user['id']]);
        if (!$post) throw new DomainException($this->t('not_found'));
        return $post;
    }
    public function friends(int $a, int $b): bool
    {
        return (bool) $this->db->value("SELECT COUNT(*) FROM f_friendships WHERE low_id=? AND high_id=? AND status='accepted'", [min($a,$b),max($a,$b)]);
    }
    public function notify(int $user, string $kind, int $target): void
    {
        if ($user !== (int) $this->user['id']) $this->db->query('INSERT INTO f_notifications (user_id,actor_id,kind,target_id,created_at) VALUES (?,?,?,?,?)', [$user,$this->user['id'],$kind,$target,$this->now()]);
    }
    public function throttle(string $scope, int $limit, int $seconds, bool $byIp = true): void
    {
        // The bucket contains an HMAC, never a raw email address or IP address.
        $bucket = hash_hmac('sha256', $scope . '|' . ($byIp ? ($_SERVER['REMOTE_ADDR'] ?? 'cli') : 'account'), $this->setting('secret'));
        $this->db->query('DELETE FROM f_throttles WHERE expires_at<?', [time()]);
        $driver = $this->db->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'mysql'
            ? 'INSERT INTO f_throttles (bucket,attempts,expires_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=attempts+1'
            : 'INSERT INTO f_throttles (bucket,attempts,expires_at) VALUES (?,1,?) ON CONFLICT(bucket) DO UPDATE SET attempts=attempts+1';
        $this->db->query($sql, [$bucket,time()+$seconds]);
        if ((int) $this->db->value('SELECT attempts FROM f_throttles WHERE bucket=?', [$bucket]) > $limit) throw new DomainException($this->t('rate_limit'));
    }
    public function run(): void
    {
        if ($this->db && $this->setting('schema_version','3') !== '3') { $this->upgradeScreen(); return; }
        if ($this->route==='media') { (new Media($this))->serve(); return; }
        if ($this->route==='link_preview') { (new Features($this))->previewApi(); return; }
        if ($this->route==='theme_css') { $this->themeCss(); return; }
        if ($this->route==='voice_api') { (new VoiceRoom($this))->serve(); return; }
        if (in_array($this->route,['chat_api','chat_media'],true)) { (new Chat($this))->serve(); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!hash_equals($this->csrf(), (string) ($_POST['csrf'] ?? ''))) throw new DomainException($this->t('csrf_error'));
                $action = (string) ($_POST['action'] ?? '');
                if ($action === 'language') {
                    $_SESSION['locale'] = in_array($_POST['locale'] ?? '', ['de','en'], true) ? $_POST['locale'] : 'de';
                    $this->redirect($this->route, array_intersect_key($_GET, array_flip(['id','q','kind','page','section','slug','token','edit'])));
                }
                if (!$this->db) (new Installer($this))->post();
                else $this->act($action);
            } catch (DomainException $error) { $_SESSION['error'] = $error->getMessage(); }
            catch (\PDOException $error) { error_log((string) $error); $_SESSION['error'] = $this->t('database_error'); }
            $this->redirect($this->db ? (in_array($this->route, ['forgot','reset','verify','login','register','feed','profile','people','groups','pages','community','messages','notifications','settings','admin','static','contact','unsubscribe'], true) ? $this->route : 'feed') : 'install', array_intersect_key($_GET, array_flip(['id','q','kind','page','section','slug','token','edit'])));
        }
        if (!$this->db) { $this->route = 'install'; $this->view('install', ['installer' => new Installer($this)]); return; }
        if (!$this->user && !in_array($this->route, ['forgot','reset','verify','login','register','static','contact','unsubscribe'], true)) $this->redirect('login');
        if ($this->user && in_array($this->route, ['forgot','reset','verify','login','register','install'], true)) $this->redirect();
        if (!in_array($this->route, ['forgot','reset','verify','feed','login','register','profile','people','groups','pages','community','messages','notifications','settings','admin','static','contact','unsubscribe'], true)) { http_response_code(404); $this->view('error', ['message' => $this->t('not_found')]); return; }
        try { $this->view($this->route, $this->data()); }
        catch (DomainException $error) { http_response_code(403); $this->view('error', ['message' => $error->getMessage()]); }
    }
    public function act(string $action): void
    {
        if ((new AccountMail($this))->act($action)) return;
        if ($action === 'login' || $action === 'register') {
            $this->throttle($action, $action === 'login' ? 10 : 5, 900);
            if ($action === 'register') {
                if ($this->setting('registration', '1') !== '1') throw new DomainException($this->t('registration_closed'));
                AntiSpam::registration($this);
                (new AccountMail($this))->ready();
                $username = $this->text('username', 40, 3);
                $email = $this->text('email', 190);
                if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/D', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException($this->t('invalid_input'));
                $password = $this->text('password', 72, 12);
                if (strlen($password) > 72) throw new DomainException($this->t('password_length'));
                if ($this->db->one('SELECT id FROM f_users WHERE username=? OR email=?', [$username,$email])) throw new DomainException($this->t('account_exists'));
                $id = $this->db->insert('INSERT INTO f_users (username,email,password,name,bio,locale,created_at,status) VALUES (?,?,?,?,?,?,?,?)', [$username,$email,password_hash($password,PASSWORD_DEFAULT),$this->text('name',100),'',$this->locale,$this->now(),'pending']);
                try { (new AccountMail($this))->verification($id); }
                catch (\Throwable $error) { $this->db->query("DELETE FROM f_users WHERE id=? AND status='pending'",[$id]); throw $error; }
                $_SESSION['success']=$this->t('verify_sent');
                $this->redirect('login');
            } else {
                $identity = $this->text('identity',190);
                $account = $this->db->one('SELECT * FROM f_users WHERE username=? OR email=?', [$identity,$identity]);
                $password = (string) ($_POST['password'] ?? '');
                $valid = password_verify($password, $account['password'] ?? '$2y$12$P2YGCagTNJRFJrMSVS5XC.8gsl/Id/YSqBx3wEbJPIFD3gDH0IWE.');
                if (!$account || !$valid || $account['status'] !== 'active') throw new DomainException($this->t('bad_login'));
                $id = (int) $account['id'];
                if (password_needs_rehash($account['password'], PASSWORD_DEFAULT)) $this->db->query('UPDATE f_users SET password=? WHERE id=?', [password_hash($password,PASSWORD_DEFAULT),$id]);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['installation'] = hash('sha256', $this->setting('secret'));
            $_SESSION['auth_version'] = (int) $this->db->value('SELECT auth_version FROM f_users WHERE id=?', [$id]);
            $_SESSION['signed_in_at'] = time();
            $_SESSION['last_seen'] = time();
            unset($_SESSION['csrf']);
            $this->redirect();
        }
        if ((new Features($this))->publicAction($action)) { $_SESSION['success']=$this->t('saved'); return; }
        $this->needUser();
        $uid = (int) $this->user['id']; $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'logout') { $_SESSION=[]; session_regenerate_id(true); $this->redirect('login'); }
        $this->throttle('write:' . $uid, 120, 60, false);
        if ((new Features($this))->act($action)) { $_SESSION['success']=$this->t('saved'); return; }
        switch ($action) {
            case 'like':
                $post=$this->visiblePost($id);
                if ($this->db->one('SELECT user_id FROM f_likes WHERE post_id=? AND user_id=?',[$id,$uid])) $this->db->query('DELETE FROM f_likes WHERE post_id=? AND user_id=?',[$id,$uid]);
                else { $this->db->query('INSERT INTO f_likes (post_id,user_id) VALUES (?,?)',[$id,$uid]); $this->notify((int)$post['user_id'],'like',$id); } break;
            case 'comment':
                $post=$this->visiblePost($id);
                $body=$this->text('body',2000); AntiSpam::content($this,'comment',$body);
                $this->db->query('INSERT INTO f_comments (post_id,user_id,body,created_at) VALUES (?,?,?,?)',[$id,$uid,$body,$this->now()]);
                $this->notify((int)$post['user_id'],'comment',$id); break;
            case 'delete_post':
                $post=$this->db->one('SELECT * FROM f_posts WHERE id=?',[$id]);
                if (!$post || ((int)$post['user_id']!==$uid && $this->user['role']!=='admin')) throw new DomainException($this->t('forbidden'));
                $media=$this->db->all('SELECT * FROM f_media WHERE post_id=?',[$id]);
                $this->db->transaction(function () use ($id,$uid) { $this->db->query('INSERT INTO f_audit (actor_id,action,target_id,created_at) VALUES (?,?,?,?)',[$uid,'delete_post',$id,$this->now()]); $this->db->query('DELETE FROM f_posts WHERE id=?',[$id]); }); (new Media($this))->remove($media); break;
            case 'report':
                $this->visiblePost($id);
                if (!$this->db->one('SELECT id FROM f_reports WHERE user_id=? AND post_id=?',[$uid,$id])) $this->db->query('INSERT INTO f_reports (user_id,post_id,reason,created_at) VALUES (?,?,?,?)',[$uid,$id,$this->text('reason',500),$this->now()]); break;
            case 'friend':
                if ($id===$uid || !$this->db->one("SELECT id FROM f_users WHERE id=? AND status='active'",[$id])) throw new DomainException($this->t('invalid_input'));
                $args=[min($uid,$id),max($uid,$id)];
                $f=$this->db->one('SELECT * FROM f_friendships WHERE low_id=? AND high_id=?',$args);
                if (!$f) { if ($this->setting('spam_protection','1')==='1') $this->throttle('friend:'.$uid,20,86400,false); $this->db->query('INSERT INTO f_friendships (low_id,high_id,requester_id) VALUES (?,?,?)',[...$args,$uid]); $this->notify($id,'friend',$uid); }
                elseif ($f['status']==='pending' && (int)$f['requester_id']!==$uid && ($_POST['choice']??'')==='accept') { $this->db->query("UPDATE f_friendships SET status='accepted' WHERE id=?",[$f['id']]); $this->notify($id,'accepted',$uid); }
                elseif (($_POST['choice']??'')==='remove') $this->db->query('DELETE FROM f_friendships WHERE id=?',[$f['id']]);
                break;
            case 'community':
                $kind=($_POST['kind']??'')==='page'?'page':'group';
                $name=$this->text('name',100); $description=$this->text('description',1000,0);
                AntiSpam::content($this,'community',$name.' '.$description);
                $this->db->transaction(function () use ($uid,$kind,$name,$description) { $id=$this->db->insert('INSERT INTO f_communities (owner_id,kind,name,description,created_at) VALUES (?,?,?,?,?)',[$uid,$kind,$name,$description,$this->now()]); $this->db->query('INSERT INTO f_memberships (community_id,user_id) VALUES (?,?)',[$id,$uid]); }); break;
            case 'join':
                $c=$this->db->one('SELECT * FROM f_communities WHERE id=?',[$id]);
                if (!$c) throw new DomainException($this->t('not_found'));
                if ((int)$c['owner_id']===$uid) throw new DomainException($this->t('owner_member'));
                if ($this->db->one('SELECT user_id FROM f_memberships WHERE community_id=? AND user_id=?',[$id,$uid])) $this->db->query('DELETE FROM f_memberships WHERE community_id=? AND user_id=?',[$id,$uid]);
                else $this->db->query('INSERT INTO f_memberships (community_id,user_id) VALUES (?,?)',[$id,$uid]); break;
            case 'message':
                if (!$this->friends($uid,$id) || !$this->db->one("SELECT id FROM f_users WHERE id=? AND status='active'",[$id])) throw new DomainException($this->t('friends_only'));
                $body=$this->text('body',5000); AntiSpam::content($this,'message',$body);
                $this->db->query('INSERT INTO f_messages (sender_id,recipient_id,body,created_at) VALUES (?,?,?,?)',[$uid,$id,$body,$this->now()]); $this->notify($id,'message',$uid); break;
            case 'seen': $this->db->query('UPDATE f_notifications SET seen=1 WHERE user_id=?',[$uid]); break;
            case 'password':
                if (!password_verify((string)($_POST['current_password']??''),$this->user['password'])) throw new DomainException($this->t('bad_login'));
                $password=$this->text('password',72,12);
                if (strlen($password)>72) throw new DomainException($this->t('password_length'));
                $this->db->query('UPDATE f_users SET password=?,auth_version=auth_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$uid]); $_SESSION['auth_version']=(int)$this->user['auth_version']+1; session_regenerate_id(true); unset($_SESSION['csrf']); break;
            case 'admin_spam':
                $this->needAdmin(); $this->saveSetting('spam_protection',isset($_POST['spam_protection'])?'1':'0'); $this->audit($action,0); break;
            case 'admin_settings':
                $this->needAdmin();
                if (isset($_POST['ice_servers'])) {
                    $ice=Chat::validateIce((string)$_POST['ice_servers']);
                    $this->saveSetting('ice_servers',json_encode($ice,JSON_THROW_ON_ERROR));
                }
                $this->saveSetting('site_name',$this->text('site_name',80));
                $this->saveSetting('registration',isset($_POST['registration'])?'1':'0');
                $theme=(string)($_POST['theme']??'');
                if (!isset($this->extensions->discover('themes')[$theme]) || $this->setting('theme.'.$theme,'1')!=='1') throw new DomainException($this->t('invalid_input'));
                $this->saveSetting('theme',$theme); $this->audit($action,0); break;
            case 'plugin':
                $this->needAdmin(); $plugin=(string)($_POST['plugin']??'');
                if (!isset($this->extensions->discover('plugins')[$plugin])) throw new DomainException($this->t('invalid_input'));
                if ($this->setting('installed.plugins.'.$plugin)==='1' && $this->setting('plugin.'.$plugin,'0')!=='1' && (!isset($_POST['trust_package']) || !password_verify((string)($_POST['current_password']??''),$this->user['password']))) throw new DomainException($this->t('package_trust'));
                $this->saveSetting('plugin.'.$plugin,$this->setting('plugin.'.$plugin,'0')==='1'?'0':'1'); $this->audit($action,0); break;
            case 'suspend':
                $this->needAdmin();
                if ($id===$uid || !$this->db->one("SELECT id FROM f_users WHERE id=? AND role<>'admin'",[$id])) throw new DomainException($this->t('forbidden'));
                $this->db->query("UPDATE f_users SET status=CASE WHEN status='active' THEN 'suspended' ELSE 'active' END,auth_version=auth_version+1 WHERE id=?",[$id]); $this->audit($action,$id); break;
            case 'resolve':
                $this->needAdmin(); $this->db->query("UPDATE f_reports SET status='closed' WHERE id=?",[$id]); $this->audit($action,$id); break;
            default: throw new DomainException($this->t('invalid_input'));
        }
        $_SESSION['success']=$this->t('saved');
    }
    private function audit(string $action, int $id): void { $this->db->query('INSERT INTO f_audit (actor_id,action,target_id,created_at) VALUES (?,?,?,?)',[$this->user['id'],$action,$id,$this->now()]); }
    public function posts(?int $community = null, ?int $profile = null): array
    {
        $uid=(int)$this->user['id']; $args=[$uid]; $where="u.status='active' AND (p.visibility='members' OR p.user_id=?)";
        if ($community!==null) { $where.=' AND p.community_id=?'; $args[]=$community; }
        if ($profile!==null) { $where.=' AND p.user_id=?'; $args[]=$profile; }
        $search=trim((string)($_GET['q']??''));
        if ($search!=='') { $where.=' AND p.body LIKE ?'; $args[]='%'.substr($search,0,100).'%'; }
        $page=max(1,min(10000,(int)($_GET['page']??1))); $offset=($page-1)*20;
        $posts=$this->db->all("SELECT p.*,u.name,u.username,c.name AS community_name,(SELECT COUNT(*) FROM f_likes l WHERE l.post_id=p.id) AS likes,(SELECT COUNT(*) FROM f_likes l WHERE l.post_id=p.id AND l.user_id=$uid) AS liked FROM f_posts p JOIN f_users u ON u.id=p.user_id LEFT JOIN f_communities c ON c.id=p.community_id WHERE $where ORDER BY p.id DESC LIMIT 20 OFFSET $offset",$args);
        if ($posts) {
            $ids=array_column($posts,'id'); $marks=implode(',',array_fill(0,count($ids),'?'));
            // One query for all visible comments, rather than one query for every post.
            $comments=$this->db->all("SELECT c.*,u.name FROM f_comments c JOIN f_users u ON u.id=c.user_id WHERE c.post_id IN ($marks) AND u.status='active' ORDER BY c.id",$ids);
            $grouped=[]; foreach($comments as $comment) $grouped[$comment['post_id']][]=$comment;
            $media=[]; foreach($this->db->all("SELECT * FROM f_media WHERE post_id IN ($marks) ORDER BY id",$ids) as $item) $media[$item['post_id']][]=$item;
            $links=[]; foreach($this->db->all("SELECT * FROM f_post_links WHERE post_id IN ($marks)",$ids) as $item) $links[$item['post_id']]=$item;
            foreach($posts as &$post) { $post['comments']=$grouped[$post['id']]??[]; $post['attachments']=$media[$post['id']]??[]; $post['link']=$links[$post['id']]??null; }
        }
        return $posts;
    }
    private function data(): array
    {
        if ($this->route==='static') {
            $document=$this->db->one('SELECT * FROM f_documents WHERE slug=? AND published=1',[(string)($_GET['slug']??'')]);
            if (!$document) throw new DomainException($this->t('not_found'));
            return ['document'=>$document];
        }
        if ($this->route==='contact' && $this->setting('contact_enabled')!=='1') throw new DomainException($this->t('not_found'));
        if (!$this->user) return [];
        $uid=(int)$this->user['id'];
        switch ($this->route) {
            case 'feed': return ['posts'=>$this->posts()];
            case 'people':
                $search=substr(trim((string)($_GET['q']??'')),0,100);
                return ['people'=>$this->db->all("SELECT id,name,username,bio FROM f_users WHERE status='active' AND (name LIKE ? OR username LIKE ?) ORDER BY id DESC LIMIT 60",['%'.$search.'%','%'.$search.'%'])];
            case 'profile':
                $id=(int)($_GET['id']??$uid); $profile=$this->db->one("SELECT id,name,username,bio,created_at FROM f_users WHERE id=? AND status='active'",[$id]);
                if (!$profile) throw new DomainException($this->t('not_found'));
                return ['profile'=>$profile,'about'=>(new Features($this))->profile($id),'friendship'=>$this->db->one('SELECT * FROM f_friendships WHERE low_id=? AND high_id=?',[min($uid,$id),max($uid,$id)]),'posts'=>$this->posts(null,$id)];
            case 'groups': case 'pages':
                return ['communities'=>$this->db->all('SELECT c.*,(SELECT COUNT(*) FROM f_memberships m WHERE m.community_id=c.id) AS members FROM f_communities c WHERE kind=? ORDER BY id DESC LIMIT 60',[$this->route==='pages'?'page':'group'])];
            case 'community':
                $id=(int)($_GET['id']??0); $community=$this->db->one('SELECT * FROM f_communities WHERE id=?',[$id]);
                if (!$community) throw new DomainException($this->t('not_found'));
                return ['community'=>$community,'joined'=>(bool)$this->db->one('SELECT user_id FROM f_memberships WHERE community_id=? AND user_id=?',[$id,$uid]),'posts'=>$this->posts($id)];
            case 'messages':
                $contacts=$this->db->all("SELECT u.id,u.name,u.username FROM f_users u JOIN f_friendships f ON (u.id=f.low_id OR u.id=f.high_id) WHERE (f.low_id=? OR f.high_id=?) AND f.status='accepted' AND u.id<>? AND u.status='active' ORDER BY u.name LIMIT 100",[$uid,$uid,$uid]);
                $id=(int)($_GET['id']??($contacts[0]['id']??0)); $contact=null; foreach($contacts as $person) if ((int)$person['id']===$id) $contact=$person;
                $messages=$contact?$this->db->all('SELECT * FROM (SELECT * FROM f_messages WHERE (sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?) ORDER BY id DESC LIMIT 100) recent ORDER BY id',[$uid,$id,$id,$uid]):[];
                return compact('contacts','contact','messages');
            case 'notifications': return ['notifications'=>$this->db->all('SELECT n.*,u.name FROM f_notifications n JOIN f_users u ON u.id=n.actor_id WHERE n.user_id=? ORDER BY n.id DESC LIMIT 100',[$uid])];
            case 'admin': return (new Admin($this))->data();
            default: return [];
        }
    }
    private function upgradeScreen(): void
    {
        if (!$this->user || $this->user['role']!=='admin') {
            if ($this->route==='login' && $_SERVER['REQUEST_METHOD']==='POST' && hash_equals($this->csrf(),(string)($_POST['csrf']??''))) {
                try { $this->act('login'); } catch (DomainException $error) { $_SESSION['error']=$error->getMessage(); }
            }
            $this->route='login'; $this->view('login'); return;
        }
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            try {
                if (!hash_equals($this->csrf(),(string)($_POST['csrf']??'')) || !isset($_POST['backup_confirmed'])) throw new DomainException($this->t('csrf_error'));
                $lock=fopen(dirname(FRIENDS_CONFIG).'/upgrade.lock','c');
                if (!$lock) throw new DomainException($this->t('config_error'));
                try { flock($lock,LOCK_EX); Upgrade::apply($this->db); }
                finally { flock($lock,LOCK_UN); fclose($lock); }
                $this->redirect('admin');
            } catch (DomainException|\PDOException $error) { error_log('Friends upgrade failed: '.$error->getCode()); $_SESSION['error']=$this->t('database_error'); }
        }
        $this->view('upgrade');
    }
    private function themeCss(): void
    {
        $theme=$this->setting('theme','friends');
        $items=$this->extensions->discover('themes');
        header('Content-Type: text/css; charset=utf-8');
        if (!isset($items[$theme]) || $this->setting('theme.'.$theme,'1')!=='1') return;
        $path=FRIENDS_ROOT.'/themes/'.$theme.'/style.css';
        if (is_file($path)) readfile($path);
    }
    public function view(string $view, array $data = []): void
    {
        $app=$this; extract($data,EXTR_SKIP);
        $error=$_SESSION['error']??null; $success=$_SESSION['success']??null; unset($_SESSION['error'],$_SESSION['success']);
        require FRIENDS_ROOT . '/resources/views/layout.php';
    }
}
