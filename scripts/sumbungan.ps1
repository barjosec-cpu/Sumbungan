# Sumbungan - single automation script (setup, Jenkins, GitHub, CI/CD)
# Usage: scripts\sumbungan.bat <command> [options]
#   setup              - .env, start Jenkins (auto-creates jobs), start app
#   jenkins            - start Jenkins only (jobs auto-seeded on boot)
#   app                - start app stack only
#   token              - create Jenkins API token -> .env
#   cicd [message]     - GitHub push + Jenkins job1 -> job2 -> job3
#   help               - show commands

param(
    [Parameter(Position = 0)]
    [ValidateSet('setup', 'jenkins', 'app', 'token', 'cicd', 'help')]
    [string]$Command = 'help',
    [Parameter(Position = 1)]
    [string]$Message = "",
    [string]$Branch = "",
    [switch]$SkipGitHub,
    [switch]$SkipJenkins,
    [int]$BuildTimeoutMin = 60
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

# --- shared helpers ---

function Load-DotEnv {
    param([string]$Path)
    if (-not (Test-Path $Path)) { return }
    Get-Content $Path | ForEach-Object {
        if ($_ -match '^\s*#' -or $_ -notmatch '=') { return }
        $k, $v = $_ -split '=', 2
        $k = $k.Trim()
        $v = $v.Trim().Trim('"').Trim("'")
        if ($k) { Set-Item -Path "env:$k" -Value $v }
    }
}

function Write-Title { param([string]$Text) Write-Host "`n=== $Text ===`n" -ForegroundColor Cyan }

Load-DotEnv (Join-Path $Root '.env')

$JenkinsUrl   = if ($env:JENKINS_URL) { $env:JENKINS_URL } else { 'http://localhost:9090' }
$JenkinsUser  = if ($env:JENKINS_USER) { $env:JENKINS_USER } else { 'admin' }
$JenkinsToken = $env:JENKINS_API_TOKEN
$GithubRepo   = if ($env:GITHUB_REPO) { $env:GITHUB_REPO } else { 'barjosec-cpu/Sumbungan' }
$GithubToken  = $env:GITHUB_TOKEN
$BuildTimeoutSec = $BuildTimeoutMin * 60

if (-not $Branch) {
    $Branch = (git branch --show-current 2>$null)
    if (-not $Branch) { $Branch = 'main' }
}
$env:GIT_BRANCH = if ($env:GIT_BRANCH) { $env:GIT_BRANCH } else { $Branch }

if (-not $Message) {
    $Message = "CI/CD automated push $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
}

function Wait-JenkinsHttp {
    param([int]$TimeoutSec = 300)
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    while ((Get-Date) -lt $deadline) {
        try {
            $r = Invoke-WebRequest -Uri "$JenkinsUrl/login" -UseBasicParsing -TimeoutSec 5
            if ($r.StatusCode -eq 200) { return $true }
        } catch { Start-Sleep -Seconds 3 }
    }
    return $false
}

function Wait-JenkinsJobsSeeded {
    param([int]$TimeoutSec = 120)
    $jobs = @('job1', 'job2', 'job3')
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    Write-Host 'Waiting for auto-created Jenkins jobs (job1, job2, job3)...'
    while ((Get-Date) -lt $deadline) {
        $found = 0
        foreach ($j in $jobs) {
            try {
                $null = Invoke-WebRequest -Uri "$JenkinsUrl/job/$j/api/json" -UseBasicParsing -TimeoutSec 5
                $found++
            } catch { }
        }
        if ($found -eq 3) {
            Write-Host '  All jobs ready (seeded on Jenkins startup).'
            return $true
        }
        Start-Sleep -Seconds 5
    }
    Write-Warning 'Jobs not all visible yet - they seed on first Jenkins start. Open http://localhost:9090 and refresh.'
    return $false
}

function Start-JenkinsStack {
    docker rm -f sumbungan_jenkins 2>$null | Out-Null
    Write-Host 'Starting Jenkins (jobs auto-create from jenkins/jobs on boot)...'
    docker compose -f docker-compose.jenkins.yml up -d --build
    if ($LASTEXITCODE -ne 0) { throw 'Jenkins failed to start' }
    if (-not (Wait-JenkinsHttp)) { throw "Jenkins not ready at $JenkinsUrl" }
    Wait-JenkinsJobsSeeded | Out-Null
}

function Start-AppStack {
    Write-Host 'Starting application stack...'
    docker compose -f docker-compose.yml up -d --build
}

function Invoke-GitHubPush {
    param([string]$CommitMessage, [string]$TargetBranch)

    if (-not $GithubToken) {
        Write-Warning 'GITHUB_TOKEN not set in .env - skipping GitHub push'
        return
    }

    $env:GITHUB_REPO = $GithubRepo
    $env:GITHUB_TOKEN = $GithubToken

    Write-Host "Pushing to GitHub ($GithubRepo branch $TargetBranch)..."

    $prevEap = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    try {
        if (-not (git config user.name 2>$null)) {
            git config user.name 'Jenkins CI' 2>&1 | Out-Null
            git config user.email 'jenkins@sumbungan.local' 2>&1 | Out-Null
        }

        $remote = "https://x-access-token:${GithubToken}@github.com/${GithubRepo}.git"

        $hasChanges = $false
        git diff-index --quiet HEAD -- 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) { $hasChanges = $true }
        elseif (git status --porcelain 2>&1) { $hasChanges = $true }

        if ($hasChanges) {
            git add -A 2>&1 | Out-Null
            git commit -m $CommitMessage 2>&1 | Out-Null
            if ($LASTEXITCODE -ne 0) { throw 'git commit failed' }
        } else {
            Write-Host 'No local changes to commit'
        }

        git fetch $remote $TargetBranch 2>&1 | Out-Null
        if ($LASTEXITCODE -eq 0) {
            git rebase FETCH_HEAD 2>&1 | Out-Null
            if ($LASTEXITCODE -ne 0) { throw 'Rebase failed - resolve conflicts locally' }
        }

        git push $remote "HEAD:${TargetBranch}" 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) { throw 'GitHub push failed' }
        Write-Host 'GitHub push OK'
    } finally {
        $ErrorActionPreference = $prevEap
    }
}

