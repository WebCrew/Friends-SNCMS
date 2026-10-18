<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
// Local development router, not a substitute for production web-server access rules.
if (PHP_SAPI!=='cli-server') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/RootHosting.php';
$root=str_replace('\\','/',(string)realpath(dirname(__DIR__)));
$documentRoot=rtrim(str_replace('\\','/',(string)realpath($_SERVER['DOCUMENT_ROOT'])),'/');
if ($root!==$documentRoot && !str_starts_with($root,$documentRoot.'/')) { http_response_code(404); return true; }
$mount=substr($root,strlen($documentRoot));
$path=rawurldecode(explode('?',$_SERVER['REQUEST_URI'],2)[0]);
if (!str_starts_with($path,$mount.'/') || str_contains($path,'\\') || str_contains($path,"\0")) { http_response_code(404); return true; }
$relative=substr($path,strlen($mount)+1);
if (!Friends\RootHosting::allowed($relative)) { http_response_code(403); return true; }
$target=$root.'/'.($relative?:'index.php');
if (!is_file($target)) { http_response_code(404); return true; }
if (!in_array($relative,['','index.php','install.php'],true)) return false;
$_SERVER['FRIENDS_ROOT_GUARD']='1';
$_SERVER['SCRIPT_NAME']=$mount.'/'.($relative==='install.php'?'install.php':'index.php');
require $target;
return true;
