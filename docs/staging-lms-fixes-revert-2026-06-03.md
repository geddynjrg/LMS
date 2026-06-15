# LMS staging fixes — investigation and revert guide

**Date:** 2026-06-03  
**Environment:** `uwezo-lms-app` (bind mount → `/home/administrator/projects/lms_source_code/uwezo-loan-tracking`)  
**Git commit at time of fix:** `9c941cba28c2b70845dba4b58f6f85c5d8cb182f` (branch `main`)

This document covers staging **500 error** fixes: Dynamic Report, **HQ Admin Dashboard**, Data Upload, and member template. Use it to undo file-level changes if something regresses.

**For the 2026-06-03 full staging repair** (missing GroupsService/HQ Livewire, wards API, queue, storage audit, deprecations), see **`docs/staging-lms-full-fix-revert-2026-06-03.md`**.

---

## HQ Admin Dashboard 500 — root cause

**URL:** `https://lms-staging.uwezo.go.ke/dashboard` (HQ officer role; `DashboardController` → `hq.dashboard`)

**Laravel log (before fix):**

```text
Trait "App\Livewire\Hq\Concerns\ManagesHqDashboardData" not found
  at app/Livewire/Hq/HqDashboardV2.php:13
```

**Chain:**

1. `.env` has `HQ_DASHBOARD_V2=true` → `resources/views/hq/dashboard.blade.php` mounts `@livewire('hq.hq-dashboard-v2')`.
2. Both `HqDashboardV2` and legacy `HqDashboard` `use ManagesHqDashboardData`.
3. **`app/Livewire/Hq/Concerns/ManagesHqDashboardData.php` was never on disk** — the trait was added in a prior dev session but **not committed to git** (`git ls-files` empty for `app/Livewire/Hq/Concerns/`). The Concerns directory existed but was empty.

**Instant rollback (UI only, no PHP):** set `HQ_DASHBOARD_V2=false` and `php artisan config:clear` — still requires the trait for legacy `HqDashboard` unless that component is also broken.

---

## What was changed (HQ Dashboard)

| Action | Path |
|--------|------|
| Restored trait (recovered from session backup / agent transcript) | `app/Livewire/Hq/Concerns/ManagesHqDashboardData.php` (~10.7KB, 325 lines) |
| Cache clear (container) | `php artisan view:clear` |

**No database changes.**

**Pre-change backup (host):**

```text
/home/administrator/backups/lms_hq_dashboard_fix_20260603_132309/
  pre-fix.txt                 # empty Concerns/ directory listing
/home/administrator/backups/ManagesHqDashboardData_recovered.php   # source copy used for restore
```

**Post-restore checksum:**

```text
07eb0a116379f225d47d1d9997401b12afdf494ae085b0bd9813e127781c8785  app/Livewire/Hq/Concerns/ManagesHqDashboardData.php
```

**Server-side verification:**

```bash
docker exec uwezo-lms-app php artisan tinker --execute='
auth()->login(App\Models\User::find(356));
$c = Livewire\Livewire::test(App\Livewire\Hq\HqDashboardV2::class);
echo strlen($c->html());
'
# Expected: ~44000+ bytes HTML (deprecation warnings OK)
```

**V2 charts:** `HQ_DASHBOARD_V2=true` uses Chart.js via Vite entries in `vite.config.js`. If charts are blank, run `npm run build` on the host and refresh; that is a **frontend** issue, not the 500.

See also: `docs/HQ_DASHBOARD_V2_ROLLBACK.md` for toggling V2 vs legacy.

---

## Revert HQ Dashboard fix

```bash
cd /home/administrator/projects/lms_source_code/uwezo-loan-tracking

# Remove trait (restores prior 500 — not recommended)
rm -f app/Livewire/Hq/Concerns/ManagesHqDashboardData.php

# Or restore from backup
cp /home/administrator/backups/ManagesHqDashboardData_recovered.php \
   app/Livewire/Hq/Concerns/ManagesHqDashboardData.php

docker exec uwezo-lms-app php artisan view:clear
docker exec uwezo-lms-app php artisan config:clear
```

**Recommended:** commit `ManagesHqDashboardData.php` to git so future deploys do not lose it again.

---

## Dynamic Report 500 — root cause

**URL pattern (all constituencies):**  
`https://lms-staging.uwezo.go.ke/{county-slug}/{constituency-slug}/reports/dynamic-report`

**Laravel log (before fix):**

```text
include(.../app/Livewire/DynamicReports.php): Failed to open stream: No such file or directory
```

**Chain:**

1. `routes/web.php` → `ReportsController::dynamicReport`
2. `resources/views/reports/dynamic_report.blade.php` → `@livewire('dynamic-reports', ...)`
3. Livewire autoload expects `app/Livewire/DynamicReports.php` — **file was missing on disk** (deleted from working tree; still in git).
4. After restoring that class, render failed again: **`App\Services\LocationService` does not exist** — same situation (`git status` showed `deleted: app/Services/LocationService.php`).

Views were present; the failure was **missing PHP classes**, not missing Blade templates.

**Note:** `app/Livewire/Dynamic_bkup.php` exists on disk (~77KB) and defines `class DynamicReports` but is **not** loaded by Composer (wrong filename). Do not rename it to replace git HEAD without a diff review.

---

## What was changed (Dynamic Report only)

