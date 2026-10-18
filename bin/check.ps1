# Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer.
# Read-only source checks plus the isolated SQLite integration suite. No HTTP fixtures or deployment.
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot
$phpExe = (Get-Command php -ErrorAction Stop).Source
$nodeExe = (Get-Command node -ErrorAction Stop).Source

& $phpExe -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);'
if ($LASTEXITCODE -ne 0) { throw 'PHP 8.4 or newer is required.' }

$loadedModules = & $phpExe -m
if ($LASTEXITCODE -ne 0) { throw 'Could not inspect PHP extensions.' }
$phpArguments = @()
foreach ($moduleName in @('pdo_sqlite','gd','fileinfo','zip')) {
    if ($loadedModules -notcontains $moduleName) {
        $moduleExtension = Join-Path (Split-Path $phpExe) "ext/php_$moduleName.dll"
        if (!(Test-Path -LiteralPath $moduleExtension)) { throw "Enable PHP extension $moduleName for the isolated integration tests." }
        $phpArguments += @('-d',"extension=$moduleExtension")
    }
}
$phpCount = 0
foreach ($entry in @('index.php','install.php')) {
    & $phpExe @phpArguments -l (Join-Path $projectRoot $entry)
    if ($LASTEXITCODE -ne 0) { throw 'Entry syntax check failed' }
    $phpCount++
}
foreach ($sourceDirectory in @('app','bin','plugins','public','resources','tests')) {
    foreach ($sourceFile in Get-ChildItem -LiteralPath (Join-Path $projectRoot $sourceDirectory) -Recurse -File -Filter '*.php') {
        & $phpExe @phpArguments -l $sourceFile.FullName
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed: $($sourceFile.FullName)" }
        $phpCount++
    }
}
$jsCount = 0
foreach ($sourceDirectory in @('public/assets','tests')) {
    foreach ($sourceFile in Get-ChildItem -LiteralPath (Join-Path $projectRoot $sourceDirectory) -Recurse -File | Where-Object Extension -In '.js','.mjs') {
        & $nodeExe --check $sourceFile.FullName
        if ($LASTEXITCODE -ne 0) { throw "JavaScript syntax check failed: $($sourceFile.FullName)" }
        $jsCount++
    }
}
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/integration.php')
if ($LASTEXITCODE -ne 0) { throw 'Integration checks failed.' }
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/voice-room.php')
if ($LASTEXITCODE -ne 0) { throw 'Voice-room checks failed.' }
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/features.php')
if ($LASTEXITCODE -ne 0) { throw 'Feature checks failed.' }
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/hosting.php')
if ($LASTEXITCODE -ne 0) { throw 'Hosting policy checks failed.' }
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/installer-errors.php')
if ($LASTEXITCODE -ne 0) { throw 'Installer error checks failed.' }
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/account-mail.php')
if ($LASTEXITCODE -ne 0) { throw 'Account email checks failed.' }
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/refinements.php')
if ($LASTEXITCODE -ne 0) { throw 'Refinement checks failed.' }
& $phpExe @phpArguments (Join-Path $projectRoot 'tests/automatic-links.php')
if ($LASTEXITCODE -ne 0) { throw 'Automatic-link checks failed.' }
& $nodeExe (Join-Path $projectRoot 'tests/automatic-links.mjs')
if ($LASTEXITCODE -ne 0) { throw 'Browser URL extraction checks failed.' }
Write-Host "Baseline passed: $phpCount PHP files, $jsCount JavaScript files, isolated SQLite integration suite."
Write-Host 'Not included: real MySQL 5.7, HTTP fixture suite, physical audio/device checks, independent security review.'
