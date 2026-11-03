# Stop only the WealthFlow PHP server; leave XAMPP MariaDB and other projects running.
$XamppRoot = 'C:\xampp'
$Php = Join-Path $XamppRoot 'php\php.exe'
$Router = Join-Path $PSScriptRoot 'source\backend\router.php'
$WebPort = 8070
$listeners = @(Get-NetTCPConnection -State Listen -LocalPort $WebPort -ErrorAction SilentlyContinue)

foreach ($pidValue in @($listeners | Select-Object -ExpandProperty OwningProcess -Unique)) {
    $processInfo = Get-CimInstance Win32_Process -Filter "ProcessId = $pidValue" -ErrorAction SilentlyContinue
    if ($processInfo -and $processInfo.ExecutablePath -ieq $Php -and
        $processInfo.CommandLine -and
        $processInfo.CommandLine.IndexOf($Router, [StringComparison]::OrdinalIgnoreCase) -ge 0) {
        Stop-Process -Id $pidValue -ErrorAction Stop
        Write-Host "Stopped WealthFlow PHP server (PID $pidValue)." -ForegroundColor Green
    } else {
        Write-Host "Port $WebPort is owned by another process; leaving it alone." -ForegroundColor Yellow
    }
}

Write-Host 'XAMPP MariaDB on port 3306 was left running.' -ForegroundColor Green