| Action | Path |
|--------|------|
| Restored from `git checkout HEAD --` | `app/Livewire/DynamicReports.php` (2142 lines, ~79KB) |
| Restored from `git checkout HEAD --` | `app/Services/LocationService.php` (~3.2KB) |
| Cache clear (container) | `php artisan view:clear` |
| Autoload refresh (container) | `composer dump-autoload -o` |

**No database migrations.** No changes to uploaded report rows or import tables.

**Pre-change backup (host):**

```text
/home/administrator/backups/lms_dynamic_report_fix_20260603_123658/
  Dynamic_bkup.php          # copy of on-disk backup component (not used by autoload)
  pre-fix-status.txt        # ls output showing DynamicReports.php missing
```

**Post-restore checksums (for verification):**

```text
e848da70ddff191d655f29a3eed6e7536f4c1d762dca162137d0a6c2cdf53a19  app/Livewire/DynamicReports.php
fbeb6cf3438ffa0d7ae9ec922dfaaa9d988d2608284eebf04dfc9dd80d1237f6  app/Services/LocationService.php
```

**Server-side verification (inside `uwezo-lms-app`):**

```bash
docker exec uwezo-lms-app php artisan tinker --execute="
\$county = App\Models\County::where('slug','taita-taveta')->first();
\$c = App\Models\Constituency::where('slug','voi')->first();
\$html = view('reports.dynamic_report', ['county'=>\$county,'constituency'=>\$c])->render();
echo 'rendered '.strlen(\$html).' bytes';
"
# Expected: rendered ~21639 bytes (deprecation warnings from AppCacheService are non-fatal)
```

---

## Revert Dynamic Report fix

If you must undo **only** the dynamic-report restoration:

```bash
cd /home/administrator/projects/lms_source_code/uwezo-loan-tracking

# Optional: stash current restored files first
git stash push -m "revert-dynamic-report-$(date +%Y%m%d)" -- \
  app/Livewire/DynamicReports.php \
  app/Services/LocationService.php

# Or delete files to reproduce prior broken state (not recommended):
# rm -f app/Livewire/DynamicReports.php app/Services/LocationService.php

# Safer: restore to a known prior commit for these paths only
# git checkout <older-commit> -- app/Livewire/DynamicReports.php app/Services/LocationService.php

docker exec uwezo-lms-app php artisan view:clear
docker exec uwezo-lms-app php artisan cache:clear
docker exec uwezo-lms-app composer dump-autoload -o
```

To **re-apply** the fix after a revert:

```bash
cd /home/administrator/projects/lms_source_code/uwezo-loan-tracking
git checkout HEAD -- app/Livewire/DynamicReports.php app/Services/LocationService.php
docker exec uwezo-lms-app php artisan view:clear
docker exec uwezo-lms-app composer dump-autoload -o
```

---

## Related staging fixes (same repo; separate revert)

### Data Upload 500

- **Cause:** `View [livewire.reports.data-upload] not found` — main Blade missing; only `bkp/` copy existed.
- **Fix:** Restored `resources/views/livewire/reports/data-upload.blade.php` from git HEAD (~1203 lines). **Do not** replace with `bkp/data-upload.blade.php` unless intentionally rolling back UI.
- **Revert:** `git checkout HEAD -- resources/views/livewire/reports/data-upload.blade.php` then `view:clear`.

### Member Excel template (protected cells)

- **Fix:** `app/Exports/MembersTemplateExport.php` (no sheet protection); regenerated `public/templates/members.xlsx`.
- **Revert:** `git checkout HEAD -- app/Exports/MembersTemplateExport.php public/templates/members.xlsx` (or restore from backup if you copied one).

### Wards / location API (loans app + HQ)

- **Files often involved:** `app/Services/LocationService.php`, `app/Http/Controllers/Api/LocationController.php`, `routes/api.php`, Livewire HQ components.
- If `routes/api.php` or `LocationController` are missing on disk, restore: `git checkout HEAD -- routes/api.php app/Http/Controllers/Api/LocationController.php`

---

## Other deleted files noticed (not restored for dynamic report)

These were **deleted in the working tree** but may affect other pages if hit:

- `app/Livewire/Hq/GenerateReport.php`
- `app/Livewire/Hq/GroupLoans.php`
- `app/Livewire/Hq/GroupsList.php`
- `app/Services/Group/GroupsService.php`
- `app/Services/Loan/LoanApplicationService.php`
- `app/Http/Helpers/FileUploadHelper.php`
- `app/Imports/MembersImport.php`

Restore individually from git if those routes 500.

---

## Manual smoke test (browser)

Requires login on staging.

1. Voi: `/taita-taveta/voi/reports/dynamic-report` — expect 200 and Dynamic Report UI (not 500).
2. Another constituency: e.g. `/meru/tigania-west/reports/dynamic-report`.
3. Data upload (regression): `/taita-taveta/voi/reports/data-upload`.
4. Member template download from Data Upload step 3 — editable cells, no sheet lock.

---

## Investigation commands (repeatable)

```bash
# Laravel log
docker exec uwezo-lms-app tail -100 /var/www/html/storage/logs/laravel.log

# Missing tracked files
cd /home/administrator/projects/lms_source_code/uwezo-loan-tracking
git status --short | grep '^ D'

# Class exists in container
docker exec uwezo-lms-app php -r "require '/var/www/html/vendor/autoload.php'; var_export(class_exists('App\Livewire\DynamicReports'));"
```

---

## Contact / scope

- Changes are **filesystem only** on the bind-mounted repo; container rebuild not required for PHP/Blade restores.
- Rebuild `uwezo-web-1` only if `Loan_Application/web` was changed (wards service normalization).
