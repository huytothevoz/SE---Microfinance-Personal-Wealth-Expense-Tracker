@echo off
setlocal
cd /d "%~dp0"
title WealthFlow Control Center
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\wealthflow-manager.ps1" %*
exit /b %ERRORLEVEL%
