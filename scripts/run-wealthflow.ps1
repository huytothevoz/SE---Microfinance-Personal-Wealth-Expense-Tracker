param(
    [string]$XamppDir = '',
    [int]$ApachePort = 8080,
    [switch]$NoBrowser
)

$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

function Write-Info([string]$Msg) {
    Write-Host "[INFO] $Msg" -ForegroundColor Cyan
}

function Write-Success([string]$Msg) {
    Write-Host "[OK]   $Msg" -ForegroundColor Green
}

function Write-Warn([string]$Msg) {
    Write-Host "[WARN] $Msg" -ForegroundColor Yellow
}

function Write-Err([string]$Msg) {
    Write-Host "[ERR]  $Msg" -ForegroundColor Red
}

function Test-PortOpen([string]$HostName, [int]$Port) {
    $tcp = New-Object System.Net.Sockets.TcpClient
    try {
        $iar = $tcp.BeginConnect($HostName, $Port, $null, $null)
        $wait = $iar.AsyncWaitHandle.WaitOne(1000, $false)
        if (-not $wait) { return $false }
        $tcp.EndConnect($iar)
        return $true
    } catch {
        return $false
    } finally {
        $tcp.Close()
        $tcp.Dispose()
    }
}

# 1. Tim thu muc XAMPP
if ([string]::IsNullOrWhiteSpace($XamppDir)) {
    $Candidates = @(
        $env:XAMPP_DIR,
        'D:\xampp',
        'C:\xampp',
        'E:\xampp',
        'F:\xampp'
    )
    foreach ($cand in $Candidates) {
        if ($cand -and (Test-Path (Join-Path $cand 'php\php.exe')) -and (Test-Path (Join-Path $cand 'apache\bin\httpd.exe'))) {
            $XamppDir = [IO.Path]::GetFullPath($cand)
            break
        }
    }
}

if (-not $XamppDir -or -not (Test-Path (Join-Path $XamppDir 'php\php.exe'))) {
    Write-Err "Khong tim thay thu muc XAMPP. Vui long dat XAMPP_DIR hoac truyen -XamppDir 'D:\xampp'."
    exit 1
}

Write-Info "Thu muc XAMPP: $XamppDir"

$Php = Join-Path $XamppDir 'php\php.exe'
$Apache = Join-Path $XamppDir 'apache\bin\httpd.exe'
$ApacheConf = Join-Path $XamppDir 'apache\conf\httpd.conf'
$Mysql = Join-Path $XamppDir 'mysql\bin\mysql.exe'
$HtdocsWealthflow = Join-Path $XamppDir 'htdocs\wealthflow'
$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$SourceDir = Join-Path $ProjectRoot 'source'

# 2. Kiem tra va khoi dong MySQL neu chua chay
Write-Info "Kiem tra dich vu MySQL (cong 3306)..."
$mysqlRunning = Test-PortOpen '127.0.0.1' 3306
if (-not $mysqlRunning) {
    Write-Warn "MySQL chua chay. Dang tu dong khoi dong MySQL..."
    $mysqlStarter = Join-Path $XamppDir 'mysql_start.bat'
    if (Test-Path $mysqlStarter) {
        Start-Process -FilePath 'cmd.exe' -ArgumentList "/c `"$mysqlStarter`"" -WorkingDirectory $XamppDir -WindowStyle Hidden
    } else {
        $mysqld = Join-Path $XamppDir 'mysql\bin\mysqld.exe'
        Start-Process -FilePath $mysqld -WorkingDirectory (Join-Path $XamppDir 'mysql') -WindowStyle Hidden
    }

    for ($i = 0; $i -lt 15; $i++) {
        Start-Sleep -Seconds 1
        if (Test-PortOpen '127.0.0.1' 3306) {
            $mysqlRunning = $true
            break
        }
    }
}

if ($mysqlRunning) {
    Write-Success "MySQL dang hoat dong tai 127.0.0.1:3306."
} else {
    Write-Err "Khong the khoi dong MySQL. Vui long bat MySQL trong XAMPP Control Panel!"
    exit 1
}

# 3. Kiem tra Composer vendor & source deployment
$vendorAutoload = Join-Path $SourceDir 'backend\vendor\autoload.php'
if (-not (Test-Path $vendorAutoload)) {
    Write-Info "Dang cai dat dependencies Composer cho backend..."
    $composerPhar = Join-Path $env:TEMP 'composer.phar'
    if (-not (Test-Path $composerPhar)) {
        Write-Info "Dang tai composer.phar..."
        Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile $composerPhar -UseBasicParsing
    }
    Push-Location (Join-Path $SourceDir 'backend')
    try {
        & $Php $composerPhar install --no-dev --prefer-dist --no-interaction --optimize-autoloader
    } finally {
        Pop-Location
    }
}

# 4. Kiem tra xem source da duoc copy sang htdocs/wealthflow chua
if (-not (Test-Path (Join-Path $HtdocsWealthflow 'index.html')) -or -not (Test-Path (Join-Path $HtdocsWealthflow 'backend\config.local.php'))) {
    Write-Info "Dong bo ma nguon sang $HtdocsWealthflow..."
    if (-not (Test-Path $HtdocsWealthflow)) {
        New-Item -ItemType Directory -Path $HtdocsWealthflow -Force | Out-Null
    }
    Copy-Item -Path "$SourceDir\*" -Destination $HtdocsWealthflow -Recurse -Force

    $localCfg = Join-Path $HtdocsWealthflow 'backend\config.local.php'
    if (-not (Test-Path $localCfg)) {
        $exampleCfg = Join-Path $HtdocsWealthflow 'backend\config.example.php'
        if (Test-Path $exampleCfg) {
            Copy-Item -Path $exampleCfg -Destination $localCfg -Force
        }
    }
}

# 5. Kiem tra database wealthflow va import neu chua co
try {
    $dbCheck = & $Mysql -u root -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'wealthflow' AND table_name = 'users';" 2>$null
    $hasUsers = ($dbCheck -match '1')
    if (-not $hasUsers) {
        Write-Info "Khoi tao Database 'wealthflow' va chay migrations..."
        & $Mysql -u root -e "CREATE DATABASE IF NOT EXISTS wealthflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        $migrations = Get-ChildItem -Path (Join-Path $SourceDir 'database') -Filter '*.sql' | Sort-Object Name
        foreach ($sqlFile in $migrations) {
            Write-Info "  -> Chay migration: $($sqlFile.Name)"
            & cmd.exe /c "`"$Mysql`" -u root wealthflow < `"$($sqlFile.FullName)`""
        }
    }
} catch {
    Write-Warn "Kiem tra database: $_"
}

