#!/bin/bash
# Promote staging → production on the Uwezo server (single-host deployment)
set -euo pipefail

ROOT="/home/administrator"
LMS="$ROOT/projects/lms_source_code/uwezo-loan-tracking"
LOANS_STAGING="$ROOT/Loan_Application"
LOANS_PROD="$ROOT/loans"

echo "=== 1. LMS: compile frontend assets (host npm — not available in container) ==="
cd "$LMS"
npm ci
npm run build

echo "=== 2. LMS: migrate & optimize ==="
docker exec -u www-data uwezo-lms-app php artisan migrate --force
docker exec -u www-data uwezo-lms-app php artisan optimize:clear
docker exec -u www-data uwezo-lms-app php artisan config:cache
docker exec -u www-data uwezo-lms-app php artisan route:cache
docker exec -u www-data uwezo-lms-app php artisan view:cache
docker exec -u www-data uwezo-lms-app php artisan horizon:terminate || true
docker exec uwezo-lms-app chown -R www-data:www-data storage bootstrap/cache

echo "=== 3. Sync Drupal uploaded files staging → prod ==="
docker run --rm \
  -v uwezo_drupal_files:/from:ro \
  -v uwezo-prod_prod_drupal_files:/to \
  alpine:3.20 sh -c 'cp -a /from/. /to/'
docker run --rm \
  -v uwezo_drupal_private:/from:ro \
  -v uwezo-prod_prod_drupal_private:/to \
  alpine:3.20 sh -c 'cp -a /from/. /to/'

echo "=== 4. Loans prod: build images from staging source ==="
cd "$LOANS_PROD"
docker compose build --no-cache prod-admin prod-web

echo "=== 5. Loans prod: recreate containers (shared staging DB) ==="
docker compose up -d --force-recreate prod-admin prod-web

echo "=== 6. Loans prod: post-deploy ==="
docker exec uwezo-prod-admin /var/www/html/vendor/bin/drush deploy -y || true
docker exec uwezo-prod-web sh -c 'cd /app/web && npx prisma@6.11.1 migrate deploy' || true

echo "=== 7. Stop unused isolated prod DB (staging DB is canonical) ==="
docker stop uwezo-prod-db 2>/dev/null || true

echo "=== 8. Smoke tests ==="
curl -sf -o /dev/null -w "lms.uwezo.go.ke HTTP %{http_code}\n" -k --max-time 30 https://127.0.0.1/ -H 'Host: lms.uwezo.go.ke' || echo "LMS check failed"
curl -sf -o /dev/null -w "loans.uwezo.go.ke HTTP %{http_code}\n" -k --max-time 30 https://127.0.0.1/ -H 'Host: loans.uwezo.go.ke' || echo "Loans web check failed"
curl -sf -o /dev/null -w "loans-admin.uwezo.go.ke HTTP %{http_code}\n" -k --max-time 30 https://127.0.0.1/ -H 'Host: loans-admin.uwezo.go.ke' || echo "Loans admin check failed"

echo "=== Done ==="
