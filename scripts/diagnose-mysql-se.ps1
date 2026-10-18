[CmdletBinding()]
param([string]$XamppRoot = 'C:\xampp', [int]$DbPort = 3307)
$ErrorActionPreference = 'Continue'
$Instance = Join-Path $XamppRoot 'se-instance'
Write-Host '=== WEALTHFLOW SE MYSQL DIAGNOSTICS - read only ===' -ForegroundColor Cyan
Write-Host "XAMPP root: $XamppRoot"
Write-Host "SE instance: $Instance"
Write-Host "DB port: $DbPort"
Write-Host "Data exists: $(Test-Path (Join-Path $Instance 'mysql-data\mysql'))"
Write-Host "Temp exists: $(Test-Path (Join-Path $Instance 'tmp'))"
Write-Host "Config exists: $(Test-Path (Join-Path $Instance 'my-se.ini'))"
Write-Host ''
Write-Host '--- TCP listener 3307 ---' -ForegroundColor Yellow
$listeners = @(Get-NetTCPConnection -State Listen -LocalPort $DbPort -ErrorAction SilentlyContinue)
if ($listeners.Count -eq 0) { Write-Host '(not listening)' }
else {
    foreach ($l in $listeners) {
        $ownerPid = $l.OwningProcess
        $proc = Get-CimInstance Win32_Process -Filter "ProcessId = $ownerPid" -ErrorAction SilentlyContinue
        Write-Host "LocalAddress=$($l.LocalAddress) Port=$DbPort PID=$ownerPid Process=$($proc.Name)"
    }
}
$paths = @()
$paths += (Join-Path $Instance 'mysql-error.log')
$paths += @(Get-ChildItem -LiteralPath $Instance -Filter 'mysql-start-*.stderr.log' -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1 | ForEach-Object { $_.FullName })
$paths += @(Get-ChildItem -LiteralPath $Instance -Filter 'mysql-start-*.stdout.log' -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1 | ForEach-Object { $_.FullName })
foreach ($path in $paths) {
    Write-Host ''
    Write-Host "--- $path (last 40 lines) ---" -ForegroundColor Yellow
    if (Test-Path -LiteralPath $path -PathType Leaf) {
        Get-Content -LiteralPath $path -Tail 40 -ErrorAction SilentlyContinue
    } else { Write-Host '(file not found)' }
}
Write-Host ''
Write-Host 'Read-only: no process or database was stopped, started, reset, or deleted.' -ForegroundColor Green
