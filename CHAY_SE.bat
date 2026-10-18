@echo off
setlocal
cd /d "%~dp0"
title SE WealthFlow - Safe Local Setup
set "XAMPP_ROOT=%XAMPP_DIR%"
if not defined XAMPP_ROOT (
    if exist "C:\xampp\php\php.exe" set "XAMPP_ROOT=C:\xampp"
)
if not defined XAMPP_ROOT (
    echo XAMPP was not found at C:\xampp. Set XAMPP_DIR to its installation folder first.
    echo Example: set "XAMPP_DIR=F:\xampp"
    pause
    exit /b 1
)
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-se.ps1" -XamppRoot "%XAMPP_ROOT%" -WebPort 8080 -DbPort 3307
set "RESULT=%ERRORLEVEL%"
if not "%RESULT%"=="0" (
    echo.
    echo SETUP FAILED. The actual MySQL error log should appear above.
    echo Send mysql-error.log or the output of KIEM_TRA_MYSQL_SE.bat if still failing.
)
echo.
pause
exit /b %RESULT%
