# HQ Dashboard V2 — Rollback Guide

This document describes the SPSS-inspired HQ dashboard (v2), how to enable it, and how to revert to the legacy interface.

## What changed

| Item | Legacy (default) | V2 (optional) |
|------|------------------|-----------------|
| Livewire component | `App\Livewire\Hq\HqDashboard` | `App\Livewire\Hq\HqDashboardV2` |
| Blade view | `livewire/hq/hq-dashboard-legacy.blade.php` | `livewire/hq/hq-dashboard-v2.blade.php` |
| Feature flag | `HQ_DASHBOARD_V2=false` | `HQ_DASHBOARD_V2=true` |
| Charts | Progress bars + SVG gauge | Chart.js (donut, bar, line) |
| Workflow | Single scrolling page | SPSS-like workspaces: Overview, Data, Analyze, Tasks |

**Legacy files are preserved and not modified** (except `hq/dashboard.blade.php`, which switches between versions).

## Enable V2

1. Edit `.env`:
   ```env
   HQ_DASHBOARD_V2=true
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Rebuild frontend assets (Chart.js bundle):
   ```bash
   npm run build
   rm -f public/hot
   ```
   If `public/hot` exists, Laravel serves Vite **dev** URLs (`localhost:5173`) and pie charts will not load in browsers.
4. Hard-refresh the browser on `/dashboard` as an HQ user.

## Roll back to legacy (instant)

1. Edit `.env`:
   ```env
   HQ_DASHBOARD_V2=false
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Refresh `/dashboard`.

No database migrations or data changes are required.

## Full removal of V2 (optional)

If you decide to drop v2 entirely:

```bash
rm app/Livewire/Hq/HqDashboardV2.php
rm resources/views/livewire/hq/hq-dashboard-v2.blade.php
rm resources/js/hq-dashboard-v2.js
rm resources/css/hq-dashboard-v2.css
rm config/dashboard.php
# Restore hq/dashboard.blade.php to always use @livewire('hq.hq-dashboard-legacy')
```

Remove `resources/js/hq-dashboard-v2.js` from `vite.config.js` and run `npm run build`.

## File inventory

### Preserved legacy (rollback source of truth)

- `app/Livewire/Hq/HqDashboard.php` — unchanged component logic
- `resources/views/livewire/hq/hq-dashboard-legacy.blade.php` — copy of original dashboard UI

### New v2 files

- `app/Livewire/Hq/HqDashboardV2.php`
- `resources/views/livewire/hq/hq-dashboard-v2.blade.php`
- `resources/js/hq-dashboard-v2.js`
- `resources/css/hq-dashboard-v2.css`
- `config/dashboard.php`

### Switch point

- `resources/views/hq/dashboard.blade.php` — reads `config('dashboard.hq_v2_enabled')`

## V2 design notes

- **Overview**: KPI cards, portfolio donut, recovery chart, task inbox
- **Data**: Filterable constituency performance table (from existing analytics service)
- **Analyze**: Choose dimension (demographics / sectors / recovery) and view chart + table
- **Tasks**: Pending HQ phases and quick links to Approval Tasks (single entry — no duplicate export paths on dashboard)

Redundancies removed on the dashboard itself:

- Duplicate progress-bar demographic cards replaced by one chart + table
- Separate “performance” and “risk” sections consolidated in Analyze workspace
- Quick actions point to existing HQ routes instead of re-implementing them

## Implementation checklist (completed)

- [x] `config/dashboard.php` feature flag
- [x] Legacy view preserved at `hq-dashboard-legacy.blade.php`
- [x] `HqDashboardV2` Livewire component + shared data trait
- [x] `hq-dashboard-v2.blade.php` SPSS workspace UI
- [x] Chart.js bundle (`resources/js/hq-dashboard-v2.js`)
- [x] Styles (`resources/css/hq-dashboard-v2.css`)
- [x] Vite entries in `vite.config.js`
- [x] Switch in `resources/views/hq/dashboard.blade.php`
- [x] `HQ_DASHBOARD_V2` in `.env.example`

After pulling these changes, run `npm install && npm run build` before enabling v2.
