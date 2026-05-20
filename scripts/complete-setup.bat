@echo off
cd /d "%~dp0.."
powershell -ExecutionPolicy Bypass -File "%~dp0complete-setup.ps1" %*
exit /b %ERRORLEVEL%
