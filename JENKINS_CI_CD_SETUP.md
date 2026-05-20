# Sumbungan Jenkins CI/CD Setup

Mirrors the **hrms-main** reference: separate Jenkins stack, three chained jobs, GitHub auto-push, and Docker Compose with a CI override.

## Architecture

```
GitHub push (scripts/github-auto-push.*)
        │
        ▼
Jenkins :9090 (docker-compose.jenkins.yml → sumbungan_jenkins)
        │
   job1 ──► PHP lint + docker build (sumbungan-app)
        │
   job2 ──► DB ping + smoke test (docker-compose.ci.yml, port 8888)
        │
   job3 ──► docker compose up (app on :8080, does not stop Jenkins)
```

## Key files

| File | Purpose |
| ---- | ------- |
| `docker-compose.yml` | App (`web`), MySQL (`db`), phpMyAdmin |
| `docker-compose.jenkins.yml` | Jenkins with Docker socket + job seed |
| `docker-compose.ci.yml` | CI port override (`8888`, `*_ci` containers) |
| `jenkins/jobs/job{1,2,3}/config.xml` | Freestyle job definitions |
| `scripts/ci-cd-auto.ps1` | Full GitHub + Jenkins automation |
| `scripts/jenkins-checkout.sh` | Clone repo into Jenkins workspace |
| `scripts/jenkins-deploy-app.sh` | Deploy app stack (job3) |
| `.env` | `GITHUB_TOKEN`, `JENKINS_API_TOKEN`, `GITHUB_REPO` |

## Environment (.env)

Copy `.env.example` → `.env`:

- `GITHUB_REPO=barjosec-cpu/Sumbungan`
- `GITHUB_TOKEN` — PAT with `repo` scope
- `JENKINS_URL=http://localhost:9090`
- `JENKINS_USER=admin` / `JENKINS_API_TOKEN` (API token from Jenkins → admin → Configure → API Token)
- `COMPOSE_PROJECT_NAME=sumbungan`

## Jenkins jobs

| Job | Action |
| --- | ------ |
| job1 | Checkout, PHP lint, `docker build` → triggers job2 |
| job2 | Start DB, connectivity test, CI smoke on `sumbungan_web_ci:8888` → triggers job3 |
| job3 | `docker compose up` production stack on 8080/8081/3307 |

## GitHub webhook (optional)

1. Repo → Settings → Webhooks → Add webhook
2. Payload URL: `http://<your-host>:9090/github-webhook/`
3. Events: Push

## Troubleshooting

**Jenkins cannot run docker** — Docker Desktop must be running; Jenkins image includes Docker CLI and socket mount.

**Smoke test fails** — Free ports 8888/8080; run `docker compose -p sumbungan_ci down -v`.

**job1 clone fails** — Set `GITHUB_TOKEN` in `.env` for private repos.

**App DB errors** — `config/database.php` reads `DB_*` env vars from `docker-compose.yml`.
