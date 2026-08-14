# AI / Blockchain / Analytics — Core LMS

## OCR
- `DocumentTextExtractionService` for phase/application document paths.
- Stores text in shared `document_extracted_texts`.
- Phase document preview surfaces extraction status when wired.

## Hash receipts
- Same internal hasher as Majuu; `source_system=core_lms`.
- Wired on `startReview`, `submitToHq`, and `approvePhaseApplications`.
- Env: `EVENT_HASHER_URL`, `EVENT_HASHER_TOKEN`.

## Phase aging
- Route: `/phase-aging` (auth staff).
- Command: `php artisan analytics:snapshot-phase-aging` (scheduled daily).

## Scheduler
- `uwezo-lms-scheduler` must run `php artisan schedule:work` from this repo compose (not the broken staging `.env` directory mount).
