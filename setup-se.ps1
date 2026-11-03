# WealthFlow/SE local runner. It uses the MariaDB instance already started in XAMPP.
[CmdletBinding()]
param(
    [string]$XamppRoot = 'C:\xampp',
    [int]$WebPort = 8070,
    [int]$DbPort = 3306,
    [string]$DbName = 'wealthflow',
    [switch]$DbOnly
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
function Test-TcpPort([string]$HostName, [int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $pending = $client.BeginConnect($HostName, $Port, $null, $null)
        if (-not $pending.AsyncWaitHandle.WaitOne(3000, $false)) { return $false }
        $client.EndConnect($pending)
        return $true
    } catch {
        return $false
    } finally {
        $client.Close()
    }
}
function Same-Path([string]$Left, [string]$Right) {
    try { return [IO.Path]::GetFullPath($Left).TrimEnd('\') -ieq [IO.Path]::GetFullPath($Right).TrimEnd('\') }
    catch { return $false }
}
function Assert-OwnedListener([int]$Port, [string]$ExpectedExecutable, [string]$ExpectedArgument) {
    $listeners = @(Get-Listener $Port)
    if ($listeners.Count -eq 0) { return $false }
    $argBase = [IO.Path]::GetFileName($ExpectedArgument)
    foreach ($pidValue in @($listeners | Select-Object -ExpandProperty OwningProcess -Unique)) {
        $proc = Get-CimInstance Win32_Process -Filter "ProcessId = $pidValue" -ErrorAction SilentlyContinue
        if (-not $proc -or -not $proc.ExecutablePath) {
            $description = "PID $pidValue"
            Fail "Port $Port is already occupied by $description. Nothing was stopped or overwritten."
        }
        if (-not (Same-Path $proc.ExecutablePath $ExpectedExecutable)) {
            if ($Port -eq 3306 -and (Same-Path $proc.ExecutablePath (Join-Path $XamppRoot 'mysql\bin\mysqld.exe'))) {
                continue
            }
            $description = if ($proc) { "$($proc.Name), PID $pidValue" } else { "PID $pidValue" }
            Fail "Port $Port is already occupied by $description. Nothing was stopped or overwritten."
        }
        $isArgMatch = $proc.CommandLine -and (
            ($proc.CommandLine.IndexOf($ExpectedArgument, [StringComparison]::OrdinalIgnoreCase) -ge 0) -or
            ($proc.CommandLine.IndexOf("router.php", [StringComparison]::OrdinalIgnoreCase) -ge 0) -or
            ($argBase -and $proc.CommandLine.IndexOf($argBase, [StringComparison]::OrdinalIgnoreCase) -ge 0)
        )
        if (-not $isArgMatch -and -not ($Port -eq 3306 -and (Same-Path $proc.ExecutablePath (Join-Path $XamppRoot 'mysql\bin\mysqld.exe')))) {
            $description = "$($proc.Name), PID $pidValue"
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
    if ($DbPort -ne 3306) { Fail 'WealthFlow must use the XAMPP MariaDB port 3306. Do not use 3307.' }
    if ($WebPort -eq $DbPort) { Fail 'WebPort and DbPort cannot be equal.' }
    if ($DbName -notmatch '^wealthflow(?:_[A-Za-z0-9_]+)?$') { Fail 'DB name must be wealthflow or wealthflow_*.' }
    $XamppRoot = [IO.Path]::GetFullPath($XamppRoot)
    $Project = [IO.Path]::GetFullPath($PSScriptRoot)
    $Source = Join-Path $Project 'source'
    $Backend = Join-Path $Source 'backend'
    $Router = Join-Path $Backend 'router.php'
    $Migrator = Join-Path $Backend 'bin\setup.php'
    $Php = Join-Path $XamppRoot 'php\php.exe'
    foreach ($item in @(
        @($Php, 'XAMPP PHP is missing.'),
        @((Join-Path $XamppRoot 'mysql\bin\mysql.exe'), 'XAMPP MariaDB client is missing.'),
        @($Router, 'SE project is missing source\backend\router.php. Copy BOTH setup files into the SE root folder.'),
        @($Migrator, 'SE project is missing source\backend\bin\setup.php.'),
        @((Join-Path $Source 'index.html'), 'SE project is missing source\index.html.'),
        @((Join-Path $Backend 'vendor\autoload.php'), 'SE project is missing Composer dependencies: source\backend\vendor.')
    )) { Test-File $item[0] $item[1] }
    & $Php -r 'exit(PHP_VERSION_ID >= 80200 && extension_loaded(''pdo_mysql'') && extension_loaded(''mbstring'') && extension_loaded(''openssl'') ? 0 : 1);'
    if ($LASTEXITCODE -ne 0) { Fail 'PHP 8.2+ with pdo_mysql, mbstring, openssl is required. Check C:\xampp\php\php.ini.' }

    $InstanceRoot = Join-Path $XamppRoot 'se-instance'
    $MysqlErrorLog = Join-Path $InstanceRoot 'mysql-error.log'
    $PhpOut = Join-Path $InstanceRoot 'php-8070.log'
    $PhpErr = Join-Path $InstanceRoot 'php-8070-error.log'
    $KeyFile = Join-Path $InstanceRoot 'app-key.txt'

    # XAMPP owns the shared MariaDB service; this project gets its own database schema.
    if (-not (Test-TcpPort '127.0.0.1' $DbPort)) {
        Fail 'Cannot reach XAMPP MariaDB at 127.0.0.1:3306. Start MySQL in XAMPP and confirm its port is 3306.'
    }
    $webRunning = Assert-OwnedListener $WebPort $Php $Router
    New-Item -ItemType Directory -Path $InstanceRoot -Force | Out-Null
    Info "[2/7] XAMPP MariaDB is reachable on 127.0.0.1:$DbPort; project database: $DbName."

    if ($DbOnly) {
        Write-Host "XAMPP MariaDB is listening on 127.0.0.1:$DbPort. No migrations or web server started." -ForegroundColor Green
        exit 0
    }

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
    $env:MAIL_TRANSPORT = if ($env:SE_MAIL_TRANSPORT -eq 'smtp') { 'smtp' } else { 'log' } # SMTP needs config.local.php credentials; restart after changes.
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
    Write-Host 'Used the existing XAMPP MariaDB; no second database instance was started.' -ForegroundColor Green
    Write-Host "Logs:         $InstanceRoot"
    Start-Process "http://localhost:$WebPort/" | Out-Null
    exit 0
} catch {
    Write-Host ''
    Write-Host ('SETUP SE FAILED: ' + $_.Exception.Message) -ForegroundColor Red
    Write-Host 'No htdocs folders or existing databases were deleted.' -ForegroundColor Yellow
    exit 1
}
