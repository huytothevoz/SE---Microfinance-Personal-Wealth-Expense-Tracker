@echo off
setlocal
chcp 65001 >nul
cd /d "%~dp0"
title WealthFlow - FR13 Visual E2E Tests (System Config & Default Categories)

python "%~dp0visual_e2e_runner.py" %*
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [!] Da xay ra loi trong qua trinh kiem thu.
    pause
)
exit /b %ERRORLEVEL%
