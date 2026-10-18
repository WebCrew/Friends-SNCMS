<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
// Isolated fixture generator. Never overwrite existing files.
if (PHP_SAPI !== 'cli' || empty($argv[1]) || file_exists($argv[1])) exit('Supply a NEW fixture directory.');
$directory=$argv[1]; mkdir($directory,0700,true);
$image=imagecreatetruecolor(1200,400);
for($x=0;$x<1200;$x++) {
    $color=imagecolorallocate($image,40+(int)($x/50),29+(int)($x/100),27);
    imageline($image,$x,0,$x,400,$color);
}
$accent=imagecolorallocate($image,242,172,119);$soft=imagecolorallocate($image,115,72,45);$white=imagecolorallocate($image,249,231,216);
for($i=0;$i<5;$i++) imagearc($image,1080,180,250+$i*95,250+$i*95,0,360,$soft);
imagefilledellipse($image,1030,210,120,120,$accent);
$font='C:/Windows/Fonts/segoeui.ttf';
if(is_file($font)) { imagettftext($image,46,0,75,175,$white,$font,'Your people. Your place.'); imagettftext($image,20,0,78,230,$accent,$font,'FRIENDS / COMMUNITY'); }
imagepng($image,$directory.'/community-header.png');imagedestroy($image);
$image=imagecreatetruecolor(200,200);$bg=imagecolorallocate($image,70,43,29);imagefill($image,0,0,$bg);$accent=imagecolorallocate($image,242,172,119);
if(is_file($font)) imagettftext($image,65,0,25,130,$accent,$font,'AH');
imagepng($image,$directory.'/avatar.png');imagedestroy($image);
file_put_contents($directory.'/readme.txt',"Friends-SNCMS test attachment.\nUTF-8: Grüße 😊");
file_put_contents($directory.'/fake.png','<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */ echo "not an image";');
foreach(['plugins','themes'] as $type) {
 $id=$type==='plugins'?'fixture-plugin':'fixture-theme';
 $z=new ZipArchive();$z->open($directory.'/'.$id.'.zip',ZipArchive::CREATE|ZipArchive::OVERWRITE);
 $z->addFromString('manifest.json',json_encode(['id'=>$id,'type'=>$type,'api'=>1,'name'=>'Fixture '.$type,'version'=>'1.0.0','author'=>'Disposable test fixture']));
 $z->addFromString($type==='plugins'?'plugin.php':'style.css',$type==='plugins'?'<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */ return static function($extensions,$app) {};':':root { --accent: #d27942; }');
 $z->close();
}
