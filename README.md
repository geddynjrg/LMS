# Uwezo Fund — Loan Management System (LMS)

Internal staff portal for processing Uwezo Fund constituency group loan applications: constituency review, HQ processing, accounts reconciliation, secretariat sign-off, training, and disbursement.

| Environment | URL |
|-------------|-----|
| Production | https://lms.uwezo.go.ke |
| Staging | https://lms-staging.uwezo.go.ke |

**Related systems**

| System | URL | Users |
|--------|-----|-------|
| Loans portal (group applications) | https://loans.uwezo.go.ke | Groups applying for loans |
| Loans admin | https://loans-admin.uwezo.go.ke | ICT / portal configuration |

---

## Documentation

| Guide | Audience | Description |
|-------|----------|-------------|
| **[How-To Guide](docs/HOW_TO.md)** | All users | Comprehensive step-by-step operational manual |
| **[FAQ](docs/FAQ.md)** | LMS staff | Quick answers to common tasks by role |
| **[Loan Portal FAQ](docs/LOAN_PORTAL_FAQ.md)** | Groups applying online | How to apply on loans.uwezo.go.ke |
| **[Production Deployment](docs/PRODUCTION_DEPLOYMENT.md)** | ICT / DevOps | Staging → production promotion |

Start with the [How-To Guide](docs/HOW_TO.md) for the full loan application workflow from group submission through disbursement.

---

## Workflow at a glance

```
Group applies (loans portal)
  → Constituency reviews & approves into a phase
  → Constituency submits phase to HQ (minutes + bank recon)
  → HQ processes & uploads processed codelist
  → Accounts reviews & clears to HQ management
  → HQ uploads management committee minutes
  → Head of Secretariat signs & approves
  → Constituency schedules training & records disbursement
```

---

## Roles

| Role | Primary access |
|------|----------------|
| Constituency Officer | Applications, phases, groups, reports, disbursement |
| HQ Officer | National submissions, allocations, processed codelists |
| Accountant | Phase financial review (`/accountant/phases`) |
| Head of Secretariat | Phase sign-off and approved codelist |
| Super Admin | Full system access |

---

## Tech stack

- **Backend:** Laravel (PHP), Livewire
- **Frontend:** Vite, Blade templates
- **Database:** MariaDB (`uwezo_fund03`)
- **Queue:** Laravel Horizon
- **Integration:** Drupal loan portal (application sync)

---

## Development

```bash
cd /home/administrator/projects/lms_source_code/uwezo-loan-tracking
composer install
npm ci && npm run build
cp .env.example .env   # configure DB, APP_URL, etc.
php artisan migrate
php artisan serve
```

Production assets are built on the host and served from `public/build/`. See [PRODUCTION_DEPLOYMENT.md](docs/PRODUCTION_DEPLOYMENT.md) for full deploy steps.

---

## Repository

GitHub: [geddynjrg/LMS](https://github.com/geddynjrg/LMS)

---

*Uwezo Fund · Ministry responsible for Uwezo Fund operations*
