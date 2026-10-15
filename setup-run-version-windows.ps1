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
           (Test-Path -LiteralPath (Join-Path $Path 'apache\conf\httpd.conf') -PathType Leaf) -and
           (Test-Path -LiteralPath (Join-Path $Path 'mysql\bin\mysqldump.exe') -PathType Leaf)
}

if ([string]::IsNullOrWhiteSpace($XamppDir)) {
    if (-not [string]::IsNullOrWhiteSpace($env:XAMPP_DIR)) {
        $XamppCandidates = @($env:XAMPP_DIR)
    } else {
        $XamppCandidates = @()
        $FileSystemDrives = @(Get-PSDrive -PSProvider FileSystem -ErrorAction SilentlyContinue)
        foreach ($Drive in $FileSystemDrives) {
            $XamppCandidates += (Join-Path $Drive.Root 'xampp')
        }
        $XamppCandidates += (Join-Path $HOME 'xampp')
        if ($env:LOCALAPPDATA) { $XamppCandidates += (Join-Path $env:LOCALAPPDATA 'xampp') }
        if ($env:ProgramFiles) { $XamppCandidates += (Join-Path $env:ProgramFiles 'xampp') }
        $ProgramFilesX86 = [Environment]::GetEnvironmentVariable('ProgramFiles(x86)')
        if ($ProgramFilesX86) { $XamppCandidates += (Join-Path $ProgramFilesX86 'xampp') }
        $XamppCandidates += 'C:\tools\xampp'

        $ControlPanel = Get-Process -Name 'xampp-control' -ErrorAction SilentlyContinue |
            Where-Object { $_.Path } |
            Select-Object -First 1
        if ($ControlPanel) { $XamppCandidates += (Split-Path -Parent $ControlPanel.Path) }
        $ControlCommand = Get-Command 'xampp-control.exe' -ErrorAction SilentlyContinue
        if ($ControlCommand -and $ControlCommand.Source) {
            $XamppCandidates += (Split-Path -Parent $ControlCommand.Source)
        }
    }

    $XamppCandidates = @(
        $XamppCandidates |
            Where-Object { Test-XamppRoot $_ } |
            ForEach-Object { [IO.Path]::GetFullPath($_) } |
            Sort-Object -Unique
    )
    if ($XamppCandidates.Count -eq 1) {
        $XamppDir = $XamppCandidates[0]
        Write-Host "Detected XAMPP: $XamppDir"
    } elseif ($XamppCandidates.Count -gt 1) {
        throw "Multiple XAMPP installations found: $($XamppCandidates -join ', '). Run again with -XamppDir 'D:\path\to\xampp'."
    } else {
        throw "XAMPP was not found in common installation locations. Set XAMPP_DIR or run with -XamppDir 'D:\path\to\xampp'. The folder must contain php, apache and mysql."
    }
} else {
    $XamppDir = [IO.Path]::GetFullPath($XamppDir)
    if (-not (Test-XamppRoot $XamppDir)) {
        throw "Invalid XAMPP directory: $XamppDir. The folder must contain php\php.exe, apache\bin\httpd.exe, apache\conf\httpd.conf and mysql\bin\mysqldump.exe."
    }
}

$Php = Join-Path $XamppDir 'php\php.exe'
$Apache = Join-Path $XamppDir 'apache\bin\httpd.exe'
$ApacheConf = Join-Path $XamppDir 'apache\conf\httpd.conf'
$Dest = Join-Path $XamppDir 'htdocs\wealthflow'
foreach ($Required in @($Php, $Apache, $ApacheConf)) {
    if (-not (Test-Path -LiteralPath $Required)) { throw "Missing: $Required" }
}
$env:PATH = (Join-Path $XamppDir 'php') + ';' + $env:PATH
$ComposerCommand = $null
if ($ComposerPhar) {
    if (-not (Test-Path -LiteralPath $ComposerPhar)) { throw 'Composer PHAR not found.' }
    $ComposerPhar = (Resolve-Path -LiteralPath $ComposerPhar).Path
} else {
    $ComposerCommand = Get-Command 'composer' -ErrorAction SilentlyContinue
    if (-not $ComposerCommand) {
        $LocalComposer = Join-Path $PSScriptRoot 'composer.phar'
        $XamppComposer = Join-Path $XamppDir 'composer.phar'
        if (Test-Path -LiteralPath $LocalComposer -PathType Leaf) {
            $ComposerPhar = $LocalComposer
        } elseif (Test-Path -LiteralPath $XamppComposer -PathType Leaf) {
            $ComposerPhar = $XamppComposer
        } else {
            throw 'Composer was not found. Install Composer and reopen PowerShell, or place composer.phar next to this script / in the XAMPP folder, or pass -ComposerPhar.'
        }
    }
}

