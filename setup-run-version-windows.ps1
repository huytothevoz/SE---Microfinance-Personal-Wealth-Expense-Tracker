param(
    [string]$XamppDir = 'C:\xampp',
    [ValidateRange(1,65535)][int]$ApachePort = 8080,
    [string]$ComposerPhar = ''
)
$ErrorActionPreference = 'Stop'
$Php = Join-Path $XamppDir 'php\php.exe'
$Apache = Join-Path $XamppDir 'apache\bin\httpd.exe'
$ApacheConf = Join-Path $XamppDir 'apache\conf\httpd.conf'
$Dest = Join-Path $XamppDir 'htdocs\wealthflow'
foreach ($Required in @($Php, $Apache, $ApacheConf, (Join-Path $PSScriptRoot 'source\.htaccess'))) {
    if (-not (Test-Path -LiteralPath $Required)) { throw "Missing: $Required" }
}
$env:PATH = (Join-Path $XamppDir 'php') + ';' + $env:PATH
if ($ComposerPhar) {
    if (-not (Test-Path -LiteralPath $ComposerPhar)) { throw 'Composer PHAR not found.' }
    $ComposerPhar = (Resolve-Path -LiteralPath $ComposerPhar).Path
} elseif (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    throw 'Install Composer first, or pass -ComposerPhar C:\path\composer.phar'
}
Write-Host "Fresh install: ALL of $XamppDir\htdocs will be moved to a backup."
Write-Host 'Only the WealthFlow database is reset. Other databases are preserved.'
# Install dependencies before touching the existing deployment.
Push-Location (Join-Path $PSScriptRoot 'source\backend')
try {
    if ($ComposerPhar) { & $Php $ComposerPhar install --no-dev --prefer-dist --no-interaction --optimize-autoloader }
    else { & composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader }
    if ($LASTEXITCODE -ne 0) { throw 'Composer install failed.' }
} finally { Pop-Location }

$Starter = Join-Path $XamppDir 'xampp_start.exe'
if (Test-Path -LiteralPath $Starter) { Start-Process -FilePath $Starter -WorkingDirectory $XamppDir }
$Ready = $false
for ($Attempt = 0; $Attempt -lt 30; $Attempt++) {
    $Client = New-Object Net.Sockets.TcpClient
    try {
        $DbHost = if ($env:DB_HOST) { $env:DB_HOST } else { '127.0.0.1' }
        $DbPort = if ($env:DB_PORT) { [int]$env:DB_PORT } else { 3306 }
        $Client.Connect($DbHost, $DbPort)
        $Ready = $true
    } catch { Start-Sleep -Seconds 1 }
    finally { $Client.Dispose() }
    if ($Ready) { break }
}
if (-not $Ready) { throw 'Start MySQL in XAMPP Control Panel and run this script again.' }
# Stop Apache before snapshotting files/database. MySQL stays running.
& $Apache -k shutdown -f $ApacheConf
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Apache may already be stopped. If htdocs is locked, stop Apache in XAMPP Control Panel.'
}
Start-Sleep -Seconds 2
& $Php (Join-Path $PSScriptRoot 'scripts\install-xampp.php') $XamppDir $ApachePort
$InstallExit = $LASTEXITCODE
# Start Apache directly; this works for XAMPP without a Windows service.
Start-Process -FilePath $Apache -WorkingDirectory (Join-Path $XamppDir 'apache\bin')
if ($InstallExit -ne 0) { throw 'Installation failed; see the backup/restore details above.' }
Write-Host "Website: http://localhost:$ApachePort/"
Write-Host "Configure SMTP: $Dest\backend\config.local.php"
$Healthy = $false
for ($Attempt = 0; $Attempt -lt 10; $Attempt++) {
    try {
        $Health = Invoke-RestMethod -Uri "http://localhost:$ApachePort/api/health" -TimeoutSec 5
        if ($Health.success) { $Healthy = $true; break }
    } catch { Start-Sleep -Seconds 1 }
}
if (-not $Healthy) { throw 'API check failed. Check XAMPP Apache logs, MySQL and backend\config.local.php.' }
Write-Host 'Fresh install and API health check completed.'
