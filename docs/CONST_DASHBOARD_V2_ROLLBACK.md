# Constituency Dashboard V2 — Rollback Guide

SPSS-inspired analytics workspace for constituency officers, with instant rollback via feature flag.

## What changed

| Item | Legacy (`CONST_DASHBOARD_V2=false`) | V2 (`CONST_DASHBOARD_V2=true`) |
|------|-------------------------------------|----------------------------------|
| Livewire component | `App\Livewire\ConstituencyComponent` | `App\Livewire\Constituency\ConstituencyDashboardV2` |
| Blade view | `livewire/constituency-component-legacy.blade.php` | `livewire/constituency/constituency-dashboard-v2.blade.php` |
| Charts | SVG gauge + progress bars | Chart.js (donut, bar) |
| Workflow | Single scrolling page | Workspaces: Overview, Data, Analyze, Tasks |

**Legacy files are preserved.** Switch point: `resources/views/dashboard/constituency.blade.php`.

## Enable V2

```env
CONST_DASHBOARD_V2=true
```

```bash
php artisan config:clear
npm run build
rm -f public/hot   # critical on staging — if this file exists, browsers load localhost:5173 and charts/CSS break
```

Hard-refresh the constituency dashboard URL as a constituency officer.

## Roll back to legacy

```env
CONST_DASHBOARD_V2=false
```

```bash
php artisan config:clear
```

## V2 design notes

- **Overview**: KPI cards, portfolio/demographics/recovery charts, ward recovery bar, action inbox
- **Data**: Ward performance table (replaces duplicate demographic/sector card grids)
- **Analyze**: Demographics / sectors / recovery cross-tab (chart + table)
- **Tasks**: Incoming applications + approved phases (single entry to application portal)

Redundancies removed on the dashboard:

- Duplicate performance gauge + risk cards → consolidated in Analyze
- Separate demographic and sector progress-card grids → charts + tables
- Quick actions link to existing constituency routes

## File inventory

### Preserved legacy

- `app/Livewire/ConstituencyComponent.php`
- `resources/views/livewire/constituency-component-legacy.blade.php`

### New v2 files

- `app/Livewire/Constituency/ConstituencyDashboardV2.php`
- `app/Livewire/Constituency/Concerns/ManagesConstituencyDashboardData.php`
- `resources/views/livewire/constituency/constituency-dashboard-v2.blade.php`
- `resources/js/const-dashboard-v2.js`
- `config/dashboard.php` (`const_v2_enabled`)
