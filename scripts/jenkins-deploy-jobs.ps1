# Deploy job1, job2, job3 into the running Jenkins container
# Usage: powershell -ExecutionPolicy Bypass -File scripts/jenkins-deploy-jobs.ps1 [-Restart]

param(
    [switch]$Restart
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

$Container = "sumbungan_jenkins"
$Jobs = @("job1", "job2", "job3")

if (-not (docker ps --format "{{.Names}}" | Select-String -Pattern "^${Container}$")) {
    Write-Host "Starting Jenkins..."
    docker compose -f docker-compose.jenkins.yml up -d
    $Restart = $true
    Start-Sleep -Seconds 15
}

foreach ($job in $Jobs) {
    $src = Join-Path $Root "jenkins\jobs\$job\config.xml"
    if (-not (Test-Path $src)) {
        throw "Missing $src"
    }
    docker exec -u root $Container mkdir -p "/var/jenkins_home/jobs/$job" | Out-Null
    docker cp $src "${Container}:/var/jenkins_home/jobs/$job/config.xml"
    docker exec -u root $Container chown -R jenkins:jenkins "/var/jenkins_home/jobs/$job"
    Write-Host "Deployed $job"
}

if ($Restart) {
    Write-Host "Restarting Jenkins to load job configs..."
    docker restart $Container | Out-Null
    & (Join-Path $Root "scripts\jenkins-wait-ready.ps1")
} else {
    Write-Host "Job configs copied (no restart). Use -Restart after config changes."
}

Write-Host ""
Write-Host "Jenkins jobs at http://localhost:9090/"
Write-Host "  job1 - build"
Write-Host "  job2 - test"
Write-Host "  job3 - deployment"
