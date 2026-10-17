# WealthFlow/SE local runner. Non-destructive; does not modify XAMPP Apache,
# XAMPP's primary MySQL datadir, or any other project in htdocs.
[CmdletBinding()]
param(
    [string]$XamppRoot = 'C:\xampp',
    [int]$WebPort = 8080,
    [int]$DbPort = 3307,
    [string]$DbName = 'wealthflow'
)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Info([string]$Message) { Write-Host $Message -ForegroundColor Cyan }
function Fail([string]$Message) { throw $Message }
function Test-File([string]$File, [string]$Hint) {
    if (-not (Test-Path -LiteralPath $File -PathType Leaf)) { Fail "$Hint`nNot found: $File" }
}
function Get-Listener([int]$Port) {
    return @(Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue)
}
function Same-Path([string]$Left, [string]$Right) {
    try { return [IO.Path]::GetFullPath($Left).TrimEnd('\') -ieq [IO.Path]::GetFullPath($Right).TrimEnd('\') }
    catch { return $false }
}
function Assert-OwnedListener([int]$Port, [string]$ExpectedExecutable, [string]$ExpectedArgument) {
    $listeners = @(Get-Listener $Port)
    if ($listeners.Count -eq 0) { return $false }
    foreach ($pidValue in @($listeners | Select-Object -ExpandProperty OwningProcess -Unique)) {
        $proc = Get-CimInstance Win32_Process -Filter "ProcessId = $pidValue" -ErrorAction SilentlyContinue
        if (-not $proc -or -not $proc.ExecutablePath -or -not (Same-Path $proc.ExecutablePath $ExpectedExecutable) -or
            -not $proc.CommandLine -or $proc.CommandLine.IndexOf($ExpectedArgument, [StringComparison]::OrdinalIgnoreCase) -lt 0) {
            $description = if ($proc) { "$($proc.Name), PID $pidValue" } else { "PID $pidValue" }
            Fail "Port $Port is already occupied by $description. Nothing was stopped or overwritten."
        }
    }
    return $true
}
function Wait-Listen([int]$Port, [int]$Seconds, [string]$Name) {
    for ($i = 0; $i -lt $Seconds; $i++) {
        if (@(Get-Listener $Port).Count -gt 0) { return }
        Start-Sleep -Seconds 1
    }
    Fail "$Name did not start on port $Port after $Seconds seconds."
}
function Secure-NewKey() {
    $bytes = New-Object byte[] 32
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
    return ([BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()
}
function Quote-Win([string]$Path) { return '"' + $Path + '"' }

try {
    Info '[1/7] Checking project and XAMPP...'
    if ($WebPort -lt 1 -or $WebPort -gt 65535 -or $DbPort -lt 1 -or $DbPort -gt 65535) { Fail 'Invalid port.' }
    if ($WebPort -eq $DbPort) { Fail 'WebPort and DbPort cannot be equal.' }
    if ($DbName -notmatch '^wealthflow(?:_[A-Za-z0-9_]+)?$') { Fail 'DB name must be wealthflow or wealthflow_*.' }
    $XamppRoot = [IO.Path]::GetFullPath($XamppRoot)
    $Project = [IO.Path]::GetFullPath($PSScriptRoot)
    $Source = Join-Path $Project 'source'
    $Backend = Join-Path $Source 'backend'
    $Router = Join-Path $Backend 'router.php'
    $Migrator = Join-Path $Backend 'bin\setup.php'
    $Php = Join-Path $XamppRoot 'php\php.exe'
    $Mysqld = Join-Path $XamppRoot 'mysql\bin\mysqld.exe'
    $BackupTemplate = Join-Path $XamppRoot 'mysql\backup'
    foreach ($item in @(
        @($Php, 'XAMPP PHP is missing.'),
        @($Mysqld, 'XAMPP MySQL/MariaDB is missing.'),
        @($Router, 'SE project is missing source\backend\router.php. Copy BOTH setup files into the SE root folder.'),
        @($Migrator, 'SE project is missing source\backend\bin\setup.php.'),
        @((Join-Path $Source 'index.html'), 'SE project is missing source\index.html.'),
        @((Join-Path $Backend 'vendor\autoload.php'), 'SE project is missing Composer dependencies: source\backend\vendor.')
    )) { Test-File $item[0] $item[1] }
    & $Php -r 'exit(PHP_VERSION_ID >= 80200 && extension_loaded(''pdo_mysql'') && extension_loaded(''mbstring'') && extension_loaded(''openssl'') ? 0 : 1);'
    if ($LASTEXITCODE -ne 0) { Fail 'PHP 8.2+ with pdo_mysql, mbstring, openssl is required. Check C:\xampp\php\php.ini.' }

    $InstanceRoot = Join-Path $XamppRoot 'se-instance'
    $DbData = Join-Path $InstanceRoot 'mysql-data'
    $Tmp = Join-Path $InstanceRoot 'tmp'
    $MysqlConfig = Join-Path $InstanceRoot 'my-se.ini'
    $MysqlErrorLog = Join-Path $InstanceRoot 'mysql-error.log'
    $PhpOut = Join-Path $InstanceRoot 'php-8080.log'
    $PhpErr = Join-Path $InstanceRoot 'php-8080-error.log'
    $KeyFile = Join-Path $InstanceRoot 'app-key.txt'

    # Fail closed when any unrelated process owns one of the two requested ports.
    $dbRunning = Assert-OwnedListener $DbPort $Mysqld $MysqlConfig
    $webRunning = Assert-OwnedListener $WebPort $Php $Router
    New-Item -ItemType Directory -Path $InstanceRoot -Force | Out-Null
    New-Item -ItemType Directory -Path $Tmp -Force | Out-Null

    Info '[2/7] Preparing an isolated MariaDB/MySQL directory (3307)...'
    if (-not (Test-Path -LiteralPath $DbData)) {
        if (-not (Test-Path -LiteralPath (Join-Path $BackupTemplate 'mysql') -PathType Container)) {
            Fail "XAMPP database template not found: $BackupTemplate\mysql. No databases were changed. Please share a screenshot of C:\xampp\mysql\backup."
        }
        $Staging = Join-Path $InstanceRoot ('mysql-data-new-' + [guid]::NewGuid().ToString('N'))
        New-Item -ItemType Directory -Path $Staging | Out-Null
        try {
            Get-ChildItem -LiteralPath $BackupTemplate -Force | Copy-Item -Destination $Staging -Recurse -Force -ErrorAction Stop
            if (-not (Test-Path -LiteralPath (Join-Path $Staging 'mysql') -PathType Container)) {
                Fail 'MySQL backup template is incomplete; refusing to use it.'
            }
            Move-Item -LiteralPath $Staging -Destination $DbData -ErrorAction Stop
            Write-Host "Created a SE-only database directory: $DbData"
        } catch {
            # An incomplete staged copy is not a real database; never touch the main data folder.
            if (Test-Path -LiteralPath $Staging) { Remove-Item -LiteralPath $Staging -Recurse -Force -ErrorAction SilentlyContinue }
            throw
        }
    } elseif (-not (Test-Path -LiteralPath (Join-Path $DbData 'mysql') -PathType Container)) {
        Fail "Existing SE MySQL data directory looks incomplete: $DbData. Back it up and inspect it; setup will not delete it."
    }

    $BaseUnix = (Join-Path $XamppRoot 'mysql').Replace('\', '/')
    $DataUnix = $DbData.Replace('\', '/')
    $TmpUnix = $Tmp.Replace('\', '/')
    $LogUnix = $MysqlErrorLog.Replace('\', '/')
    $PidUnix = (Join-Path $InstanceRoot 'mysql-se.pid').Replace('\', '/')
    if (-not (Test-Path -LiteralPath $MysqlConfig)) {
        $myIni = @"
[mysqld]
basedir="$BaseUnix"
datadir="$DataUnix"
tmpdir="$TmpUnix"
port=$DbPort
bind-address=127.0.0.1
log-error="$LogUnix"
pid-file="$PidUnix"
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci

[client]
host=127.0.0.1
port=$DbPort
"@
        [IO.File]::WriteAllText($MysqlConfig, $myIni, [Text.Encoding]::ASCII)
    } else {
        $actualIni = [IO.File]::ReadAllText($MysqlConfig)
        if ($actualIni -notmatch "(?m)^port=$DbPort\s*$" -or $actualIni -notlike "*$DataUnix*") {
            Fail "Existing SE MySQL config needs inspection: $MysqlConfig. Nothing was overwritten."
        }
    }
    if (-not $dbRunning) {
        Info "[3/7] Starting the SE-only MySQL instance on 127.0.0.1:$DbPort..."
        $mysqlArgs = '--defaults-file=' + (Quote-Win $MysqlConfig) + ' --console'
        Start-Process -FilePath $Mysqld -WorkingDirectory (Join-Path $XamppRoot 'mysql\bin') -ArgumentList $mysqlArgs -WindowStyle Hidden | Out-Null
        try { Wait-Listen $DbPort 25 'SE MySQL' }
        catch { Fail "$($_.Exception.Message)`nRead MySQL log: $MysqlErrorLog" }
        $null = Assert-OwnedListener $DbPort $Mysqld $MysqlConfig
    } else { Info '[3/7] SE MySQL is already running; reusing it.' }

    Info '[4/7] Applying SE database migrations safely (no DROP/RESET)...'
    if (-not (Test-Path -LiteralPath $KeyFile -PathType Leaf)) {
        [IO.File]::WriteAllText($KeyFile, (Secure-NewKey), [Text.Encoding]::ASCII)
    }
    $appKey = ([IO.File]::ReadAllText($KeyFile)).Trim()
    if ($appKey.Length -lt 32) { Fail "Invalid APP_KEY file: $KeyFile" }
    # The app reads environment settings before config.local.php. Existing secrets/files untouched.
    $env:APP_URL = "http://localhost:$WebPort"
    $env:APP_ENV = 'local'
    $env:APP_KEY = $appKey
    $env:DB_HOST = '127.0.0.1'
    $env:DB_PORT = [string]$DbPort
    $env:DB_NAME = $DbName
    $env:DB_USER = if ($env:SE_DB_USER) { $env:SE_DB_USER } else { 'root' }
    $env:DB_PASSWORD = if ($env:SE_DB_PASSWORD) { $env:SE_DB_PASSWORD } else { '' }
    $env:MAIL_TRANSPORT = 'log' # Local preview only; do not send real emails from shared config.
    & $Php $Migrator
    if ($LASTEXITCODE -ne 0) {
        Fail "SE migration failed. No database was deleted. Check the DB permissions and files in $Project\source\database."
    }

    if (-not $webRunning) {
        Info "[5/7] Starting SE PHP web server on 127.0.0.1:$WebPort..."
        $phpArgs = '-S 127.0.0.1:' + $WebPort + ' -t ' + (Quote-Win $Source) + ' ' + (Quote-Win $Router)
        Start-Process -FilePath $Php -WorkingDirectory $Project -ArgumentList $phpArgs -WindowStyle Hidden `
            -RedirectStandardOutput $PhpOut -RedirectStandardError $PhpErr | Out-Null
        Wait-Listen $WebPort 15 'SE PHP web server'
        $null = Assert-OwnedListener $WebPort $Php $Router
    } else { Info '[5/7] SE PHP server is already running; reusing it.' }

    Info '[6/7] Testing API -> MySQL connection...'
    $healthy = $false
    for ($i = 0; $i -lt 12; $i++) {
        try {
            $health = Invoke-RestMethod -Uri "http://127.0.0.1:$WebPort/api/health" -TimeoutSec 3
            if ($health.success -eq $true -and $health.data.databaseConnected -eq $true) { $healthy = $true; break }
            if ($health.success -eq $true -and $health.databaseConnected -eq $true) { $healthy = $true; break }
        } catch { }
        Start-Sleep -Seconds 1
    }
    if (-not $healthy) { Fail "SE API health check failed. Inspect $PhpErr and $MysqlErrorLog." }

    Info '[7/7] Everything is ready.'
    Write-Host ''
    Write-Host "SE URL:       http://localhost:$WebPort/" -ForegroundColor Green
    Write-Host "SE MySQL:     127.0.0.1:$DbPort (database: $DbName)" -ForegroundColor Green
    Write-Host 'LTW / port 3306 / Apache settings were not modified.' -ForegroundColor Green
    Write-Host "Logs:         $InstanceRoot"
    Start-Process "http://localhost:$WebPort/" | Out-Null
    exit 0
} catch {
    Write-Host ''
    Write-Host ('SETUP SE FAILED: ' + $_.Exception.Message) -ForegroundColor Red
    Write-Host 'No htdocs folders or existing databases were deleted.' -ForegroundColor Yellow
    exit 1
}