# 6. Kiem tra file cau hinh httpd.conf cua Apache
$confText = Get-Content -Path $ApacheConf -Raw
$needConfUpdate = $false
if ($confText -notmatch "Listen\s+(127\.0\.0\.1:)?$ApachePort") {
    $confText = $confText -replace '(?m)^Listen\s+\d+.*$', "Listen 127.0.0.1:$ApachePort"
    $needConfUpdate = $true
}
$targetDocRoot = ($HtdocsWealthflow -replace '\\', '/')
if ($confText -notmatch [regex]::Escape($targetDocRoot)) {
    $confText = $confText -replace '(?m)^DocumentRoot\s+.*$', "DocumentRoot `"$targetDocRoot`""
    $needConfUpdate = $true
}
if ($confText -notmatch [regex]::Escape("<Directory `"$targetDocRoot`">")) {
    $dirBlock = @"

<Directory "$targetDocRoot">
    Options Indexes FollowSymLinks ExecCGI Includes
    AllowOverride All
    Require all granted
</Directory>
"@
    $confText += $dirBlock
    $needConfUpdate = $true
}

if ($needConfUpdate) {
    Write-Info "Cap nhat cau hinh Apache port $ApachePort va DocumentRoot..."
    Set-Content -Path $ApacheConf -Value $confText -Encoding ASCII
}

# 7. Kiem tra va khoi dong Apache neu chua chay
Write-Info "Kiem tra Apache (cong $ApachePort)..."
$apacheRunning = Test-PortOpen '127.0.0.1' $ApachePort
if (-not $apacheRunning) {
    Write-Info "Dang khoi dong Apache tren cong $ApachePort..."
    Start-Process -FilePath $Apache -ArgumentList @('-d', (Join-Path $XamppDir 'apache'), '-f', 'conf/httpd.conf') -WorkingDirectory $XamppDir -WindowStyle Hidden

    for ($i = 0; $i -lt 10; $i++) {
        Start-Sleep -Seconds 1
        if (Test-PortOpen '127.0.0.1' $ApachePort) {
            $apacheRunning = $true
            break
        }
    }
}

if ($apacheRunning) {
    Write-Success "Apache dang hoat dong tai cong $ApachePort."
} else {
    Write-Err "Khong the ket noi den Apache cong $ApachePort. Kiem tra file log: $XamppDir\apache\logs\error.log"
    exit 1
}

# 8. Kiem tra Health endpoint
$healthUrl = "http://localhost:$ApachePort/api/health"
$homeUrl = "http://localhost:$ApachePort/"
$appUrl = "http://localhost:$ApachePort/app.html"

try {
    $health = Invoke-RestMethod -Uri $healthUrl -TimeoutSec 3
    if ($health.success) {
        Write-Success "API Health Check: OK!"
    }
} catch {
    Write-Warn "API Health chua phan hoi: $_"
}

# 9. In thong tin dang nhap
Write-Host ""
Write-Host "===============================================================" -ForegroundColor Green
Write-Host "          WEALTHFLOW DA SAN SANG DE SU DUNG!                   " -ForegroundColor Green
Write-Host "===============================================================" -ForegroundColor Green
Write-Host "  [+] Trang chu:      $homeUrl" -ForegroundColor White
Write-Host "  [+] Dashboard App:  $appUrl" -ForegroundColor White
Write-Host "  [+] API Health:     $healthUrl" -ForegroundColor White
Write-Host "---------------------------------------------------------------" -ForegroundColor Gray
Write-Host "  [+] TAI KHOAN MAU (DEMO):" -ForegroundColor Yellow
Write-Host "      Email:    user1@gmail.com" -ForegroundColor Yellow
Write-Host "      Mat khau: User1234" -ForegroundColor Yellow
Write-Host "===============================================================" -ForegroundColor Green
Write-Host ""

# 10. Tu dong mo trinh duyet
if (-not $NoBrowser) {
    Write-Info "Dang mo trinh duyet: $homeUrl"
    Start-Process $homeUrl
}