function Get-JenkinsHeaders {
    $pair = "${JenkinsUser}:${JenkinsToken}"
    $b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
    return @{ Authorization = "Basic $b64" }
}

function Invoke-JenkinsApi {
    param([string]$Uri, [string]$Method = 'Get')
    return Invoke-RestMethod -Uri $Uri -Headers (Get-JenkinsHeaders) -Method $Method -TimeoutSec 120
}

function Get-JenkinsLastBuildNumber {
    param([string]$JobName)
    try {
        return [int](Invoke-JenkinsApi -Uri "$JenkinsUrl/job/$JobName/lastBuild/api/json").number
    } catch { return 0 }
}

function Invoke-JenkinsBuild {
    param([string]$JobName)
    Write-Host "Triggering $JobName ..."
    $headers = Get-JenkinsHeaders
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $crumb = Invoke-RestMethod -Uri "$JenkinsUrl/crumbIssuer/api/json" -Headers $headers -WebSession $session
    $headers[$crumb.crumbRequestField] = $crumb.crumb
    $before = Get-JenkinsLastBuildNumber -JobName $JobName
    Invoke-WebRequest -Uri "$JenkinsUrl/job/$JobName/build" -Method Post -Headers $headers -WebSession $session -UseBasicParsing | Out-Null
    $deadline = (Get-Date).AddSeconds(300)
    while ((Get-Date) -lt $deadline) {
        if ((Get-JenkinsLastBuildNumber -JobName $JobName) -gt $before) {
            return (Get-JenkinsLastBuildNumber -JobName $JobName)
        }
        Start-Sleep -Seconds 3
    }
    return ($before + 1)
}

