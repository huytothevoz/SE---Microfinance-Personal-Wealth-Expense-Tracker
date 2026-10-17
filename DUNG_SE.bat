@echo off
setlocal
cd /d "%~dp0"
title Stop SE (only the owned PHP and database instance)
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0stop-se.ps1"
echo.
pause
