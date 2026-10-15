param(
    [string]$XamppDir = '',
    [ValidateRange(1,65535)][int]$ApachePort = 8080,
    [string]$ComposerPhar = ''
)
$ErrorActionPreference = 'Stop'

function Test-XamppRoot([string]$Path) {
    if ([string]::IsNullOrWhiteSpace($Path)) { return $false }
    return (Test-Path -LiteralPath (Join-Path $Path 'php\php.exe') -PathType Leaf) -and
           (Test-Path -LiteralPath (Join-Path $Path 'apache\bin\httpd.exe') -PathType Leaf) -and
           (Test-Path -LiteralPath (Join-Path $Path 'apache\conf\httpd.conf') -PathType Leaf)
}

if ([string]::IsNullOrWhiteSpace($XamppDir)) {
    $XamppCandidates = @()
    if (-not [string]::IsNullOrWhiteSpace($env:XAMPP_DIR)) {
        $XamppCandidates += $env:XAMPP_DIR
    }
    $FileSystemDrives = @(Get-PSDrive -PSProvider FileSystem -ErrorAction SilentlyContinue)
    $FileSystemDrives | ForEach-Object {
        $XamppCandidates += (Join-Path $_.Root 'xampp')
    }
    $XamppCandidates = @(
        $XamppCandidates |
            Where-Object { Test-XamppRoot $_ } |
            ForEach-Object { [IO.Path]::GetFullPath($_) } |
            Sort-Object -Unique
    )

    if ($XamppCandidates.Count -eq 0) {
        Write-Host 'XAMPP was not found at a drive root. Searching all folders on all file-system drives...'
        $RecursiveCandidates = @(foreach ($Drive in $FileSystemDrives) {
            $DriveRoot = $Drive.Root
            Write-Host "Searching $DriveRoot"
            Get-ChildItem -LiteralPath $DriveRoot -Filter 'xampp-control.exe' -File -Recurse -Force -ErrorAction SilentlyContinue |
                ForEach-Object {
                    $Candidate = $_.Directory.FullName
                    if (Test-XamppRoot $Candidate) {
                        [IO.Path]::GetFullPath($Candidate)
                    }
                }
        })
        $XamppCandidates = @(($XamppCandidates + $RecursiveCandidates) | Sort-Object -Unique)
    }

    if ($XamppCandidates.Count -eq 1) {
        $XamppDir = $XamppCandidates[0]
        Write-Host "Detected XAMPP: $XamppDir"
    } elseif ($XamppCandidates.Count -gt 1) {
        $Found = $XamppCandidates -join ', '
        throw "Multiple XAMPP installations found: $Found. Run again with -XamppDir 'D:\path\to\xampp'."
    } else {
        throw "XAMPP was not found on any file-system drive. Install XAMPP or run again with -XamppDir 'D:\path\to\xampp'. The directory must contain xampp-control.exe, php\php.exe and apache\bin\httpd.exe."
    }
} else {
    $XamppDir = [IO.Path]::GetFullPath($XamppDir)
    if (-not (Test-XamppRoot $XamppDir)) {
        throw "Invalid XAMPP directory: $XamppDir. Run again with -XamppDir 'D:\path\to\xampp'; it must contain php\php.exe and apache\bin\httpd.exe."
    }
}

$Php = Join-Path $XamppDir 'php\php.exe'
$Apache = Join-Path $XamppDir 'apache\bin\httpd.exe'
$ApacheConf = Join-Path $XamppDir 'apache\conf\httpd.conf'
$Dest = Join-Path $XamppDir 'htdocs\wealthflow'
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