if (-not (Test-Path -LiteralPath (Join-Path $PSScriptRoot 'source\backend\composer.json') -PathType Leaf) -or
    -not (Test-Path -LiteralPath (Join-Path $PSScriptRoot 'scripts\install-xampp.php') -PathType Leaf)) {
    throw 'Run this setup script from the project folder; source\backend\composer.json or scripts\install-xampp.php is missing.'
}

$PortProbe = $null
try {
    $PortProbe = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, $ApachePort)
    $PortProbe.Start()
} catch {
    throw "Port $ApachePort is already in use. Stop the application using it or run with -ApachePort <free-port>."
} finally {
    if ($PortProbe) { $PortProbe.Stop() }
}
Write-Host "Fresh install: ALL of $XamppDir\htdocs will be moved to a backup."
Write-Host 'Only the WealthFlow database is reset. Other databases are preserved.'
# Install dependencies before touching the existing deployment.
$OriginalPhpIniScanDir = $env:PHP_INI_SCAN_DIR
$TemporaryPhpIniDir = $null
try {
    & $Php -r "exit(extension_loaded('zip') ? 0 : 1);" | Out-Null
    $ZipEnabled = $LASTEXITCODE -eq 0
    if (-not $ZipEnabled) {
        $ZipExtension = Join-Path $XamppDir 'php\ext\php_zip.dll'
        if (-not (Test-Path -LiteralPath $ZipExtension -PathType Leaf)) {
            throw "PHP ZIP support is required by Composer, but php_zip.dll was not found at $ZipExtension. Repair or update this XAMPP PHP installation."
        }
        $TemporaryPhpIniDir = Join-Path $env:TEMP ('wealthflow-php-ini-' + [guid]::NewGuid().ToString('N'))
        New-Item -ItemType Directory -Path $TemporaryPhpIniDir | Out-Null
        Set-Content -LiteralPath (Join-Path $TemporaryPhpIniDir '99-wealthflow-zip.ini') -Value 'extension=zip' -Encoding ASCII
        $env:PHP_INI_SCAN_DIR = if ([string]::IsNullOrWhiteSpace($OriginalPhpIniScanDir)) {
            $TemporaryPhpIniDir
        } else {
            $TemporaryPhpIniDir + ';' + $OriginalPhpIniScanDir
        }
        & $Php -r "exit(extension_loaded('zip') ? 0 : 1);" | Out-Null
        $ZipEnabled = $LASTEXITCODE -eq 0
        if (-not $ZipEnabled) {
            throw 'PHP ZIP support could not be enabled for Composer. Check the XAMPP PHP extension_dir and php_zip.dll.'
        }
        Write-Host 'Enabled PHP ZIP temporarily for Composer; php.ini was not changed.'
    }

    Push-Location (Join-Path $PSScriptRoot 'source\backend')
    try {
        if ($ComposerPhar) { & $Php $ComposerPhar install --no-dev --prefer-dist --no-interaction --optimize-autoloader }
        else { & $ComposerCommand.Source install --no-dev --prefer-dist --no-interaction --optimize-autoloader }
        if ($LASTEXITCODE -ne 0) { throw 'Composer install failed.' }
    } finally { Pop-Location }
} finally {
    $env:PHP_INI_SCAN_DIR = $OriginalPhpIniScanDir
    if ($TemporaryPhpIniDir -and (Test-Path -LiteralPath $TemporaryPhpIniDir)) {
        Remove-Item -LiteralPath $TemporaryPhpIniDir -Recurse -Force
    }
}

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
$ApacheProcesses = @(Get-CimInstance Win32_Process -Filter "Name = 'httpd.exe'" |
    Where-Object {
        $_.ExecutablePath -and
        [IO.Path]::GetFullPath($_.ExecutablePath) -ieq [IO.Path]::GetFullPath($Apache)
    })
if ($ApacheProcesses.Count -gt 0) {
    # XAMPP's Apache is often a console process rather than an installed Windows service.
    $ApacheProcessIds = @($ApacheProcesses | ForEach-Object { [int]$_.ProcessId })
    $ApacheProcesses |
        Sort-Object @{ Expression = { if ($ApacheProcessIds -contains [int]$_.ParentProcessId) { 0 } else { 1 } } } |
        ForEach-Object {
            Stop-Process -Id ([int]$_.ProcessId) -Force -ErrorAction SilentlyContinue
        }
    for ($Attempt = 0; $Attempt -lt 10; $Attempt++) {
        $StillRunning = @(Get-CimInstance Win32_Process -Filter "Name = 'httpd.exe'" |
            Where-Object {
                $_.ExecutablePath -and
                [IO.Path]::GetFullPath($_.ExecutablePath) -ieq [IO.Path]::GetFullPath($Apache)
            })
        if ($StillRunning.Count -eq 0) { break }
        Start-Sleep -Seconds 1
    }
    if ($StillRunning.Count -gt 0) {
        throw "Could not stop the Apache processes from $XamppDir. Stop Apache in XAMPP Control Panel and retry."
    }
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
