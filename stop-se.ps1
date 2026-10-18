# Stop only SE processes; never force-kill mysqld (a hard stop can corrupt databases).
$XamppRoot = 'C:\xampp'
$Php = Join-Path $XamppRoot 'php\php.exe'
$Mysql = Join-Path $XamppRoot 'mysql\bin\mysqld.exe'
$MysqlAdmin = Join-Path $XamppRoot 'mysql\bin\mysqladmin.exe'
$Router = Join-Path $PSScriptRoot 'source\backend\router.php'
$Ini = Join-Path $XamppRoot 'se-instance\my-se.ini'
foreach ($item in @(@($Php, $Router, 8080, 'SE PHP'), @($Mysql, $Ini, 3307, 'SE MySQL'))) {
    $expectedExe, $marker, $port, $description = $item
    $listeners = @(Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue)
    foreach ($pidValue in @($listeners | Select-Object -ExpandProperty OwningProcess -Unique)) {
        $p = Get-CimInstance Win32_Process -Filter "ProcessId = $pidValue" -ErrorAction SilentlyContinue
        if ($p -and $p.ExecutablePath -and $p.CommandLine -and
            $p.ExecutablePath -ieq $expectedExe -and
            $p.CommandLine.IndexOf($marker, [StringComparison]::OrdinalIgnoreCase) -ge 0) {
            if ($port -eq 8080) {
                Stop-Process -Id $pidValue -ErrorAction Stop
                Write-Host "Stopped $description (PID $pidValue)." -ForegroundColor Green
            } elseif (Test-Path -LiteralPath $MysqlAdmin) {
                # Clean server shutdown. For the default isolated server root has no password.
                & $MysqlAdmin --protocol=tcp --host=127.0.0.1 --port=3307 --user=root shutdown
                if ($LASTEXITCODE -eq 0) {
                    Write-Host 'SE MySQL stopped cleanly.' -ForegroundColor Green
                } else {
                    Write-Host 'Could not shut down SE MySQL cleanly (possibly a changed root password). Keeping it running.' -ForegroundColor Yellow
                }
            } else {
                Write-Host 'mysqladmin.exe missing. SE MySQL left running to protect data.' -ForegroundColor Yellow
            }
        } else {
            Write-Host "Port $port is owned by another process; leaving it alone."
        }
    }
}
Write-Host 'LTW, main XAMPP and all database files were untouched.'
