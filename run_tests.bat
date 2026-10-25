@echo off
setlocal
chcp 65001 >nul
cd /d "%~dp0"
title WealthFlow - Master Test Center (Visual E2E Testing)

python "%~dp0source\tests\master_test_runner.py" %*
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [!] Da xay ra loi trong qua trinh kiem thu.
    pause
)
exit /b %ERRORLEVEL%
