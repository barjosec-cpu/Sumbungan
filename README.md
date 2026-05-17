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
├── Dockerfile          # PHP + Apache image
├── docker-compose.yml  # App + MySQL stack
├── Jenkinsfile         # CI pipeline
└── .htaccess           # Apache rewrite rules
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

| Service       | Container        | Host port |
| ------------- | ---------------- | --------- |
| App (Apache)  | `sumbungan_app`  | `8080`    |
| MySQL 8       | `sumbungan_db`   | `3307`    |

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

- Default database: `sumbungan`
- Schema and seed data: `sumbungan_db.sql` (auto-imported by Docker on first start)
- Default Docker credentials:
  - `root` / `root`
  - `sumbungan` / `sumbungan123`

Connect from the host:

```bash
mysql -h 127.0.0.1 -P 3307 -u root -proot sumbungan
```

## CI/CD with Jenkins

This repository ships with a `Jenkinsfile` that runs a 5-stage pipeline:

1. **Checkout** — pull source from Git
2. **PHP Syntax Check** — `php -l` on every `.php` file (using a throwaway PHP container)
3. **Build Docker Image** — `docker build -t sumbungan:<build> -t sumbungan:latest .`
4. **Smoke Test** — start the built image, hit `http://localhost/` from inside the container, fail on non-2xx
5. **Build Info** — print image metadata

### Requirements on the Jenkins host

The Jenkins agent must have access to the Docker daemon. The recommended setup is the official `jenkins/jenkins:lts` image with the host Docker socket mounted **and** the Docker CLI installed inside.

```bash
docker run -d --name jenkins --restart unless-stopped ^
  -p 8081:8080 -p 50000:50000 ^
  -u root ^
  -v jenkins_home:/var/jenkins_home ^
  -v /var/run/docker.sock:/var/run/docker.sock ^
  jenkins/jenkins:lts
```

After the container is up, install the Docker CLI inside it:

```bash
docker exec -u 0 jenkins bash -c "apt-get update && apt-get install -y docker.io"
```

### Creating the pipeline job

1. Go to `http://localhost:8081`
2. **New Item** → name `Sumbungan` → **Pipeline** → OK
3. Under **Pipeline**:
   - Definition: *Pipeline script from SCM*
   - SCM: *Git*
   - Repository URL: `https://github.com/barjosec-cpu/Sumbungan.git`
   - Branch: `*/main`
   - Script Path: `Jenkinsfile`
4. **Save** → **Build Now**

A successful build produces local images:

```
sumbungan:latest
sumbungan:<BUILD_NUMBER>
```

## Environment Variables

The app reads these env vars (with sensible defaults for local dev):

| Variable      | Default       | Purpose                |
| ------------- | ------------- | ---------------------- |
| `DB_HOST`     | `db`          | MySQL host             |
| `DB_USER`     | `root`        | MySQL user             |
| `DB_PASSWORD` | `root`        | MySQL password         |
| `DB_NAME`     | `sumbungan`   | Database name          |

## Troubleshooting

**Port 8080 already in use** — change the host port in `docker-compose.yml`, e.g. `"8090:80"`.

**Database changes not appearing** — `sumbungan_db.sql` only runs on a fresh volume. Reset with:

```bash
docker compose down -v && docker compose up -d --build
```

**Jenkins build fails with `docker: not found`** — install the Docker CLI inside the Jenkins container (see [Requirements on the Jenkins host](#requirements-on-the-jenkins-host)).

**Jenkins build fails with `permission denied` on `/var/run/docker.sock`** — start the Jenkins container with `-u root` or add the `jenkins` user to the host's `docker` group (matching GID).

---

## License

Proprietary — internal project for the Sumbungan team.
