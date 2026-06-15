# LMS staging full fix — apply & revert guide

**Date:** 2026-06-03  
**Repo:** `/home/administrator/projects/lms_source_code/uwezo-loan-tracking`  
**Container:** `uwezo-lms-app` (bind-mounted to repo)  
**Pre-fix backup:** `/home/administrator/backups/lms_full_fix_20260603_134628/`  
**Git HEAD at fix time:** `9c941cba28c2b70845dba4b58f6f85c5d8cb182f`

Use this document if a change causes regressions and you need to roll back or re-apply fixes.

---

## Summary of changes

| Area | What was done |
|------|----------------|
| Missing PHP classes | Restored 7 deleted tracked files from `git checkout HEAD --` |
| HQ `/hq/hq-reports` | `nationalIndex()` now redirects to `hq.reports.index` |
| Queue `ProcessLoanApplication` | Restored `LoanApplicationService`; fixed `confirmation_sent_at` null key |
| Wards API | Added `LocationController`, extended `LocationService`, route `GET api/v1/locations/wards` |
| Storage downloads | `StoragePathHelper` + audit command; controllers use unified path resolution |
| PHP deprecations | Parameter order/defaults fixed in `AppCacheService`, `GroupsService`, `LoanPhaseService`, `TempApplicationService` |
| Excel exports | `ApplicationPhaseDetails` uses FQCN `\Maatwebsite\Excel\Facades\Excel` |

---

## Files restored from git (revert = delete or `git checkout` before fix)

```bash
cd /home/administrator/projects/lms_source_code/uwezo-loan-tracking
git checkout HEAD -- \
  app/Services/Group/GroupsService.php \
  app/Services/Loan/LoanApplicationService.php \
  app/Livewire/Hq/GenerateReport.php \
  app/Livewire/Hq/GroupLoans.php \
  app/Livewire/Hq/GroupsList.php \
  app/Http/Helpers/FileUploadHelper.php \
  app/Imports/MembersImport.php
```

**Re-apply restore:**

```bash
git checkout HEAD -- <paths above>
docker exec uwezo-lms-app composer dump-autoload -o
docker exec uwezo-lms-app php artisan view:clear
```

---

## New / modified files (revert = remove or restore from backup)

| File | Revert action |
|------|----------------|
| `app/Livewire/Hq/Concerns/ManagesHqDashboardData.php` | Copy from `/home/administrator/backups/ManagesHqDashboardData_recovered.php` or prior session backup |
| `app/Http/Controllers/Api/LocationController.php` | `rm` file; remove route from `routes/api.php` |
| `app/Http/Helpers/StoragePathHelper.php` | `rm` file; restore `getFilePath()` in `LoansController` + `HqApplications` from git |
| `app/Console/Commands/AuditApplicationStorageFiles.php` | `rm` file (optional command) |
| `app/Services/LocationService.php` | `git checkout HEAD --` (loses ward resolver helpers) |
| `app/Services/Loan/LoanApplicationService.php` | Revert `confirmation_sent_at` hunk only if queue breaks |
| `app/Http/Controllers/ReportsController.php` | Restore empty `nationalIndex()` stub if redirect unwanted |
| `app/Services/Cache/AppCacheService.php` | `git checkout HEAD --` |
| `app/Services/Group/GroupsService.php` | `git checkout HEAD --` after restore (loses param fix) |
| `app/Services/Loan/LoanPhaseService.php` | `git checkout HEAD --` |
| `app/Services/Loan/TempApplicationService.php` | `git checkout HEAD --` |
| `routes/api.php` | Remove `LocationController` import + `locations/wards` route |

**After any revert:**

```bash
docker exec uwezo-lms-app composer dump-autoload -o
docker exec uwezo-lms-app php artisan view:clear
docker exec uwezo-lms-app php artisan config:clear
docker exec uwezo-lms-app php artisan route:clear
```

---

## Verification checklist

```bash
# Classes exist
docker exec uwezo-lms-app php -r 'require "vendor/autoload.php"; foreach (["App\\Services\\Group\\GroupsService","App\\Livewire\\Hq\\GroupsList","App\\Services\\Loan\\LoanApplicationService"] as $c) echo class_exists($c)?"OK $c\n":"MISS $c\n";'

# Wards API (set X-API-Key from .env)
docker exec uwezo-lms-app curl -sS -H "X-API-Key: YOUR_KEY" \
  "http://127.0.0.1:8000/api/v1/locations/wards?constituency_name=Tigania%20West" | head -c 500

# Views (no fatal)
docker exec uwezo-lms-app php artisan tinker --execute='
$c=App\Models\County::first(); $co=App\Models\Constituency::first();
view("group.view-groups",["county"=>$c,"constituency"=>$co])->render();
view("hq.group.groups_list")->render();
echo "ok";
'

# Storage audit (lists DB paths missing on disk — does not delete data)
docker exec uwezo-lms-app php artisan storage:audit-application-files --limit=20

# Queue (after retry)
docker exec uwezo-lms-app php artisan queue:failed
```

**Browser (logged in):**

- `/dashboard` (HQ)
- `/hq/groups/list`, `/hq/loans/group-loans`, `/hq/reports`
- `/hq/hq-reports` → should redirect to `/hq/reports`
- Constituency: `/{county}/{constituency}/groups`, `.../loans` (group loans)
- `.../reports/dynamic-report`, `.../reports/data-upload`

---

## Storage missing files (data issue)

Run: `php artisan storage:audit-application-files --limit=20`

**Finding (2026-06-03):** Many DB paths point to filenames that do not exist under `storage/app/applications/{APP-ID}/...` (e.g. API saved one filename, disk has a different hashed name, or files were never copied to this server). Newer applications (e.g. `APP-20260603-*`) do have files under `storage/app/applications/`.

**Code fix:** `StoragePathHelper` resolves exact paths on `local`/`public` disks. It cannot invent missing binaries.

**Operational recovery:**

1. Use audit output to list orphaned DB document paths.
2. Re-submit or re-upload from the loans web application, **or** restore a storage backup into `storage/app/applications/`.
3. Optionally add a one-off script to match by `application_id` + document folder if files exist with different names in the same directory (not automated in this fix).

Example paths still missing: `applications/APP-20260602-IR0KH8/group_registration_certificate/kcb-pdq_....pdf`

---

## Queue jobs

**Fix applied:** `confirmation_sent_at` / `confirmation_confirmed_at` use `! empty($memberData['...'] ?? null)` before `createFromTimestamp`.

**Retry failed jobs (already run during fix; safe to repeat):**

```bash
docker exec uwezo-lms-app php artisan queue:retry all
```

**If retries keep failing:** inspect `php artisan queue:failed` and `storage/logs/laravel.log`; do not delete `failed_jobs` rows until root cause is confirmed.

---

## Related docs

- `docs/staging-lms-fixes-revert-2026-06-03.md` — Dynamic Report, HQ dashboard trait, Data Upload
- `docs/HQ_DASHBOARD_V2_ROLLBACK.md` — toggle `HQ_DASHBOARD_V2` env flag

---

## Do NOT revert unless necessary

- Database rows / uploaded report tables (constituency data upload)
- `public/templates/members.xlsx` (regenerated template)
- `.env` values (`HQ_DASHBOARD_V2`, API keys)
