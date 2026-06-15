# Production deployment: staging → production

This document describes how to promote **lms-staging**, **loans-staging**, and **loans-staging-admin** to their production domains on the Uwezo server.

| Staging | Production | Source code | Database |
|---------|------------|-------------|----------|
| `lms-staging.uwezo.go.ke` | `lms.uwezo.go.ke` | `/home/administrator/projects/lms_source_code/uwezo-loan-tracking` | `uwezo-lms-db` / `uwezo_fund03` (shared) |
| `loans-staging.uwezo.go.ke` | `loans.uwezo.go.ke` | `/home/administrator/Loan_Application/web` | `uwezo-db-1` / `web` (shared) |
| `loans-staging-admin.uwezo.go.ke` | `loans-admin.uwezo.go.ke` | `/home/administrator/Loan_Application/admin` | `uwezo-db-1` / `drupal` (shared) |

Production and staging share the **same MariaDB instances**. Production containers use different hostnames and TLS certificates but point at the staging databases by design.

---

## Architecture (single server)

```
nginx (uwezo-nginx-1)
├── lms.uwezo.go.ke          → uwezo-lms-app:8000
├── lms-staging.uwezo.go.ke  → uwezo-lms-staging:8000 (+ Vite :5173)
├── loans.uwezo.go.ke        → uwezo-prod-web:3000
├── loans-staging.uwezo.go.ke → uwezo-web-1:3000
├── loans-admin.uwezo.go.ke  → uwezo-prod-admin (PHP-FPM)
└── loans-staging-admin…     → uwezo-admin-1 (PHP-FPM)

Databases
├── uwezo-lms-db  (MySQL)  → uwezo_fund03   — LMS staging + prod
└── uwezo-db-1    (MariaDB) → drupal, web    — Loans staging + prod
```

Docker Compose for production loans: `/home/administrator/loans/docker-compose.yml`  
Automated script: `/home/administrator/projects/lms_source_code/uwezo-loan-tracking/scripts/promote-staging-to-production.sh`

---

## Prerequisites

- SSH access to the server as `administrator`
- Docker and Docker Compose installed
- Node.js/npm on the **host** (LMS frontend is built on the host; npm is not in the LMS container)
- Source trees up to date with the changes you want in production

---

## Step 1 — LMS (`lms.uwezo.go.ke`)

Both `lms-staging` and `lms.uwezo.go.ke` run from the **same codebase** mounted into `uwezo-lms-app`. Deploying LMS is compile + migrate + cache; no separate prod container.

### 1.1 Set production URL

In `/home/administrator/projects/lms_source_code/uwezo-loan-tracking/.env`:

```env
APP_URL=https://lms.uwezo.go.ke
APP_ENV=production
APP_DEBUG=false
```

Database (already shared with staging):

```env
DB_HOST=uwezo-lms-db
DB_DATABASE=uwezo_fund03
```

### 1.2 Compile frontend assets (host)

```bash
cd /home/administrator/projects/lms_source_code/uwezo-loan-tracking
npm ci
npm run build
```

Output goes to `public/build/` (served by Laravel via `manifest.json`).

### 1.3 Run migrations and cache

```bash
docker exec uwezo-lms-app php artisan migrate --force
docker exec uwezo-lms-app php artisan optimize:clear
docker exec uwezo-lms-app php artisan config:cache
docker exec uwezo-lms-app php artisan route:cache
docker exec uwezo-lms-app php artisan view:cache
docker exec uwezo-lms-app php artisan horizon:terminate || true
```

### 1.4 Verify LMS

```bash
curl -sk -o /dev/null -w "HTTP %{http_code}\n" https://127.0.0.1/ -H 'Host: lms.uwezo.go.ke'
curl -sk -o /dev/null -w "manifest %{http_code}\n" https://127.0.0.1/build/manifest.json -H 'Host: lms.uwezo.go.ke'
```

Expected: `HTTP 200` for both.

---

## Step 2 — Loans web (`loans.uwezo.go.ke`)

Built from staging source at `/home/administrator/Loan_Application`. Production images are defined in `/home/administrator/loans/docker-compose.yml`.

### 2.1 Production environment

`/home/administrator/loans/.env` must use the **staging database** credentials:

```env
STAGING_DB_NAME=drupal
STAGING_DB_USER=drupal
STAGING_DB_PASSWORD=<same as staging>
LMS_API_URL=https://lms.uwezo.go.ke
LMS_API_HOST=lms.uwezo.go.ke
PROD_WEB_DOMAIN=loans.uwezo.go.ke
PROD_ADMIN_DOMAIN=loans-admin.uwezo.go.ke
```

Next.js build URLs are in `Loan_Application/web/.env.production` (`NEXT_PUBLIC_APP_URL`, `NEXT_PUBLIC_API_URL`, etc.).

### 2.2 Sync Drupal uploaded files (staging → prod volumes)

