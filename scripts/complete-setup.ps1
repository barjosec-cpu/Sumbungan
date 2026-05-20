# Sumbungan Complete CI/CD Setup Script (Windows PowerShell)
# Run: powershell -ExecutionPolicy Bypass -File scripts/complete-setup.ps1

$JENKINS_PORT = 9090
$APP_PORT = 8080
$PHPMYADMIN_PORT = 8081

function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Warn { Write-Host $args -ForegroundColor Yellow }
function Write-Info { Write-Host $args -ForegroundColor Blue }

Write-Info ""
Write-Info "Sumbungan CI/CD Setup (Jenkins + Docker + GitHub)"
Write-Info ""

Write-Warn "Step 1: Checking prerequisites..."
$tools = @("docker", "git")
foreach ($cmd in $tools) {
    if (Get-Command $cmd -ErrorAction SilentlyContinue) {
        Write-Success "  $cmd OK"
    } else {
        Write-Host "  Missing: $cmd" -ForegroundColor Red
        exit 1
    }
}

Write-Warn "Step 2: Environment file..."
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Success "  Created .env from .env.example"
    Write-Warn "  Edit .env: GITHUB_TOKEN, JENKINS_API_TOKEN, GITHUB_REPO"
} else {
    Write-Warn "  .env already exists"
}

Write-Warn "Step 3: Starting Jenkins..."
# Remove stale container if a previous start failed (e.g. port conflict)
docker rm -f sumbungan_jenkins 2>$null | Out-Null
$port9090 = Get-NetTCPConnection -LocalPort 9090 -ErrorAction SilentlyContinue
if ($port9090) {
    Write-Warn "  Port 9090 is in use - stop the other service or change docker-compose.jenkins.yml"
}
docker compose -f docker-compose.jenkins.yml up -d --build
if ($LASTEXITCODE -ne 0) {
    Write-Host "  Jenkins failed to start. Check: docker compose -f docker-compose.jenkins.yml logs jenkins" -ForegroundColor Red
    exit 1
}
& "$PSScriptRoot\jenkins-wait-ready.ps1"

Write-Warn "Step 4: Deploy Jenkins jobs..."
& "$PSScriptRoot\jenkins-deploy-jobs.ps1" -Restart

Write-Warn "Step 5: Starting application stack..."
docker compose -f docker-compose.yml up -d --build

Write-Success ""
Write-Success "Setup complete"
Write-Info "  Jenkins:     http://localhost:$JENKINS_PORT"
Write-Info "  App:         http://localhost:$APP_PORT"
Write-Info "  PHPMyAdmin:  http://localhost:$PHPMYADMIN_PORT"
Write-Info ""
Write-Info "Next: set tokens in .env, then run:"
Write-Info "  scripts\ci-cd-auto.bat `"your commit message`""
