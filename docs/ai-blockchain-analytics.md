# AI / Blockchain / Analytics — Core LMS

## OCR
- `DocumentTextExtractionService` for phase/application document paths.
- Core LMS image (`uwezo-lms-app`) installs `tesseract-ocr`, `tesseract-ocr-eng`, and `poppler-utils`.
- Stores text in shared `document_extracted_texts`.
- Phase document preview surfaces extraction status when wired.

## Hash receipts
- Same internal hasher as Majuu; `source_system=core_lms`.
- Wired on `startReview`, `submitToHq`, and `approvePhaseApplications`.
- Env: `EVENT_HASHER_URL`, `EVENT_HASHER_TOKEN`.
- Hasher is compose-managed with `expose: "8090"` only — no host `ports:` mapping.

## Phase aging
- Route: `/phase-aging` (auth staff).
- Command: `php artisan analytics:snapshot-phase-aging` (scheduled daily at 05:30).

## Scheduler
- `uwezo-lms-scheduler` is managed by this repo's `docker-compose.yml` (`php artisan schedule:work --verbose --no-interaction`, `restart: unless-stopped`).
- Uses `env_file: .env` (a file). Do not bind-mount `.env` — if Docker creates `.env` as a directory, the container crashes.
- Staging compose (`docker.compose_staging.yml`) was updated the same way; `/home/administrator/lms_app_staging/.env` remains a directory and must not be bind-mounted.
