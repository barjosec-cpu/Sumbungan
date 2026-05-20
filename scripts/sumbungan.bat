@echo off
REM Sumbungan - single entry script (all CI/CD commands)
REM Usage: scripts\sumbungan.bat [setup|jenkins|app|token|cicd|help] [options]
cd /d "%~dp0.."
powershell -ExecutionPolicy Bypass -File "%~dp0sumbungan.ps1" %*
exit /b %ERRORLEVEL%
