# AI / Blockchain / Analytics — Core LMS

## OCR
- `DocumentTextExtractionService` for phase/application document paths.
- Core LMS image (`uwezo-lms-app`) installs `tesseract-ocr`, `tesseract-ocr-eng`, and `poppler-utils`.
- Stores text in shared `document_extracted_texts`.
- Phase document preview surfaces extraction status when wired.

## Document preview copilot (assistive)
- `DocumentPreviewCopilotService` — rule-based summary from OCR text + `PhaseDocumentType`.
- Shown above the OCR block on `resources/views/documents/phase-document-preview.blade.php`.
- Detects ID/date patterns and suggests officer actions. Labeled assistive — not auto-decision.

## Officer review checklist drafts
- Table `review_checklist_drafts` (shared `uwezo_fund03`, `source_system=core_lms`).
- `ReviewChecklistDraftService` builds items from `PhaseDocumentType::requiredForConstituencyHqSubmission()` plus GFS/amount checks.
- Routes: `GET/POST /review-checklist/{phase}` (`review-checklist.show` / `.save`).
- Blade partial `partials/review-checklist-draft.blade.php` on HQ/CO/accountant phase review views.

## Missing GFS / artefact alerts
- `MissingGfsArtefactAlertService` flags approved / pending_disbursement / in_training apps missing `gfs_code` or required phase artefacts.
- Banner on `/phase-aging` analytics view.

## Hash receipts
- Same internal hasher as Majuu; `source_system=core_lms`.
- Wired on `startReview`, `submitToHq`, and `approvePhaseApplications`.
- Env: `EVENT_HASHER_URL`, `EVENT_HASHER_TOKEN`.
- Hasher is compose-managed with `expose: "8090"` only — no host `ports:` mapping.

## Phase aging + triage
- Route: `/phase-aging` (auth staff).
- `PhaseAgingAnalyticsService::suggestedTriage()` — oldest first, SLA warn ≥7 / breach ≥14 days.
- Command: `php artisan analytics:snapshot-phase-aging` (scheduled daily at 05:30).

## Scheduler
- `uwezo-lms-scheduler` is managed by this repo's `docker-compose.yml` (`php artisan schedule:work --verbose --no-interaction`, `restart: unless-stopped`).
- Uses `env_file: .env` (a file). Do not bind-mount `.env` — if Docker creates `.env` as a directory, the container crashes.
- Staging compose (`docker.compose_staging.yml`) was updated the same way; `/home/administrator/lms_app_staging/.env` remains a directory and must not be bind-mounted.
