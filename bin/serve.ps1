# Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.
# Local classic installer. Development router mirrors the production root URL allowlist.
$projectRoot = Split-Path $PSScriptRoot
$phpExe = (Get-Command php -ErrorAction Stop).Source
$loadedModules = & $phpExe -m
$phpArguments = @('-d','upload_max_filesize=10M','-d','post_max_size=48M')
foreach ($moduleName in @('pdo_mysql','fileinfo','gd','curl','zip','openssl')) {
    $extensionPath = Join-Path (Split-Path $phpExe) "ext/php_$moduleName.dll"
    if ($loadedModules -notcontains $moduleName -and (Test-Path -LiteralPath $extensionPath)) {
        $phpArguments += @('-d',"extension=$extensionPath")
    }
}
& $phpExe @phpArguments -S 127.0.0.1:8088 -t $projectRoot (Join-Path $PSScriptRoot 'router.php')
