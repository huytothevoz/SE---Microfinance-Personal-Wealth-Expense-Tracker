@echo off
setlocal
cd /d "%~dp0"
title SE WealthFlow - Safe Local Setup
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-se.ps1" -XamppRoot "C:\xampp" -WebPort 8080 -DbPort 3307
set "RESULT=%ERRORLEVEL%"
if not "%RESULT%"=="0" (
    echo.
    echo Setup failed. Copy the red error lines or take a screenshot.
)
echo.
pause
exit /b %RESULT%
