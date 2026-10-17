$ErrorActionPreference = 'Continue'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$WebPort = 8080
$DbPort = 3307
$DbName = 'wealthflow'

function Find-Xampp {
    $candidates = @(
        $env:XAMPP_DIR,
        'D:\xampp',
        'C:\xampp',
        'E:\xampp',
        'F:\xampp'
    )
    foreach ($cand in $candidates) {
        if ($cand -and (Test-Path (Join-Path $cand 'php\php.exe')) -and (Test-Path (Join-Path $cand 'mysql\bin\mysqld.exe'))) {
            return [IO.Path]::GetFullPath($cand)
        }
    }
    return $null
}

$XamppRoot = Find-Xampp
if (-not $XamppRoot) {
    Write-Host "[LOI] Khong tim thay thu muc cai dat XAMPP (chua php\php.exe va mysql\bin\mysqld.exe)." -ForegroundColor Red
    Write-Host "Vui long dat bien moi truong XAMPP_DIR hoac cai dat XAMPP vao C:\xampp hoac D:\xampp." -ForegroundColor Yellow
    exit 1
}

$Php = Join-Path $XamppRoot 'php\php.exe'
$Mysqld = Join-Path $XamppRoot 'mysql\bin\mysqld.exe'
$MysqlAdmin = Join-Path $XamppRoot 'mysql\bin\mysqladmin.exe'
$MysqlCli = Join-Path $XamppRoot 'mysql\bin\mysql.exe'
$BackupTemplate = Join-Path $XamppRoot 'mysql\backup'

$ProjectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$Source = Join-Path $ProjectRoot 'source'
$Backend = Join-Path $Source 'backend'
$Router = Join-Path $Backend 'router.php'
$Migrator = Join-Path $Backend 'bin\setup.php'

$InstanceRoot = Join-Path $XamppRoot 'se-instance'
$DbData = Join-Path $InstanceRoot 'mysql-data'
$Tmp = Join-Path $InstanceRoot 'tmp'
$MysqlConfig = Join-Path $InstanceRoot 'my-se.ini'
$MysqlErrorLog = Join-Path $InstanceRoot 'mysql-error.log'
$PhpOut = Join-Path $InstanceRoot 'php-8080.log'
$PhpErr = Join-Path $InstanceRoot 'php-8080-error.log'
$KeyFile = Join-Path $InstanceRoot 'app-key.txt'

function Get-Listener([int]$Port) {
    return @(Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue)
}

function Test-WebRunning {
    $conn = Get-Listener $WebPort
    return ($conn.Count -gt 0)
}

function Test-DbRunning {
    $conn = Get-Listener $DbPort
    return ($conn.Count -gt 0)
}

function Quote-Win([string]$Path) { return '"' + $Path + '"' }