```bash
docker run --rm \
  -v uwezo_drupal_files:/from:ro \
  -v uwezo-prod_prod_drupal_files:/to \
  alpine:3.20 sh -c 'cp -a /from/. /to/'

docker run --rm \
  -v uwezo_drupal_private:/from:ro \
  -v uwezo-prod_prod_drupal_private:/to \
  alpine:3.20 sh -c 'cp -a /from/. /to/'
```

### 2.3 Build and deploy containers

```bash
cd /home/administrator/loans
docker compose build --no-cache prod-admin prod-web
docker compose up -d --force-recreate prod-admin prod-web
```

Containers:

- `uwezo-prod-web` — Next.js, `DATABASE_URL=...@db:3306/web`
- `uwezo-prod-admin` — Drupal, `DRUPAL_DATABASE_HOST=db`, database `drupal`

Both attach to external network `uwezo_default` where `db` resolves to `uwezo-db-1` (same as staging).

### 2.4 Post-deploy (Drupal + Prisma)

```bash
docker exec uwezo-prod-admin /var/www/html/vendor/bin/drush deploy -y
docker exec uwezo-prod-web sh -c 'cd /app/web && npx prisma@6.11.1 migrate deploy'
```

### 2.5 Stop unused isolated prod DB (if still running)

The old `uwezo-prod-db` container is no longer used:

```bash
docker stop uwezo-prod-db 2>/dev/null || true
docker compose --remove-orphans up -d   # optional cleanup
```

### 2.6 Verify loans web

```bash
curl -sk -o /dev/null -w "HTTP %{http_code}\n" https://127.0.0.1/ -H 'Host: loans.uwezo.go.ke'
docker exec uwezo-prod-web wget -qO- http://localhost:3000/api/health
```

Expected: `HTTP 200` and health response containing `UP`.

---

## Step 3 — Loans admin (`loans-admin.uwezo.go.ke`)

Promoted together with loans web (same `docker compose` build). The admin image is built from `Loan_Application/admin` and uses the same `drupal` database as `loans-staging-admin.uwezo.go.ke`.

Ecitizen OAuth redirect for production:

```env
ECITIZEN_REDIRECT_URI=https://loans-admin.uwezo.go.ke/api/oauth/ecitizen
```

Ensure the Ecitizen application allows this redirect URI.

### Verify admin

```bash
curl -sk -o /dev/null -w "HTTP %{http_code}\n" https://127.0.0.1/ -H 'Host: loans-admin.uwezo.go.ke'
```

Expected: `HTTP 302` (redirect to login) or `200` on login page.

---

## Step 4 — Database alignment checklist

| App | Container | DB host | Database |
|-----|-----------|---------|----------|
| LMS staging | `uwezo-lms-app` | `uwezo-lms-db` | `uwezo_fund03` |
| LMS production | `uwezo-lms-app` (same) | `uwezo-lms-db` | `uwezo_fund03` |
| Loans staging web | `uwezo-web-1` | `db` | `web` |
| Loans prod web | `uwezo-prod-web` | `db` | `web` |
| Loans staging admin | `uwezo-admin-1` | `db` | `drupal` |
| Loans prod admin | `uwezo-prod-admin` | `db` | `drupal` |

Quick checks:

```bash
docker exec uwezo-lms-app php artisan tinker --execute="echo config('database.connections.mysql.database');"
# → uwezo_fund03

docker exec uwezo-prod-admin php -r 'echo getenv("DRUPAL_DATABASE_HOST")."/".getenv("DRUPAL_DATABASE_NAME");'
# → db/drupal

docker exec uwezo-prod-web sh -c 'echo $DATABASE_URL' | grep '@db:3306/web'
```

---

## One-command promotion

After pulling latest source changes:

```bash
bash /home/administrator/projects/lms_source_code/uwezo-loan-tracking/scripts/promote-staging-to-production.sh
```

Review script output and re-run smoke tests in a browser:

- https://lms.uwezo.go.ke
- https://loans.uwezo.go.ke
- https://loans-admin.uwezo.go.ke

---

## Rollback notes

- **LMS**: Revert git changes in the source tree, re-run `npm run build`, and `php artisan optimize:clear`.
- **Loans**: Rebuild previous image tags if tagged, or checkout prior commit in `Loan_Application` and rebuild.
- **Database**: Because prod shares staging DBs, schema migrations are not isolated—test migrations on staging first.

---

## Deployment log (2026-05-31)

| Check | Result |
|-------|--------|
| `lms.uwezo.go.ke` | HTTP 200 |
| `lms-staging.uwezo.go.ke` | HTTP 200 |
| `loans.uwezo.go.ke` | HTTP 200 |
| `loans-admin.uwezo.go.ke` | HTTP 302 (login redirect) |
| `loans-staging.uwezo.go.ke` | HTTP 200 |
| Drush deploy (prod admin) | Config imported, no pending hooks |
| Prisma migrate (prod web) | No pending migrations |
| LMS DB | `uwezo_fund03 @ uwezo-lms-db` |
| Loans prod DB | `db/drupal` + `db/web` (staging MariaDB) |
