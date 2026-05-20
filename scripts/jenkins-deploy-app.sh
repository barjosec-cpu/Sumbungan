#!/usr/bin/env bash
# Deploy Sumbungan app only (never touches sumbungan_jenkins)
set -eux

COMPOSE_PROJECT="${COMPOSE_PROJECT_NAME:-sumbungan}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.yml}"

echo "Job3 - Sumbungan Deploy"
mkdir -p backups

# Stop only app services - NEVER use --remove-orphans (it kills sumbungan_jenkins on shared network)
docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" stop web db phpmyadmin 2>/dev/null || true
docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" rm -f web db phpmyadmin 2>/dev/null || true

docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" up -d --build web db phpmyadmin

echo "Waiting for database..."
for i in $(seq 1 30); do
  if docker exec sumbungan_db mysqladmin ping -h localhost -uroot -p"${MYSQL_ROOT_PASSWORD:-root}" 2>/dev/null; then
    echo "DB healthy"
    break
  fi
  sleep 5
done

echo "Waiting for web..."
for i in $(seq 1 24); do
  if docker exec sumbungan_web curl -fsS -o /dev/null http://localhost/ 2>/dev/null; then
    echo "Web healthy"
    break
  fi
  sleep 5
done

docker ps --filter name=sumbungan_web --filter name=sumbungan_db --filter name=sumbungan_pma
echo "Deploy complete"
echo "  App:        http://localhost:8080"
echo "  PHPMyAdmin: http://localhost:8081"
echo "  Jenkins:    http://localhost:9090 (unchanged)"
