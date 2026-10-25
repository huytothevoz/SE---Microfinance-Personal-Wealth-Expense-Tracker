@echo off
setlocal
cd /d "%~dp0"
title SE WealthFlow - Setup

set "XAMPP_ROOT=%XAMPP_DIR%"
if not defined XAMPP_ROOT (
    if exist "C:\xampp\php\php.exe" if exist "C:\xampp\mysql\bin\mysqld.exe" set "XAMPP_ROOT=C:\xampp"
    if not defined XAMPP_ROOT if exist "D:\xampp\php\php.exe" if exist "D:\xampp\mysql\bin\mysqld.exe" set "XAMPP_ROOT=D:\xampp"
    if not defined XAMPP_ROOT if exist "E:\xampp\php\php.exe" if exist "E:\xampp\mysql\bin\mysqld.exe" set "XAMPP_ROOT=E:\xampp"
    if not defined XAMPP_ROOT if exist "F:\xampp\php\php.exe" if exist "F:\xampp\mysql\bin\mysqld.exe" set "XAMPP_ROOT=F:\xampp"
)

if not defined XAMPP_ROOT (
    echo XAMPP was not found. Set XAMPP_DIR to the folder containing php\php.exe and mysql\bin\mysqld.exe.
    echo Example: set "XAMPP_DIR=D:\xampp"
    echo.
    pause
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-se.ps1" -XamppRoot "%XAMPP_ROOT%" -WebPort 8080 -DbPort 3307
set "RESULT=%ERRORLEVEL%"
if not "%RESULT%"=="0" (
    echo.
    echo SETUP FAILED. Read the error above before trying again.
    echo Do not delete or reset the SE database to troubleshoot.
)
echo.
pause
exit /b %RESULT%
