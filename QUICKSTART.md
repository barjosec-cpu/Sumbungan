# Sumbungan CI/CD — Quick Start

Same automation pattern as **hrms-main**: GitHub auto-push → Jenkins **job1** (build) → **job2** (test) → **job3** (deploy).

## One-time setup

```bat
cd C:\xampp\htdocs\Sumbungan
copy .env.example .env
REM Edit .env: GITHUB_TOKEN, GITHUB_REPO=barjosec-cpu/Sumbungan, JENKINS_API_TOKEN

scripts\complete-setup.bat
```

| Service | URL |
| ------- | --- |
| Jenkins | http://localhost:9090 |
| App | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |

Create Jenkins API token (if needed):

```bat
scripts\jenkins-create-api-token.bat
```

> On Windows, use the `.bat` wrappers if PowerShell reports `running scripts is disabled`.

## Run full pipeline (GitHub + Jenkins)

```powershell
scripts\ci-cd-auto.bat "CI/CD automated push"
```

Or Jenkins only:

```powershell
scripts\ci-cd-auto.ps1 -SkipGitHub
```

## Manual commands

```powershell
# Start Jenkins only
powershell -File scripts\start-jenkins.ps1

# Deploy job configs into Jenkins
powershell -File scripts\jenkins-deploy-jobs.ps1 -Restart

# App stack
docker compose up -d --build
```

See **JENKINS_CI_CD_SETUP.md** for architecture and troubleshooting.
