@echo off
setlocal
cd /d "%~dp0"
set "XAMPP_ROOT=%XAMPP_DIR%"
if not defined XAMPP_ROOT set "XAMPP_ROOT=C:\xampp"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\diagnose-mysql-se.ps1" -XamppRoot "%XAMPP_ROOT%" -DbPort 3307
pause
