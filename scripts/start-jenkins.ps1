# Start Jenkins (separate stack - survives app deploy)
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

Write-Host "Starting Jenkins on http://localhost:9090 ..."
docker compose -f docker-compose.jenkins.yml up -d --build

& "$Root\scripts\jenkins-wait-ready.ps1"
Write-Host "Jenkins is up: http://localhost:9090"
