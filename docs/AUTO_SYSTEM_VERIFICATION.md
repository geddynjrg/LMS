# Auto System Verification

When `AUTO_SYSTEM_VERIFICATION=true`, applications are verified automatically immediately after phase assignment.

## Behaviour

| Match result | Action |
|--------------|--------|
| No matches | `link_status = attempted_no_match`, `loan_type = Wezesha` — ready to approve as new group |
| Exactly one **strong** match | Auto-linked to master group, `loan_type = Endeleza` — ready to approve |
| Multiple matches or ambiguous | Left as `not_attempted` — officer uses **Review Matches** manually |

**Strong match** means any of:

- Match score ≥ 90%
- Registration number match
- 4+ member ID numbers match

## Enable / disable

```env
AUTO_SYSTEM_VERIFICATION=true   # enable
AUTO_SYSTEM_VERIFICATION=false  # disable (manual flow only)
```

```bash
php artisan config:clear
```

## Audit

Outcomes are logged to the activity log (`system-verification` and `phase-assignment`).