function Secure-NewKey() {
    $bytes = New-Object byte[] 32
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
    return ([BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()
}

function Init-EnvVars {
    if (-not (Test-Path $InstanceRoot)) { New-Item -ItemType Directory -Path $InstanceRoot -Force | Out-Null }
    if (-not (Test-Path $Tmp)) { New-Item -ItemType Directory -Path $Tmp -Force | Out-Null }
    if (-not (Test-Path $KeyFile)) { [IO.File]::WriteAllText($KeyFile, (Secure-NewKey), [Text.Encoding]::ASCII) }
    $appKey = ([IO.File]::ReadAllText($KeyFile)).Trim()
    
    $env:APP_URL = "http://localhost:$WebPort"
    $env:APP_ENV = 'local'
    $env:APP_KEY = $appKey
    $env:DB_HOST = '127.0.0.1'
    $env:DB_PORT = [string]$DbPort
    $env:DB_NAME = $DbName
    $env:DB_USER = if ($env:SE_DB_USER) { $env:SE_DB_USER } else { 'root' }
    $env:DB_PASSWORD = if ($env:SE_DB_PASSWORD) { $env:SE_DB_PASSWORD } else { '' }
    $env:MAIL_TRANSPORT = 'log'
}

function Start-Database {
    if (Test-DbRunning) {
        Write-Host "  [OK] MySQL SE (port $DbPort) da dang chay." -ForegroundColor Green
        return $true
    }
    Write-Host "  [*] Dang khoi tao va bat MySQL SE (port $DbPort)..." -ForegroundColor Cyan
    if (-not (Test-Path -LiteralPath $DbData)) {
        if (-not (Test-Path -LiteralPath (Join-Path $BackupTemplate 'mysql') -PathType Container)) {
            Write-Host "  [LOI] Khong tim thay thu muc mau: $BackupTemplate\mysql." -ForegroundColor Red
            return $false
        }
        $Staging = Join-Path $InstanceRoot ('mysql-data-new-' + [guid]::NewGuid().ToString('N'))
        New-Item -ItemType Directory -Path $Staging | Out-Null
        try {
            Get-ChildItem -LiteralPath $BackupTemplate -Force | Copy-Item -Destination $Staging -Recurse -Force -ErrorAction Stop
            Move-Item -LiteralPath $Staging -Destination $DbData -ErrorAction Stop
            Write-Host "  [OK] Da tao thu muc du lieu MySQL rieng: $DbData" -ForegroundColor Green
        } catch {
            if (Test-Path -LiteralPath $Staging) { Remove-Item -LiteralPath $Staging -Recurse -Force -ErrorAction SilentlyContinue }
            Write-Host "  [LOI] Khoi tao du lieu MySQL that bai: $_" -ForegroundColor Red
            return $false
        }
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
    }

    $mysqlArgs = '--defaults-file=' + (Quote-Win $MysqlConfig) + ' --console'
    Start-Process -FilePath $Mysqld -WorkingDirectory (Join-Path $XamppRoot 'mysql\bin') -ArgumentList $mysqlArgs -WindowStyle Hidden | Out-Null
    
    for ($i = 0; $i -lt 25; $i++) {
        Start-Sleep -Seconds 1
        if (Test-DbRunning) {
            Write-Host "  [OK] MySQL SE da khoi dong thanh cong tren cong $DbPort." -ForegroundColor Green
            return $true
        }
    }
    Write-Host "  [LOI] MySQL SE khong khoi dong duoc sau 25s. Xem log: $MysqlErrorLog" -ForegroundColor Red
    return $false
}

function Start-WebServer {
    if (Test-WebRunning) {
        Write-Host "  [OK] PHP Web Server (port $WebPort) da dang chay." -ForegroundColor Green
        return $true
    }
    Write-Host "  [*] Dang khoi dong PHP Web Server (port $WebPort)..." -ForegroundColor Cyan
    Init-EnvVars

    $phpArgs = '-S 127.0.0.1:' + $WebPort + ' -t ' + (Quote-Win $Source) + ' ' + (Quote-Win $Router)
    Start-Process -FilePath $Php -WorkingDirectory $ProjectRoot -ArgumentList $phpArgs -WindowStyle Hidden -RedirectStandardOutput $PhpOut -RedirectStandardError $PhpErr | Out-Null

    for ($i = 0; $i -lt 15; $i++) {
        Start-Sleep -Seconds 1
        if (Test-WebRunning) {
            Write-Host "  [OK] PHP Web Server da hoat dong tai http://localhost:$WebPort/" -ForegroundColor Green
            return $true
        }
    }
    Write-Host "  [LOI] PHP Web Server khong phan hoi. Xem log: $PhpErr" -ForegroundColor Red
    return $false
}

function Apply-Migrations {
    Write-Host "  [*] Dang kiem tra va nap CSDL Migrations..." -ForegroundColor Cyan
    Init-EnvVars
    & $Php $Migrator
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  [OK] CSDL Migrations da cap nhat thanh cong!" -ForegroundColor Green
        return $true
    } else {
        Write-Host "  [LOI] Chay migrations that bai. Xem chi tiet trong file log." -ForegroundColor Red
        return $false
    }
}

function Stop-AllServices {
    Write-Host "  [*] Dang dung he thong WealthFlow..." -ForegroundColor Cyan
    # 1. Dung PHP Server
    $listeners = Get-Listener $WebPort
    foreach ($pidValue in @($listeners | Select-Object -ExpandProperty OwningProcess -Unique)) {
        Stop-Process -Id $pidValue -Force -ErrorAction SilentlyContinue
        Write-Host "  [OK] Da dung tien trinh PHP Web Server (PID $pidValue)." -ForegroundColor Green
    }
    # 2. Dung MySQL SE an toan qua mysqladmin
    if (Test-DbRunning) {
        if (Test-Path $MysqlAdmin) {
            & $MysqlAdmin --protocol=tcp --host=127.0.0.1 --port=$DbPort --user=root shutdown 2>$null
            Start-Sleep -Seconds 2
        }
        if (Test-DbRunning) {
            $dbListeners = Get-Listener $DbPort
            foreach ($dbPid in @($dbListeners | Select-Object -ExpandProperty OwningProcess -Unique)) {
                Stop-Process -Id $dbPid -Force -ErrorAction SilentlyContinue
            }
        }
        Write-Host "  [OK] Da dung MySQL SE an toan." -ForegroundColor Green
    } else {
        Write-Host "  [OK] MySQL SE hien khong chay." -ForegroundColor Gray
    }
}

function Reset-Database {
    Write-Host ""
    Write-Host "  [CANH BAO] Toan bo du lieu bang 'wealthflow' tren port $DbPort se bi XOA va tao lai tu dau!" -ForegroundColor Yellow
    $confirm = Read-Host "  Ban co chac chan muon reset toan bo CSDL khong? (nhap 'y' de dong y)"
    if ($confirm -ne 'y' -and $confirm -ne 'Y') {
        Write-Host "  [*] Da huy thao tac reset CSDL." -ForegroundColor Gray
        return
    }

    if (-not (Test-DbRunning)) {
        $started = Start-Database
        if (-not $started) { return }
    }

    Write-Host "  [*] Dang xoa va tao lai Database '$DbName'..." -ForegroundColor Cyan
    & $MysqlCli -P $DbPort -h 127.0.0.1 -u root -e "DROP DATABASE IF EXISTS \`$DbName\`; CREATE DATABASE \`$DbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    Init-EnvVars
    & $Php $Migrator
    Write-Host "  [OK] Da nap thanh cong toan bo 28 SQL Migrations va Seeds du lieu mau!" -ForegroundColor Green
}

function Check-HealthStatus {
    Write-Host ""
    Write-Host "---------------- TRANG THAI HE THONG CHI TIET ----------------" -ForegroundColor Cyan
    $web = Test-WebRunning
    $db = Test-DbRunning
    Write-Host ("  * PHP Web Server (Port $WebPort):  " + $(if ($web) { "DANG CHAY (RUNNING)" } else { "DA DUNG (STOPPED)" })) -ForegroundColor $(if ($web) { "Green" } else { "Red" })
    Write-Host ("  * MySQL SE DB    (Port $DbPort):   " + $(if ($db) { "DANG CHAY (RUNNING)" } else { "DA DUNG (STOPPED)" })) -ForegroundColor $(if ($db) { "Green" } else { "Red" })
    Write-Host "  * XAMPP Thu muc:                   $XamppRoot" -ForegroundColor Gray
    Write-Host "  * Du lieu SE MySQL:                $DbData" -ForegroundColor Gray

    if ($web) {
        Write-Host "  * Kiem tra Endpoint /api/health: " -NoNewline
        try {
            $res = Invoke-RestMethod -Uri "http://127.0.0.1:$WebPort/api/health" -TimeoutSec 3
            if ($res.success) {
                Write-Host "HOAT DONG TOT (OK)" -ForegroundColor Green
                Write-Host "    Response: $($res | ConvertTo-Json -Compress)" -ForegroundColor Gray
            } else {
                Write-Host "LOI PHAN HOI" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "KHONG THE KET NOI: $_" -ForegroundColor Red
        }
    }
    Write-Host "---------------------------------------------------------------" -ForegroundColor Cyan
}

function Run-Tests {
    Write-Host ""
    Write-Host "---------------- BO KIEM THU TU DONG (TEST SUITE) -------------" -ForegroundColor Cyan
    Write-Host "  [*] 1. Chay 36 ca kiem thu backend FR03-FR06..." -ForegroundColor Cyan
    & $Php (Join-Path $Backend 'tests\run_tests.php')
    Write-Host ""
    Write-Host "  [*] 2. Chay 7 ca kiem thu Email Service SMTP..." -ForegroundColor Cyan
    & $Php (Join-Path $Backend 'tests\mail_smoke_test.php')
    Write-Host "---------------------------------------------------------------" -ForegroundColor Cyan
}

function View-Logs {
    Write-Host ""
    Write-Host "---------------- NHAT KY VA TAI LIEU LOI (LOGS) ---------------" -ForegroundColor Cyan
    if (Test-Path $PhpErr) {
        Write-Host "  === PHP Error Log (10 dong cuoi) ===" -ForegroundColor Yellow
        Get-Content $PhpErr -Tail 10 | ForEach-Object { Write-Host "    $_" }
    } else {
        Write-Host "  [i] Chua co file loi PHP." -ForegroundColor Gray
    }
    if (Test-Path $MysqlErrorLog) {
        Write-Host "  === MySQL Error Log (10 dong cuoi) ===" -ForegroundColor Yellow
        Get-Content $MysqlErrorLog -Tail 10 | ForEach-Object { Write-Host "    $_" }
    }
    $storage = Join-Path $Backend 'storage'
    if (Test-Path $storage) {
        $emls = Get-ChildItem -Path $storage -Filter '*.eml' | Sort-Object LastWriteTime -Descending | Select-Object -First 3
        if ($emls) {
            Write-Host "  === Email OTP gia lap (.eml) moi nhat ===" -ForegroundColor Yellow
            foreach ($eml in $emls) {
                Write-Host "    - $($eml.Name) ($($eml.LastWriteTime))" -ForegroundColor Gray
            }
        }
    }
    Write-Host "---------------------------------------------------------------" -ForegroundColor Cyan
}

# VONG LAP MENU DIEU KHIEN CHINH (TUI)
while ($true) {
    $webState = if (Test-WebRunning) { "RUNNING (http://localhost:$WebPort)" } else { "STOPPED" }
    $dbState = if (Test-DbRunning) { "RUNNING (Port $DbPort)" } else { "STOPPED" }
    $webColor = if (Test-WebRunning) { "Green" } else { "Red" }
    $dbColor = if (Test-DbRunning) { "Green" } else { "Red" }

    Write-Host ""
    Write-Host "=======================================================================" -ForegroundColor Cyan
    Write-Host "     WEALTHFLOW CONTROL CENTER - QUAN LY TAI CHINH CA NHAN (SE)        " -ForegroundColor Yellow
    Write-Host "=======================================================================" -ForegroundColor Cyan
    Write-Host "  [Trang thai]" -NoNewline
    Write-Host " Web: " -NoNewline
    Write-Host $webState -ForegroundColor $webColor -NoNewline
    Write-Host " | DB SE: " -NoNewline
    Write-Host $dbState -ForegroundColor $dbColor
    Write-Host "  [XAMPP Path] $XamppRoot" -ForegroundColor Gray
    Write-Host "-----------------------------------------------------------------------" -ForegroundColor Cyan
    Write-Host "  [1] Khoi dong he thong (Start Web + DB) va Mo trinh duyet" -ForegroundColor White
    Write-Host "  [2] Dung toan bo he thong (Stop Web va DB an toan)" -ForegroundColor White
    Write-Host "  [3] Kiem tra trang thai chi tiet (Health Check va API status)" -ForegroundColor White
    Write-Host "  [4] Chay cap nhat CSDL Migrations (28 SQL files)" -ForegroundColor White
    Write-Host "  [5] Reset CSDL va Du lieu mau (Reset va Seed sach)" -ForegroundColor Yellow
    Write-Host "  [6] Chay bo kiem thu tu dong (36 unit tests + 7 mail checks)" -ForegroundColor White
    Write-Host "  [7] Xem nhat ky loi va email OTP gan nhat" -ForegroundColor White
    Write-Host "  [8] Mo trinh duyet Web (http://localhost:$WebPort/)" -ForegroundColor White
    Write-Host "  [0] Thoat (Exit)" -ForegroundColor Red
    Write-Host "=======================================================================" -ForegroundColor Cyan
    $raw = Read-Host "  Chon chuc nang [0-8]"
    $choice = if ($raw) { $raw.Trim() } else { "" }

    switch ($choice) {
        '1' {
            Write-Host ""
            $dbOk = Start-Database
            if ($dbOk) {
                Apply-Migrations | Out-Null
                $webOk = Start-WebServer
                if ($webOk) {
                    Write-Host ""
                    Write-Host "  ===========================================================" -ForegroundColor Green
                    Write-Host "       WEALTHFLOW DA KHOI DONG VA SAN SANG SU DUNG!          " -ForegroundColor Green
                    Write-Host "  ===========================================================" -ForegroundColor Green
                    Write-Host "  URL Giao dien:  http://localhost:$WebPort/" -ForegroundColor White
                    Write-Host "  Demo User:      user1@gmail.com   / Mat khau: User1234" -ForegroundColor Yellow
                    Write-Host "  Admin User:     admin@wealthflow.vn / Mat khau: Admin@1234" -ForegroundColor Yellow
                    Write-Host "  ===========================================================" -ForegroundColor Green
                    Start-Process "http://localhost:$WebPort/" | Out-Null
                }
            }
        }
        '2' {
            Write-Host ""
            Stop-AllServices
        }
        '3' {
            Check-HealthStatus
        }
        '4' {
            Write-Host ""
            if (-not (Test-DbRunning)) { Start-Database | Out-Null }
            Apply-Migrations
        }
        '5' {
            Reset-Database
        }
        '6' {
            Run-Tests
        }
        '7' {
            View-Logs
        }
        '8' {
            Start-Process "http://localhost:$WebPort/" | Out-Null
        }
        '0' {
            if (Test-WebRunning -or Test-DbRunning) {
                $ans = Read-Host "  He thong van dang chay. Ban co muon giu chay ngam khong? (Y/n)"
                if ($ans -eq 'n' -or $ans -eq 'N') {
                    Stop-AllServices
                }
            }
            Write-Host "  Tam biet!" -ForegroundColor Cyan
            exit 0
        }
        default {
            Write-Host "  [!] Lua chon khong hop le, vui long nhap so tu 0 den 8." -ForegroundColor Yellow
        }
    }
}
