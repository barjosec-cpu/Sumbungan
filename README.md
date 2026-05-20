# Sumbungan

Barangay Desk Integration System — a PHP/MySQL web application for managing barangay (community) services, complaints, and reports, with Docker and Jenkins CI/CD support.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Quick Start (Docker)](#quick-start-docker)
- [Local Development (XAMPP)](#local-development-xampp)
- [Database](#database)
- [CI/CD with Jenkins](#cicd-with-jenkins)
- [Environment & configuration](#environment--configuration)
- [Localhost URLs (quick reference)](#localhost-urls-quick-reference)
- [Docker services](#docker-services)
- [Setup status](#setup-status)
- [Environment Variables](#environment-variables)
- [Troubleshooting](#troubleshooting)

---

## Features

- Barangay residents portal
- Admin dashboard for staff
- Complaint / request submission and tracking
- File uploads
- REST-style API endpoints under `/api`

## Tech Stack

- **Backend:** PHP 8.1, Apache (`mod_rewrite`)
- **Database:** MySQL 8.0
- **Frontend:** HTML / CSS / JS in `public/` and `assets/`
- **Containerization:** Docker, Docker Compose
- **CI/CD:** Jenkins (pipeline-as-code via `Jenkinsfile`)

## Project Structure

```
Sumbungan/
├── admin/              # Admin dashboard pages
├── api/                # JSON API endpoints
├── assets/             # CSS, JS, images, uploads
├── config/             # App configuration
├── dashboard/          # User dashboard
├── docs/               # Project documentation
├── includes/           # Shared PHP includes (bootstrap, helpers)
├── public/             # Public entry pages
├── sql/                # SQL helper scripts
├── sumbungan_db.sql    # Database schema + seed data
├── index.php           # App entry point
├── docker/                 # Apache/PHP config + Jenkins image
├── jenkins/jobs/           # job1 build, job2 test, job3 deploy
├── scripts/                # CI/CD automation (GitHub + Jenkins)
├── Dockerfile              # PHP + Apache image
├── docker-compose.yml      # App + MySQL + phpMyAdmin
├── docker-compose.jenkins.yml
├── docker-compose.ci.yml
├── Jenkinsfile             # Orchestrates job1 → job2 → job3
├── .env.example            # GitHub + Jenkins tokens
├── QUICKSTART.md           # CI/CD quick start
└── .htaccess               # Apache rewrite rules
```

## Quick Start (Docker)

Prereqs: Docker Desktop running.

```bash
# 1. Clone
git clone https://github.com/barjosec-cpu/Sumbungan.git
cd Sumbungan

# 2. Build and start the stack
docker compose up -d --build

# 3. Open the app
#    http://localhost:8080
```

Services started:

| Service       | Container         | Host port |
| ------------- | ----------------- | --------- |
| App (Apache)  | `sumbungan_web`   | `8080`    |
| MySQL 8       | `sumbungan_db`    | `3307`    |
| phpMyAdmin    | `sumbungan_pma`   | `8081`    |

Stop:

```bash
docker compose down
```

Stop and wipe DB volume:

```bash
docker compose down -v
```

## Local Development (XAMPP)

1. Place this repository under `C:\xampp\htdocs\Sumbungan` (Windows) or your `htdocs` directory.
2. Start Apache and MySQL from the XAMPP control panel.
3. Import `sumbungan_db.sql` via phpMyAdmin (`http://localhost/phpmyadmin`).
4. Open `http://localhost/Sumbungan`.

## Database

| Environment | Database name | Host | Port | User / password |
| ----------- | ------------- | ---- | ---- | --------------- |
| Docker | `sumbungan` | `db` (in container) / `127.0.0.1` (from host) | `3307` | `root` / `root` |
| XAMPP | `sumbungan_db` | `localhost` | `3306` | `root` / *(empty)* |

- Schema and seed data: `sumbungan_db.sql` (auto-imported by Docker on first start into `sumbungan`)
- Docker app user (optional): `sumbungan` / `sumbungan123`

Connect from the host (Docker):

```bash
mysql -h 127.0.0.1 -P 3307 -u root -proot sumbungan
```

## CI/CD with Jenkins (same as hrms-main reference)

Automated **GitHub push** + **Jenkins** (3 jobs) + **Docker** — see `QUICKSTART.md` and `JENKINS_CI_CD_SETUP.md`.

### Quick start

```powershell
copy .env.example .env
# Set GITHUB_TOKEN, JENKINS_API_TOKEN, GITHUB_REPO=barjosec-cpu/Sumbungan

scripts\complete-setup.bat
scripts\ci-cd-auto.bat "CI/CD automated push"
```

### Pipeline flow

| Step | What runs |
| ---- | --------- |
| GitHub | `scripts/github-auto-push.*` commits and pushes to `GITHUB_REPO` |
| job1 | PHP lint + `docker build` → image `sumbungan-app` |
| job2 | DB health + smoke test via `docker-compose.ci.yml` (port 8888) |
| job3 | `docker compose up` app on http://localhost:8080 |

Jenkins runs in its **own** compose file (port **9090**) so deploys never stop the CI server:

```powershell
docker compose -f docker-compose.jenkins.yml up -d --build
```

`Jenkinsfile` can orchestrate job1 → job2 → job3 for a multibranch/pipeline job.

## Environment & configuration

Copy `.env.example` to `.env` for CI/CD tokens (GitHub, Jenkins). Database settings:

| File | Purpose |
| ---- | ------- |
| `config/database.php` | Reads `DB_*` env vars (Docker) or XAMPP defaults |
| `docker-compose.yml` | Env vars for the `web` container |
| `.env` | `GITHUB_TOKEN`, `JENKINS_API_TOKEN`, `GITHUB_REPO`, MySQL passwords |

### XAMPP defaults (`config/database.php`)

| Setting | Value |
| ------- | ----- |
| Host | `localhost` |
| Database | `sumbungan_db` |
| User | `root` |
| Password | *(empty)* |

### Docker defaults (`docker-compose.yml` → app container)

| Setting | Value |
| ------- | ----- |
| Host | `db` (MySQL service name on the Docker network) |
| Database | `sumbungan` |
| User | `root` |
| Password | `root` |

> **Important:** Docker MySQL creates the database `sumbungan`. XAMPP typically uses `sumbungan_db` (from `sumbungan_db.sql`). These are two separate databases on two separate MySQL instances.

---

## Localhost URLs (quick reference)

```
┌──────────────────────────────────────────────────────────────────┐
│  Sumbungan App (Docker)     →  http://localhost:8080             │
│  Sumbungan App (XAMPP)      →  http://localhost/Sumbungan/       │
│  Jenkins UI                 →  http://localhost:9090             │
│  Jenkins jobs               →  job1, job2, job3                    │
│  phpMyAdmin (Docker)        →  http://localhost:8081             │
│  phpMyAdmin (XAMPP)         →  http://localhost/phpmyadmin       │
│  MySQL via Docker           →  localhost:3307  (root / root)     │
│  MySQL via XAMPP            →  localhost:3306  (root / no pass)  │
│  GitHub repo                →  https://github.com/barjosec-cpu/Sumbungan │
└──────────────────────────────────────────────────────────────────┘
```

| URL | What it serves |
| --- | -------------- |
| http://localhost:8080 | App in Docker (`sumbungan_web`) |
| http://localhost/Sumbungan/ | App via XAMPP Apache |
| http://localhost:9090 | Jenkins (`sumbungan_jenkins`) |
| http://localhost:9090/job/job1/ | Build job |
| http://localhost:8081 | phpMyAdmin (Docker stack) |
| http://localhost/phpmyadmin | XAMPP database admin |

---

## Docker services

### Running containers

| Container | Image | Host ports |
| --------- | ----- | ---------- |
| `sumbungan_web` | `sumbungan-app` | `8080` → 80 |
| `sumbungan_db` | `mysql:8.0` | `3307` → 3306 |
| `sumbungan_pma` | `phpmyadmin:5` | `8081` → 80 |
| `sumbungan_jenkins` | `sumbungan-jenkins:lts` | `9090` → 8080 |

Start the app stack:

```bash
docker compose up -d --build
```

Start Jenkins (separate stack):

```powershell
scripts\start-jenkins.bat
```

### Connect to Docker MySQL from the host

```bash
mysql -h 127.0.0.1 -P 3307 -u root -proot sumbungan
```

Tables created on first run: `users`, `complaints`, `barangay_settings`, `complaint_timeline`, `notifications`.

### Jenkins built images

After job1:

```
sumbungan-app:latest
sumbungan-app:<BUILD_NUMBER>
```

---

## Setup status

Use this checklist to confirm everything is working.

| Item | Status | Notes |
| ---- | ------ | ----- |
| GitHub repo | OK | https://github.com/barjosec-cpu/Sumbungan |
| `Dockerfile` | OK | PHP 8.1 + Apache |
| `docker-compose.yml` | OK | App + MySQL on ports 8080 / 3307 |
| Jenkins + Docker CI/CD | Setup | job1/job2/job3 at http://localhost:9090 (see QUICKSTART.md) |
| XAMPP app (`/Sumbungan/`) | OK | Uses `sumbungan_db` on port 3306 |
| Docker app (`:8080`) | Check DB | Pages load; login/dashboard need `config/database.php` aligned with Docker (`db` / `sumbungan` / `root` / `root`) |

### Config alignment (Docker vs XAMPP)

| Setting | XAMPP (`config/database.php`) | Docker (`docker-compose.yml`) |
| ------- | ------------------------------ | ----------------------------- |
| Host | `localhost` | `db` |
| Database | `sumbungan_db` | `sumbungan` |
| User | `root` | `root` |
| Password | *(empty)* | `root` |

For Docker, `config/database.php` must use the Docker values (or read from environment variables). For XAMPP, keep the XAMPP defaults above.

`config/database.php` already uses `getenv('DB_*')` so Docker picks up `docker-compose.yml` values and XAMPP keeps the `sumbungan_db` default without a `.env` file.

---

## Environment Variables

Set in `docker-compose.yml` for the `web` service (read by `config/database.php` in Docker):

| Variable | Docker value | Purpose |
| -------- | ------------ | ------- |
| `DB_HOST` | `db` | MySQL host (Docker service name) |
| `DB_USER` | `root` | MySQL user |
| `DB_PASSWORD` | `root` | MySQL password |
| `DB_NAME` | `sumbungan` | Database name |

MySQL service env (`db` container):

| Variable | Value |
| -------- | ----- |
| `MYSQL_ROOT_PASSWORD` | `root` |
| `MYSQL_DATABASE` | `sumbungan` |
| `MYSQL_USER` | `sumbungan` |
| `MYSQL_PASSWORD` | `sumbungan123` |

## Troubleshooting

**Port 8080 already in use** — change the host port in `docker-compose.yml`, e.g. `"8090:80"`.

**Database changes not appearing** — `sumbungan_db.sql` only runs on a fresh volume. Reset with:

```bash
docker compose down -v && docker compose up -d --build
```

**Jenkins build fails with `docker: not found`** — use `docker-compose.jenkins.yml` (includes Docker CLI). Start with `scripts/start-jenkins.ps1`.

**Jenkins `permission denied` on docker.sock** — the custom Jenkins entrypoint adds the `jenkins` user to the socket group (same as hrms-main).

**Docker app loads but login/API fails** — ensure `config/database.php` uses env vars; restart: `docker compose restart web`.

**`ERR_CONNECTION_REFUSED` on port 9090** — run `docker compose -f docker-compose.jenkins.yml up -d`.

**CI smoke test fails** — free port 8888; run `docker compose -p sumbungan_ci down -v`.

**Port 8080 wrong service** — check `docker ps` for `sumbungan_web`.

---

## License

Proprietary — internal project for the Sumbungan team.
