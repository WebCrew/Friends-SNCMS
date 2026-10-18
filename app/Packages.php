<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use DomainException;
final class Packages
{
    public function __construct(private App $app) {}
    public static function inspect(string $path, string $type): array
    {
        if (!in_array($type,['plugins','themes'],true) || !class_exists(\ZipArchive::class)) throw new DomainException('ZIP extension is required.');
        $zip=new \ZipArchive();
        if ($zip->open($path)!==true) throw new DomainException('Invalid ZIP.');
        try {
            if ($zip->numFiles<1 || $zip->numFiles>100) throw new DomainException('Package file limit exceeded.');
            $files=[]; $size=0;
            for ($i=0;$i<$zip->numFiles;$i++) {
                $stat=$zip->statIndex($i); $name=$stat['name'];
                if (str_ends_with($name,'/')) {
                    if (!preg_match('~^(?:[a-z0-9_-]+/)+$~D',$name)) throw new DomainException('Unsafe directory entry.');
                    $zip->getExternalAttributesIndex($i,$os,$attr);
                    if ($os===\ZipArchive::OPSYS_UNIX && (($attr>>16)&0170000)!==0040000 && (($attr>>16)&0170000)!==0) throw new DomainException('Invalid directory attributes.');
                    continue;
                }
                if (!preg_match('~^(?:[a-z0-9_-]+/)*[a-z0-9_-]+\.(?:json|php|css|md|txt)$~D',$name)
                    || isset($files[strtolower($name)]) || ($stat['encryption_method']??0)!==0
                    || $stat['size']>1048576 || ($size+=$stat['size'])>5242880
                    || ($stat['size']>100000 && $stat['size']>max(1,$stat['comp_size'])*100)) throw new DomainException('Unsafe path, duplicate or oversized package entry.');
                $zip->getExternalAttributesIndex($i,$os,$attr);
                $kind=($attr>>16)&0170000;
                if ($os===\ZipArchive::OPSYS_UNIX && $kind!==0 && $kind!==0100000) throw new DomainException('Symlinks and special entries are forbidden.');
                if ($type==='themes' && pathinfo($name,PATHINFO_EXTENSION)==='php') throw new DomainException('Themes contain CSS, never executable PHP.');
                $bytes=$zip->getFromIndex($i,1048577);
                if (!is_string($bytes) || strlen($bytes)!==$stat['size']) throw new DomainException('Unreadable package entry.');
                if (str_ends_with($name,'.css') && preg_match('~url\s*\(|@import|@font-face|expression\s*\(|[<>]|javascript:~i',$bytes)) throw new DomainException('Theme CSS must not load external content or contain executable markup.');
                $files[strtolower($name)]=['name'=>$name,'bytes'=>$bytes];
            }
            $manifest=json_decode($files['manifest.json']['bytes']??'',true);
            if (!is_array($manifest) || !preg_match('/^[a-z][a-z0-9-]{0,39}$/D',(string)($manifest['id']??''))
                || ($manifest['api']??0)!==1 || ($manifest['type']??'')!==$type
                || !is_string($manifest['name']??null) || strlen($manifest['name'])>100
                || !is_string($manifest['version']??null) || !preg_match('/^[0-9][a-zA-Z0-9.+-]{0,29}$/D',$manifest['version'])
                || !is_string($manifest['author']??null) || strlen($manifest['author'])>190
                || !isset($files[$type==='plugins'?'plugin.php':'style.css'])) throw new DomainException('Invalid manifest or missing entry point.');
            return ['id'=>$manifest['id'],'manifest'=>$manifest,'files'=>$files];
        } finally { $zip->close(); }
    }
    public function install(string $type): void
    {
        $app=$this->app; $app->needAdmin();
        if (!password_verify((string)($_POST['current_password']??''),$app->user['password'])
            || !isset($_POST['trust_package'])) throw new DomainException($app->t('package_trust'));
        $app->throttle('package:'.$app->user['id'],5,3600,false);
        $file=$_FILES['package']??[];
        if (($file['error']??-1)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']??'')
            || filesize($file['tmp_name'])>5242880) throw new DomainException($app->t('upload_error'));
        $package=self::inspect($file['tmp_name'],$type);
        $destination=FRIENDS_ROOT.'/'.$type.'/'.$package['id'];
        if (file_exists($destination)) throw new DomainException($app->t('package_exists'));
        $stage=dirname(FRIENDS_CONFIG).'/package-'.bin2hex(random_bytes(12));
        if (!mkdir($stage,0700,true)) throw new DomainException($app->t('upload_error'));
        try {
            foreach ($package['files'] as $entry) {
                $filePath=$stage.'/'.$entry['name'];
                if (!is_dir(dirname($filePath))) mkdir(dirname($filePath),0700,true);
                if (file_put_contents($filePath,$entry['bytes'])!==strlen($entry['bytes'])) throw new DomainException($app->t('upload_error'));
            }
            // Reserve the identifier and never merge/overwrite an existing installed package.
            $lock=fopen(dirname(FRIENDS_CONFIG).'/packages.lock','c');
            if (!$lock) throw new DomainException($app->t('upload_error'));
            try {
                if (!flock($lock,LOCK_EX)) throw new DomainException($app->t('upload_error'));
                if (file_exists($destination)) throw new DomainException($app->t('package_exists'));
                $app->saveSetting(($type==='plugins'?'plugin.':'theme.').$package['id'],'0');
                if (!rename($stage,$destination)) throw new DomainException($app->t('package_permissions'));
                $app->saveSetting('installed.'.$type.'.'.$package['id'],'1');
            } finally { flock($lock,LOCK_UN); fclose($lock); }
        } finally {
            if (is_dir($stage)) {
                $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($stage,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($iterator as $item) $item->isDir()?rmdir($item->getPathname()):unlink($item->getPathname());
                rmdir($stage);
            }
        }
    }
}
