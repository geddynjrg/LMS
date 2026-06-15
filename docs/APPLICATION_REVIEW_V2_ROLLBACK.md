# Application Review V2 — Rollback Guide

Improved constituency application review UX with workflow stepper, next-action banner, and always-visible action rail. Instant rollback via feature flag.

## What changed

| Item | Legacy (`APPLICATION_REVIEW_V2=false`) | V2 (`APPLICATION_REVIEW_V2=true`) |
|------|----------------------------------------|-----------------------------------|
| Blade view | `livewire/loan/app/application-view-legacy.blade.php` | `livewire/loan/app/application-view-v2.blade.php` |
| Workflow | Collapsible sidebar cards | Stepper + next-action banner + fixed action rail |
| Tabs | Generic labels | Renamed: Application, Members, Loan history, Documents, Activity |
| Modals / actions | Shared | Same modals (`partials/application-review-modals.blade.php`) |

**Legacy view is preserved.** Switch point: `ApplicationView::render()` reads `config('applications.review_v2_enabled')`.

## Enable V2

```env
APPLICATION_REVIEW_V2=true
```

```bash
php artisan config:clear
npm run build
rm -f public/hot
```

Open a constituency application review URL, e.g. `{county}/{constituency}/applications/{applicationId}`.

## Roll back to legacy

```env
APPLICATION_REVIEW_V2=false
```

```bash
php artisan config:clear
```

No asset rebuild required for rollback (v2 CSS is only loaded when the v2 view renders).

## V2 design notes

- **Header**: Application ID, status, phase, amount, submit date; compact toolbar (certificate, disbursement, export, refresh)
- **Stepper**: Assign phase → Verify identity → Review details → Approve/reject
- **Next-action banner**: Contextual guidance with primary CTA (assign, review matches, approve)
- **Action rail**: Phase assignment, system verification, approve/reject — always expanded (no collapse)
- **Mobile**: Sticky bottom bar with primary actions

## Files

- `app/Livewire/Concerns/ManagesApplicationReviewWorkflow.php` — stepper, banner, tab metadata
- `resources/views/livewire/loan/app/application-view-v2.blade.php`
- `resources/views/livewire/loan/app/partials/application-review-action-rail.blade.php`
- `resources/views/livewire/loan/app/partials/application-review-modals.blade.php`
- `resources/css/application-review-v2.css`
- `config/applications.php` → `review_v2_enabled`
