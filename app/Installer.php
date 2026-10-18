<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class Installer
{
    public function __construct(private App $app) {}
    public static function databaseErrorKey(int $driverCode): string
    {
        return match($driverCode) {
            1045=>'db_auth_error',
            1044=>'db_permission_error',
            1049=>'db_missing_error',
            2002,2003=>'db_unreachable_error',
            2005=>'db_host_error',
            2026=>'db_tls_error',
            1040=>'db_busy_error',
            default=>'db_connect_error'
        };
    }
    public function checks(): array { return ['PHP ≥ 8.4'=>PHP_VERSION_ID>=80400,'PDO MySQL'=>extension_loaded('pdo_mysql'),'Fileinfo'=>extension_loaded('fileinfo'),'GD (images)'=>extension_loaded('gd'),'cURL (previews)'=>extension_loaded('curl'),'ZIP (packages)'=>extension_loaded('zip'),'OpenSSL (SMTP)'=>extension_loaded('openssl'),'DOM / Iconv'=>extension_loaded('dom') && extension_loaded('iconv'),'storage writable'=>is_writable(FRIENDS_ROOT.'/storage'),'password_hash / random_bytes'=>function_exists('password_hash') && function_exists('random_bytes')]; }
    private function database(array $config): Db
    {
        try {
            $db=new Db($config);
            $version=(string)$db->value('SELECT VERSION()');
            if (version_compare($version,'5.7.0','<')) throw new DomainException($this->app->t('db_version'));
            if ($db->all('SHOW TABLES')) throw new DomainException($this->app->t('db_empty'));
            return $db;
        } catch (\PDOException $error) {
            // Driver codes distinguish socket/network/authentication failures without recording
            // the exception message, host, account, database name or password.
            $diagnostic=['time'=>gmdate('c'),'sqlstate'=>(string)$error->getCode(),'driver_code'=>(int)($error->errorInfo[1]??0)];
            error_log('Friends installer: SQLSTATE '.$diagnostic['sqlstate'].', driver '.$diagnostic['driver_code']);
            $path=dirname(FRIENDS_CONFIG).'/installer-diagnostic.json';
            @file_put_contents($path,json_encode($diagnostic,JSON_THROW_ON_ERROR)."\n",LOCK_EX);
            @chmod($path,0600);
            throw new DomainException($this->app->t(self::databaseErrorKey($diagnostic['driver_code'])));
        }
    }
    public function post(): void
    {
        if (is_file(FRIENDS_CONFIG) || is_file(FRIENDS_ROOT.'/storage/installed.lock')) throw new DomainException($this->app->t('installed'));
        $action=$_POST['action']??'';
        if ($action==='install_back') { $_SESSION['install_step']=max(1,(int)($_SESSION['install_step']??1)-1); return; }
        $step=(int)($_SESSION['install_step']??1);
        if ($action==='install_check' && $step===1) {
            if (in_array(false,$this->checks(),true)) throw new DomainException($this->app->t('requirements_failed'));
            $_SESSION['install_step']=2; return;
        }
        if ($action==='install_database' && $step===2) {
            $host=$this->app->text('host',190); $database=$this->app->text('database',64);
            if (!preg_match('/^[a-zA-Z0-9_.:-]+$/D',$host) || !preg_match('/^[a-zA-Z0-9_]+$/D',$database)) throw new DomainException($this->app->t('invalid_input'));
            $port=filter_var($_POST['port']??3306,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
            if (!$port) throw new DomainException($this->app->t('invalid_input'));
            $config=['dsn'=>"mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",'user'=>$this->app->text('db_user',100),'password'=>(string)($_POST['db_password']??'')];
            $this->database($config); $_SESSION['install_db']=$config; $_SESSION['install_step']=3; return;
        }
        if ($action!=='install_finish' || $step!==3 || empty($_SESSION['install_db'])) throw new DomainException($this->app->t('invalid_input'));
        $username=$this->app->text('username',40,3); $email=$this->app->text('email',190); $password=$this->app->text('password',72,12);
        $name=$this->app->text('name',100); $site=$this->app->text('site_name',80);
        if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/D',$username) || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)>72) throw new DomainException($this->app->t('invalid_input'));
        $lock=fopen(FRIENDS_ROOT.'/storage/install.mutex','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) throw new DomainException($this->app->t('install_busy'));
        try {
            if (is_file(FRIENDS_CONFIG) || is_file(FRIENDS_ROOT.'/storage/installed.lock')) throw new DomainException($this->app->t('installed'));
            $config=$_SESSION['install_db']; $db=$this->database($config);
            // MySQL DDL commits implicitly. Refuse non-empty databases; do not claim transactional DDL.
            $db->migrate();
            $db->transaction(function () use ($db,$username,$email,$password,$name,$site): void {
                $db->query('INSERT INTO f_users (username,email,password,name,bio,role,locale,created_at) VALUES (?,?,?,?,?,?,?,?)',[$username,$email,password_hash($password,PASSWORD_DEFAULT),$name,'','admin',$this->app->locale,$this->app->now()]);
                foreach(['site_name'=>$site,'locale'=>$this->app->locale,'registration'=>'1','theme'=>'friends','schema_version'=>'3','plugin.welcome'=>'1','secret'=>bin2hex(random_bytes(32))] as $key=>$value) $db->query('INSERT INTO f_settings (name,value) VALUES (?,?)',[$key,$value]);
            });
            $temp=FRIENDS_CONFIG.'.tmp.'.bin2hex(random_bytes(8));
            if (file_put_contents($temp,"<?php\n// Private installer-generated configuration. Never serve through HTTP.\nreturn ".var_export($config,true).";\n",LOCK_EX)===false) throw new DomainException($this->app->t('config_error'));
            @chmod($temp,0600);
            if (!rename($temp,FRIENDS_CONFIG)) throw new DomainException($this->app->t('config_error'));
            file_put_contents(FRIENDS_ROOT.'/storage/installed.lock',$this->app->now(),LOCK_EX);
            @unlink(dirname(FRIENDS_CONFIG).'/installer-diagnostic.json');
            unset($_SESSION['install_db'],$_SESSION['install_step']); session_regenerate_id(true); unset($_SESSION['csrf']);
            $_SESSION['success']=$this->app->t('install_done'); $this->app->redirect('login');
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
}
