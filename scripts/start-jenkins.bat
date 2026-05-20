@echo off
cd /d "%~dp0.."
powershell -ExecutionPolicy Bypass -File "%~dp0start-jenkins.ps1" %*
exit /b %ERRORLEVEL%
