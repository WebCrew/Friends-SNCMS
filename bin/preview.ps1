# Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.
# A new disposable preview, separate from real installation storage.
$projectRoot = Split-Path $PSScriptRoot
$phpExe = (Get-Command php -ErrorAction Stop).Source
$loadedModules = & $phpExe -m
$phpArguments = @('-d','upload_max_filesize=10M','-d','post_max_size=48M')
foreach ($moduleName in @('pdo_sqlite','fileinfo','gd','curl','zip','openssl')) {
    $extensionPath = Join-Path (Split-Path $phpExe) "ext/php_$moduleName.dll"
    if ($loadedModules -notcontains $moduleName -and (Test-Path -LiteralPath $extensionPath)) {
        $phpArguments += @('-d',"extension=$extensionPath")
    }
}
$previewDirectory = Join-Path ([IO.Path]::GetTempPath()) ('friends-preview-' + [guid]::NewGuid().ToString('N'))
$priorConfig = $env:FRIENDS_CONFIG
$priorPreview = $env:FRIENDS_PREVIEW
try {
    & $phpExe @phpArguments (Join-Path $PSScriptRoot 'seed-preview.php') $previewDirectory
    if ($LASTEXITCODE -ne 0 -or !(Test-Path -LiteralPath (Join-Path $previewDirectory 'config.php'))) { throw 'Preview preparation failed.' }
    $env:FRIENDS_CONFIG = Join-Path $previewDirectory 'config.php'
    $env:FRIENDS_PREVIEW = '1'
    Write-Host 'Open http://127.0.0.1:8088 — stop with Ctrl+C. Disposable data:' $previewDirectory
    & $phpExe @phpArguments -S 127.0.0.1:8088 -t $projectRoot (Join-Path $PSScriptRoot 'router.php')
} finally {
    $env:FRIENDS_CONFIG = $priorConfig
    $env:FRIENDS_PREVIEW = $priorPreview
}
