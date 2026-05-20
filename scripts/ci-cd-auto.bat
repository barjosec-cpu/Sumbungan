@echo off
REM Full CI/CD: GitHub push + Jenkins job1/job2/job3
REM Usage: scripts\ci-cd-auto.bat ["commit message"] [branch]

setlocal
cd /d "%~dp0.."

set MSG=%~1
if "%MSG%"=="" set MSG=CI/CD automated push

set BR=%~2
if "%BR%"=="" set BR=main

powershell -ExecutionPolicy Bypass -File "%~dp0ci-cd-auto.ps1" -Message "%MSG%" -Branch "%BR%"
exit /b %ERRORLEVEL%
