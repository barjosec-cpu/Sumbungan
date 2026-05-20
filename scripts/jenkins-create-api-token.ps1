# Create a Jenkins API token and append it to .env
# Usage: powershell -ExecutionPolicy Bypass -File scripts/jenkins-create-api-token.ps1

param(
    [string]$JenkinsUrl = "http://localhost:9090",
    [string]$User = "admin"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$EnvFile = Join-Path $Root ".env"

function Test-JenkinsReachable {
    param([string]$Url)
    try {
        $null = Invoke-WebRequest -Uri "$Url/login" -UseBasicParsing -TimeoutSec 5
        return $true
    } catch {
        return $false
    }
}

if (-not (Test-JenkinsReachable -Url $JenkinsUrl)) {
    Write-Host ""
    Write-Host "Jenkins is not reachable at $JenkinsUrl" -ForegroundColor Red
    Write-Host "Start it first:  scripts\start-jenkins.bat" -ForegroundColor Yellow
    Write-Host "Then check:      docker ps --filter name=sumbungan_jenkins" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

$sec = Read-Host "Jenkins password for user '$User'" -AsSecureString
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec)
$pass = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)

$pair = "${User}:${pass}"
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$crumb = Invoke-RestMethod `
    -Uri "$JenkinsUrl/crumbIssuer/api/json" `
    -Headers @{ Authorization = "Basic $b64" } `
    -WebSession $session `
    -Method Get

$headers = @{
    Authorization = "Basic $b64"
}
$headers[$crumb.crumbRequestField] = $crumb.crumb

$jsonPayload = '{"newTokenName":"ci-cd-auto"}'
$formBody = "json=" + [uri]::EscapeDataString($jsonPayload)

$tokenUri = "$JenkinsUrl/user/$User/descriptorByName/jenkins.security.ApiTokenProperty/generateNewToken"

$token = $null
try {
    $resp = Invoke-RestMethod `
        -Method Post `
        -Uri $tokenUri `
        -Headers $headers `
        -WebSession $session `
        -ContentType "application/x-www-form-urlencoded" `
        -Body $formBody
    $token = $resp.data.tokenValue
} catch {
    Write-Warning "Invoke-RestMethod failed ($($_.Exception.Message)); trying curl..."
}

if (-not $token -and (Get-Command curl.exe -ErrorAction SilentlyContinue)) {
    try {
        $crumbJson = curl.exe -fsS -u $pair "$JenkinsUrl/crumbIssuer/api/json" | ConvertFrom-Json
        $crumbField = $crumbJson.crumbRequestField
        $crumbValue = $crumbJson.crumb
        $raw = curl.exe -fsS -u $pair -H "${crumbField}: ${crumbValue}" -X POST `
            -H "Content-Type: application/x-www-form-urlencoded" `
            --data-raw "json={""newTokenName"":""ci-cd-auto""}" `
            $tokenUri
        $parsed = $raw | ConvertFrom-Json
        $token = $parsed.data.tokenValue
    } catch {
        Write-Host "curl token request failed. Check admin password and Jenkins security settings." -ForegroundColor Red
    }
}

if (-not $token) {
    throw "Jenkins did not return a token value (403 often means wrong password or CSRF). Create token manually in Jenkins UI: admin -> Security -> API Token."
}

Write-Host "New API token created (save it now - shown once)"

$lines = @()
if (Test-Path $EnvFile) { $lines = Get-Content $EnvFile }
$updated = $false
$newLines = foreach ($line in $lines) {
    if ($line -match '^\s*JENKINS_API_TOKEN=') {
        $updated = $true
        "JENKINS_API_TOKEN=$token"
    } else { $line }
}
if (-not $updated) {
    $newLines += "JENKINS_API_TOKEN=$token"
}
if (-not ($newLines | Where-Object { $_ -match '^\s*JENKINS_USER=' })) {
    $newLines += "JENKINS_USER=$User"
}
if (-not ($newLines | Where-Object { $_ -match '^\s*JENKINS_URL=' })) {
    $newLines += "JENKINS_URL=$JenkinsUrl"
}
Set-Content -Path $EnvFile -Value $newLines -Encoding UTF8
Write-Host "Updated $EnvFile"
