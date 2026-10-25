@echo off
setlocal
cd /d "%~dp0"
call "%~dp0SETUP_SE.bat" %*
exit /b %ERRORLEVEL%
