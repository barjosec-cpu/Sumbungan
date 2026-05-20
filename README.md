# Sumbungan

Barangay Desk Integration System — PHP/MySQL web app with Docker and automated Jenkins CI/CD (GitHub push → build → test → deploy).

---

## Table of Contents

1. [Features](#features)
2. [Tech stack](#tech-stack)
3. [Project structure](#project-structure)
4. [One script (all automation)](#one-script-all-automation)
5. [Quick start](#quick-start)
6. [CI/CD architecture](#cicd-architecture)
7. [Docker](#docker)
8. [XAMPP development](#xampp-development)
9. [Database](#database)
10. [Environment variables](#environment-variables)
11. [REST API](#rest-api)
12. [Troubleshooting](#troubleshooting)
13. [License](#license)

---

## Features

- Barangay residents portal
- Admin dashboard
- Complaint submission and tracking
- File uploads
- REST API under `/api`

## Tech stack

- PHP 8.1, Apache (`mod_rewrite`)
- MySQL 8.0
- Docker / Docker Compose
- Jenkins (job1 → job2 → job3)
- GitHub auto-push

## Project structure

```
Sumbungan/
├── admin/ dashboard/ api/ assets/ config/ includes/ public/
├── jenkins/jobs/job{1,2,3}/config.xml   # auto-seeded into Jenkins on boot
├── scripts/
│   ├── sumbungan.bat / sumbungan.ps1    # ONE entry script (all commands)
│   ├── jenkins-checkout.sh              # used inside Jenkins only
│   └── jenkins-deploy-app.sh            # used inside Jenkins only
├── docker/                              # Apache + Jenkins image
├── docker-compose.yml
├── docker-compose.jenkins.yml
├── docker-compose.ci.yml
├── Dockerfile
├── Jenkinsfile
├── .env.example
└── sumbungan_db.sql
```

---

## One script (all automation)

Use **`scripts\sumbungan.bat`** for everything. No separate setup/token/cicd scripts.

| Command | What it does |
| ------- | -------------- |
| `scripts\sumbungan.bat setup` | Create `.env`, start Jenkins + app |
| `scripts\sumbungan.bat jenkins` | Start Jenkins only |
| `scripts\sumbungan.bat app` | Start app + MySQL + phpMyAdmin |
| `scripts\sumbungan.bat token` | Create Jenkins API token → `.env` |
| `scripts\sumbungan.bat cicd "message"` | GitHub push + Jenkins job1→job2→job3 |
| `scripts\sumbungan.bat cicd -SkipGitHub` | Jenkins pipeline only |
| `scripts\sumbungan.bat help` | Show commands |

**Jenkins jobs are never created manually.** On each Jenkins container start, `docker/jenkins/init.groovy.d/01-seed-jobs.groovy` installs **job1**, **job2**, and **job3** from `jenkins/jobs/`.

---

## Quick start

```bat
cd C:\xampp\htdocs\Sumbungan
copy .env.example .env
REM Edit: GITHUB_TOKEN, GITHUB_REPO=barjosec-cpu/Sumbungan

scripts\sumbungan.bat setup
scripts\sumbungan.bat token
scripts\sumbungan.bat cicd "CI/CD automated push"
```

| Service | URL |
| ------- | --- |
| App | http://localhost:8080 |
| Jenkins | http://localhost:9090 |
| phpMyAdmin (Docker) | http://localhost:8082 |
| MySQL (host) | `localhost:3307` (root / root) |

---

## CI/CD architecture

```
GitHub push (sumbungan.bat cicd)
        |
        v
Jenkins :9090 (sumbungan_jenkins)
        |
   job1 -- PHP lint + docker build (sumbungan-app)
        |
   job2 -- DB test + smoke (port 8888, docker-compose.ci.yml)
        |
   job3 -- docker compose up (app :8080; Jenkins stays up)
```

### Jenkins jobs (auto-created)

| Job | Action |
| --- | ------ |
| job1 | Checkout, PHP lint, `docker build` → triggers job2 |
| job2 | DB health, smoke test on `sumbungan_web_ci` → triggers job3 |
| job3 | Deploy `web` + `db` (+ phpMyAdmin on :8082) |

### `.env` for CI/CD

Copy `.env.example` → `.env`:

- `GITHUB_REPO=barjosec-cpu/Sumbungan`
- `GITHUB_TOKEN` — PAT with `repo` scope
- `JENKINS_URL=http://localhost:9090`
- `JENKINS_USER=admin`
- `JENKINS_API_TOKEN` — from `scripts\sumbungan.bat token` or Jenkins UI

### GitHub webhook (optional)

Repo → Settings → Webhooks → `http://<host>:9090/github-webhook/` → Push events.

---

## Docker

### App stack

```bat
scripts\sumbungan.bat app
```

Or: `docker compose up -d --build`

| Container | Port |
| --------- | ---- |
| `sumbungan_web` | 8080 |
| `sumbungan_db` | 3307 |
| `sumbungan_pma` | 8082 |
| `sumbungan_jenkins` | 9090 |

Stop app: `docker compose down`  
Reset DB: `docker compose down -v && docker compose up -d --build`

### Jenkins stack (separate compose file)

```bat
scripts\sumbungan.bat jenkins
```

Jobs seed automatically; no `jenkins-deploy-jobs` step.

---

## XAMPP development

1. Place repo under `C:\xampp\htdocs\Sumbungan`
2. Start Apache + MySQL in XAMPP
3. Import `sumbungan_db.sql` via http://localhost/phpmyadmin
4. Open http://localhost/Sumbungan/

`config/database.php` uses `getenv('DB_*')` in Docker and XAMPP defaults (`sumbungan_db`, empty password) locally.

---

## Database

| Environment | Database | Host | Port | User / pass |
| ----------- | -------- | ---- | ---- | ----------- |
| Docker | `sumbungan` | `db` / `127.0.0.1` | 3307 | `root` / `root` |
| XAMPP | `sumbungan_db` | `localhost` | 3306 | `root` / *(empty)* |

```bash
mysql -h 127.0.0.1 -P 3307 -u root -proot sumbungan
```

---

## Environment variables

**App (docker-compose.yml → `web`):** `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

**MySQL container:** `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

**CI/CD (.env):** `GITHUB_TOKEN`, `GITHUB_REPO`, `JENKINS_*`, `COMPOSE_PROJECT_NAME`

---

## REST API

Base path: `/api/` (e.g. `http://localhost:8080/api/` in Docker).

Response envelope:

```json
{ "ok": true, "data": {}, "error": null }
```

Use session cookies (`PHPSESSID`) with `credentials: 'same-origin'`.

### Auth — `POST /api/auth.php`

- **Login:** `email`, `password` → `data.user`, `data.role`
- **Register:** `full_name`, `email`, `password` (min 8), optional `address`
- **Logout:** `action=logout`

### Upload — `POST /api/upload.php`

- `multipart/form-data` field `file` (JPEG/PNG/WebP/GIF, max 5 MB)
- Returns `data.path`, `data.url`

### Complaints — `GET|POST|PATCH /api/complaints.php`

- **List** `GET` — complainant: own; admin: all + filters `status`, `type`, `q`
- **Detail** `GET ?id=BRY-4021` — includes `timeline`
- **Create** `POST` — `type`, `location`, `description`, optional `photo_path` or multipart `photo`
- **Update** `PATCH` — admin only: `status`, `timeline_label`, `timeline_details`

### Analytics — `GET /api/analytics.php` (admin)

Query `metric`: `summary` | `daily` | `by_type` | `by_status` | `top_locations` | `recent`

### Users — `GET|POST /api/users.php`

- `GET` — admin roster
- `POST ?action=profile` — update own profile (+ optional `profile_pic`)

### Export — `GET /api/export.php` (admin)

- `?type=cases` or `?type=users` → CSV download

### Notifications — `GET|POST /api/notifications.php`

- `GET` — list for current user
- `POST ?action=read` — mark read (optional `id`)

### HTTP errors

| Code | Meaning |
| ---- | ------- |
| 400 | Invalid input |
| 401 | Not logged in |
| 403 | Forbidden |
| 404 | Not found |
| 409 | Conflict |
| 500 | Server error |

---

## Troubleshooting

| Problem | Fix |
| ------- | --- |
| PowerShell scripts disabled | Use `scripts\sumbungan.bat` (includes `-ExecutionPolicy Bypass`) |
| Jenkins not on :9090 | `scripts\sumbungan.bat jenkins` |
| Jobs missing in Jenkins | Restart Jenkins; jobs auto-seed from `jenkins/jobs/` |
| job1 `pipefail` error | Shell scripts use LF; jobs run via `sed` strip of CRLF |
| Port 8082 busy | Old `jenkins` container may use 8081; phpMyAdmin uses **8082** |
| Port 8080 busy | Change mapping in `docker-compose.yml` |
| `docker: not found` in Jenkins | Docker Desktop must be running |
| GitHub push fails | Check `GITHUB_TOKEN` and `GITHUB_REPO` in `.env` |
| DB login fails in Docker | `config/database.php` must read env vars (already configured) |
| CI smoke fails | `docker compose -p sumbungan_ci down -v` |

---

## License

Proprietary — Sumbungan team.
scripts\sumbungan.bat cicd "CI/CD automated push"