function Wait-JenkinsBuildNumber {
    param([string]$JobName, [int]$BuildNumber, [int]$TimeoutSec = 3600)
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    $uri = "$JenkinsUrl/job/$JobName/$BuildNumber/api/json"
    while ((Get-Date) -lt $deadline) {
        $info = Invoke-JenkinsApi -Uri $uri
        if ($info.building) {
            Write-Host "  $JobName #$BuildNumber building..."
        } elseif ($info.result -eq 'SUCCESS') {
            Write-Host "  $JobName #$BuildNumber SUCCESS"
            return $true
        } elseif ($info.result) {
            Write-Host "  $JobName #$BuildNumber $($info.result)"
            return $false
        }
        Start-Sleep -Seconds 15
    }
    throw "Timeout waiting for $JobName #$BuildNumber"
}

function Wait-DownstreamBuild {
    param([string]$JobName, [int]$AfterNumber, [int]$TimeoutSec = 3600)
    Write-Host "Waiting for $JobName (after build #$AfterNumber)..."
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    while ((Get-Date) -lt $deadline) {
        if ((Get-JenkinsLastBuildNumber -JobName $JobName) -gt $AfterNumber) {
            $n = Get-JenkinsLastBuildNumber -JobName $JobName
            return (Wait-JenkinsBuildNumber -JobName $JobName -BuildNumber $n -TimeoutSec $TimeoutSec)
        }
        Start-Sleep -Seconds 5
    }
    throw "Timeout: $JobName was not triggered"
}

function Invoke-CiCdPipeline {
    if (-not $SkipGitHub) {
        Invoke-GitHubPush -CommitMessage $Message -TargetBranch $Branch
    }

    if ($SkipJenkins) { return }

    if (-not $JenkinsToken) {
        Write-Warning 'JENKINS_API_TOKEN not set - run: scripts\sumbungan.bat token'
        exit 1
    }

    if (-not (Wait-JenkinsHttp)) {
        throw "Jenkins not running at $JenkinsUrl - run: scripts\sumbungan.bat jenkins"
    }

    Wait-JenkinsJobsSeeded | Out-Null

    $j1b = Get-JenkinsLastBuildNumber -JobName 'job1'
    $j2b = Get-JenkinsLastBuildNumber -JobName 'job2'
    $j3b = Get-JenkinsLastBuildNumber -JobName 'job3'

    $b1 = Invoke-JenkinsBuild -JobName 'job1'
    if (-not (Wait-JenkinsBuildNumber -JobName 'job1' -BuildNumber $b1 -TimeoutSec $BuildTimeoutSec)) {
        throw "job1 failed - $JenkinsUrl/job/job1/$b1/console"
    }
    if (-not (Wait-DownstreamBuild -JobName 'job2' -AfterNumber $j2b -TimeoutSec $BuildTimeoutSec)) {
        throw "job2 failed - $JenkinsUrl/job/job2/lastBuild/console"
    }
    if (-not (Wait-DownstreamBuild -JobName 'job3' -AfterNumber $j3b -TimeoutSec $BuildTimeoutSec)) {
        throw "job3 failed - $JenkinsUrl/job/job3/lastBuild/console"
    }

    Write-Host ''
    Write-Host 'CI/CD complete'
    Write-Host "  Jenkins: $JenkinsUrl"
    Write-Host '  App:     http://localhost:8080'
}

