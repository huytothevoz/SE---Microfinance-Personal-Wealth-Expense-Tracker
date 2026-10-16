@echo off
setlocal
title WealthFlow Runner
cd /d "%~dp0"

echo [WealthFlow] Dang khoi dong he thong...
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\run-wealthflow.ps1" %*
if errorlevel 1 (
    echo.
    echo [ERROR] Co loi xay ra khi khoi dong WealthFlow.
    pause
    exit /b %errorlevel%
)
pause
