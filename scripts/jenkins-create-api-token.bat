@echo off
REM Create Jenkins API token and save to .env (default user: admin)
cd /d "%~dp0.."
powershell -ExecutionPolicy Bypass -File "%~dp0jenkins-create-api-token.ps1" %*
exit /b %ERRORLEVEL%