function Invoke-CreateToken {
    param([string]$User = 'admin')

    if (-not (Wait-JenkinsHttp)) {
        throw "Jenkins not reachable at $JenkinsUrl - run: scripts\sumbungan.bat jenkins"
    }

    $sec = Read-Host "Jenkins password for user '$User'" -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec)
    $pass = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)

    $pair = "${User}:${pass}"
    $b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $crumb = Invoke-RestMethod -Uri "$JenkinsUrl/crumbIssuer/api/json" -Headers @{ Authorization = "Basic $b64" } -WebSession $session
    $headers = @{ Authorization = "Basic $b64" }
    $headers[$crumb.crumbRequestField] = $crumb.crumb

    $tokenUri = "$JenkinsUrl/user/$User/descriptorByName/jenkins.security.ApiTokenProperty/generateNewToken"
    $formBody = 'json=' + [uri]::EscapeDataString('{"newTokenName":"sumbungan-ci"}')

    $token = $null
    try {
        $resp = Invoke-RestMethod -Method Post -Uri $tokenUri -Headers $headers -WebSession $session `
            -ContentType 'application/x-www-form-urlencoded' -Body $formBody
        $token = $resp.data.tokenValue
    } catch {
        if (Get-Command curl.exe -ErrorAction SilentlyContinue) {
            $cj = curl.exe -fsS -u $pair "$JenkinsUrl/crumbIssuer/api/json" | ConvertFrom-Json
            $raw = curl.exe -fsS -u $pair -H "$($cj.crumbRequestField): $($cj.crumb)" -X POST `
                -H 'Content-Type: application/x-www-form-urlencoded' `
                --data-raw 'json={"newTokenName":"sumbungan-ci"}' $tokenUri
            $token = ($raw | ConvertFrom-Json).data.tokenValue
        }
    }

    if (-not $token) { throw 'Could not create token (wrong password or 403). Create manually in Jenkins UI.' }

    $envFile = Join-Path $Root '.env'
    $lines = if (Test-Path $envFile) { Get-Content $envFile } else { @() }
    $out = @()
    $tokDone = $false
    foreach ($line in $lines) {
        if ($line -match '^\s*JENKINS_API_TOKEN=') { $out += "JENKINS_API_TOKEN=$token"; $tokDone = $true }
        else { $out += $line }
    }
    if (-not $tokDone) { $out += "JENKINS_API_TOKEN=$token" }
    if (-not ($out | Where-Object { $_ -match '^\s*JENKINS_USER=' })) { $out += "JENKINS_USER=$User" }
    if (-not ($out | Where-Object { $_ -match '^\s*JENKINS_URL=' })) { $out += "JENKINS_URL=$JenkinsUrl" }
    Set-Content -Path $envFile -Value $out -Encoding UTF8
    Write-Host "Token saved to .env (shown once): $token"
}

function Show-Help {
    @"

Sumbungan automation (one script)

  scripts\sumbungan.bat setup          First-time: .env + Jenkins + app
  scripts\sumbungan.bat jenkins        Start Jenkins (job1/2/3 auto-created)
  scripts\sumbungan.bat app            Start app + MySQL + phpMyAdmin
  scripts\sumbungan.bat token          Save Jenkins API token to .env
  scripts\sumbungan.bat cicd [msg]     GitHub push + Jenkins pipeline
  scripts\sumbungan.bat cicd -SkipGitHub   Jenkins only

Jenkins jobs are NOT created manually. They seed from jenkins/jobs/ on container start.

URLs: App http://localhost:8080 | Jenkins http://localhost:9090 | phpMyAdmin http://localhost:8082

"@
}

# --- commands ---

switch ($Command) {
    'help' { Show-Help }
    'setup' {
        Write-Title 'Setup'
        foreach ($cmd in @('docker', 'git')) {
            if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) { throw "Missing: $cmd" }
        }
        if (-not (Test-Path '.env')) {
            Copy-Item '.env.example' '.env'
            Write-Host 'Created .env - edit GITHUB_TOKEN and JENKINS_API_TOKEN'
        }
        Start-JenkinsStack
        Start-AppStack
        Write-Host ''
        Write-Host 'Setup done. Next: scripts\sumbungan.bat token'
        Write-Host 'Then:     scripts\sumbungan.bat cicd'
    }
    'jenkins' {
        Write-Title 'Start Jenkins'
        Start-JenkinsStack
        Write-Host "Jenkins: $JenkinsUrl"
    }
    'app' {
        Write-Title 'Start App'
        Start-AppStack
        Write-Host 'App: http://localhost:8080'
    }
    'token' {
        Write-Title 'Jenkins API Token'
        Invoke-CreateToken
    }
    'cicd' {
        Write-Title 'CI/CD'
        Invoke-CiCdPipeline
    }
}
