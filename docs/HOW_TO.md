# Uwezo Fund Loan Application System — How-To Guide

A step-by-step operational guide for everyone who uses the Uwezo Fund loan application ecosystem: groups applying online, constituency officers processing applications, and HQ / Accounts / Secretariat staff approving phases for training and disbursement.

For quick answers to specific tasks, see [FAQ.md](./FAQ.md). Groups applying online should use [LOAN_PORTAL_FAQ.md](./LOAN_PORTAL_FAQ.md). For deployment and server setup, see [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md).

---

## Table of contents

1. [What this system does](#1-what-this-system-does)
2. [System components and URLs](#2-system-components-and-urls)
3. [Roles and dashboards](#3-roles-and-dashboards)
4. [Key concepts](#4-key-concepts)
5. [End-to-end workflow](#5-end-to-end-workflow)
6. [Group portal — how groups apply](#6-group-portal--how-groups-apply)
7. [Constituency officer — how to process applications](#7-constituency-officer--how-to-process-applications)
8. [HQ officer — how to process phases](#8-hq-officer--how-to-process-phases)
9. [Accountant — how to review phases](#9-accountant--how-to-review-phases)
10. [Head of Secretariat — how to approve phases](#10-head-of-secretariat--how-to-approve-phases)
11. [After approval — training and disbursement](#11-after-approval--training-and-disbursement)
12. [Historical data upload](#12-historical-data-upload)
13. [GFS codes — rules and data quality](#13-gfs-codes--rules-and-data-quality)
14. [Loan recommendation formula](#14-loan-recommendation-formula)
15. [Reports and exports](#15-reports-and-exports)
16. [Troubleshooting](#16-troubleshooting)
17. [Getting help](#17-getting-help)

---

## 1. What this system does

The Uwezo Fund Loan Application System manages the full lifecycle of constituency-based group loans:

| Stage | Who | What happens |
|-------|-----|--------------|
| Apply | Group (online) | Group submits application with members, financials, and documents |
| Review | Constituency Officer | Applications are reviewed, linked to master groups, approved into a **phase** |
| Submit | Constituency Officer | Phase is submitted to HQ with committee minutes and bank reconciliation |
| Process | HQ Officer | Applications are verified; **processed codelist** is generated |
| Clear | Accountant | Processed codelist and bank recon are reconciled |
| Authorise | Head of Secretariat | Phase is signed; training and disbursement are authorised |
| Disburse | Constituency Officer | Training is scheduled; cheques are recorded |

The **LMS** (Loan Management System) is the internal staff portal. The **Loans portal** is where groups apply. Both share application data through the integrated database.

---

## 2. System components and URLs

| Component | Production URL | Purpose |
|-----------|----------------|---------|
| **LMS** | `https://lms.uwezo.go.ke` | Constituency, HQ, Accounts, Secretariat workflows |
| **Loans portal** | `https://loans.uwezo.go.ke` | Group self-service loan applications |
| **Loans admin** | `https://loans-admin.uwezo.go.ke` | Portal configuration (Drupal admin — ICT use) |

Staging equivalents use `-staging` in the hostname (e.g. `lms-staging.uwezo.go.ke`).

**Browser:** Use Chrome, Firefox, or Edge. JavaScript must be enabled.

---

## 3. Roles and dashboards

After login, the LMS sends you to the dashboard for your role:

| Role | Landing page | Primary menu areas |
|------|--------------|-------------------|
| **Constituency Officer** | Constituency dashboard | Applications, Loans, Groups, Reports |
| **HQ Officer** | HQ Applications | Submissions, Allocations, Groups, Reports |
| **Accountant** | Accounts Dashboard (`/accountant/phases`) | Pending phase review |
| **Head of Secretariat** | Secretariat Pending Tasks | Phase sign-off |
| **Super Admin** | Role-dependent | All areas |

Constituency officers only see data for their assigned constituency. HQ, Accounts, and Secretariat see national queues filtered by phase status.

---

## 4. Key concepts

### Phases

A **phase** is a funding cycle (e.g. *Phase 6*) for one constituency. All applications in a phase are reviewed together, submitted to HQ as a batch, and eventually disbursed together.

Phases move through statuses:

```
draft → submitted_to_hq → approved → in_training → disbursed
```

### Loan products

| Product | Meaning | How it is set |
|---------|---------|---------------|
| **Wezesha** | First-time loan for a **new** group | No master group match at review; officer assigns a new group code |
| **Endeleza** | Repeat loan for a **returning** group | Officer links application to an existing master group; loan level increments |

### Loan types (within a phase)

| Type | Use |
|------|-----|
| **New** | First application in a phase for the group |
| **Resubmission** | Group reapplies after a previous rejection or incomplete submission |
| **Reallocation** | Funds reallocated within policy rules |
| **Refinancing** | Additional loan while an existing one is active (only type allowed multiple times per phase) |

### GFS code

The **Government Financial System (GFS) code** uniquely identifies a group within a constituency for treasury and reporting.

**Format:**

```
4-{county}-{constituency}-411020{category}-{sector}{category}-{group_suffix}
```

Example: `4-015-070-4110202-402-002`

- **Last 3–4 digits** (`002`) = group suffix — **one suffix per group per constituency**
- Same group keeps the same suffix across all phases
- Different groups **must not** share the same suffix

### Master group

A **master group** is the permanent record in **Groups Management**. Endeleza applications must link to it. Wezesha groups are created at first approval.

### Processed codelist

An Excel workbook generated by the LMS listing every approved application in a phase: GFS codes, amounts, loan levels, ward totals, and financial summary. HQ uploads or generates this file; Accounts uses it for reconciliation.

### Approved codelist

A **signed PDF** produced after Head of Secretariat approval. Available to constituency, HQ, and Secretariat from the phase **Generated Files** section.

---

## 5. End-to-end workflow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  GROUP PORTAL (loans.uwezo.go.ke)                                           │
│  Group registers → fills application → members confirm → submits            │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONSTITUENCY (LMS)                                                           │
│  Review applications → assign to phase → approve/reject each application    │
│  Upload committee minutes + bank reconciliation → Submit phase to HQ        │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  HQ (LMS)                                                                   │
│  Review phase → verify amounts/GFS → upload Processed Codelist (Excel)      │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  ACCOUNTS (LMS)                                                             │
│  Compare processed codelist vs bank recon → Approve & send to HQ management │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  HQ (LMS)                                                                   │
│  Upload Management Proposal Review Committee minutes                          │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  HEAD OF SECRETARIAT (LMS)                                                  │
│  Review documents → upload signature → approve phase                        │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  CONSTITUENCY (LMS)                                                         │
│  Schedule training → record disbursement (cheque no., amount, identifier)   │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Phase locking:** Once a constituency submits a phase to HQ, constituency editing is locked until HQ returns it for corrections.

---

## 6. Group portal — how groups apply

> Groups use **loans.uwezo.go.ke** (staging: **loans-staging.uwezo.go.ke**), not the LMS.

**Detailed applicant guide:** [LOAN_PORTAL_FAQ.md](./LOAN_PORTAL_FAQ.md) — step-by-step for all 7 application steps, OTP confirmation, and dashboard tracking.

### Before applying

1. Confirm the group has a valid registration and bank details.
2. Gather National ID numbers and phone numbers for all members.
3. Prepare income/expense figures and table-banking records if applicable.

### Application steps

1. **Register / log in** on the loans portal.
2. **Start a new application** and select the constituency and ward.
3. **Add group details:** nature of group (Youth / Women / PWD), business type, sector.
4. **Add members** — each member confirms via SMS/portal.
5. **Enter financials:** monthly income, expenses, table-banking contributions and duration.
6. **Upload required documents** (registration certificate, bank statement, etc.).
7. **Submit** — status becomes *Submitted* and appears in the constituency LMS queue.

### After submission

- The group can track status on the portal.
- The constituency officer reviews in the LMS under **Applications**.
- Rejected applications show a reason; the group may resubmit as a **Resubmission**.

---

## 7. Constituency officer — how to process applications

### 7.1 Log in and find applications

1. Open `https://lms.uwezo.go.ke` and log in with your official government email.
2. Go to **Applications** (or **Loans → Applications**).
3. Use tabs/filters: *Submitted*, *Under review*, *In phase*.
4. Click an application ID to open the full review page.

### 7.2 Create or open a phase

1. Go to **Applications → Phases** (or **Loans → Phases**).
2. Click **Create phase** or open an existing **draft** phase.
3. Name it clearly: `Phase 6`, `Phase 7`, etc. — do not put "Refinancing" or "Resubmission" in the phase name.

### 7.3 Assign applications to a phase

1. From the applications list or phase page, select applications.
2. **Add to phase** and confirm they appear in the phase application list.
3. Only applications in *reviewable* status can be added.

### 7.4 Review each application

Open the application and work through these checks:

| Check | Action |
|-------|--------|
| Group identity | Link to master group (**Endeleza**) or mark as new (**Wezesha**) |
| Ward | Assign ward if missing |
| Documents | Verify uploads are complete and readable |
| Eligibility | Check uncleared balances, defaults, duplicate members |
| Amount | Use system recommendation (see [§14](#14-loan-recommendation-formula)) |

#### Link a returning group (Endeleza)

1. Search by group name or registration number.
2. Select the matching **master group** from suggestions.
3. Confirm — loan type becomes **Endeleza** and loan level increments.

#### Register a new group (Wezesha)

1. Confirm no master group match exists.
2. Mark as **new group**.
3. On approval, enter a **group code** (3+ digits, unique in the constituency).
4. The system generates the GFS prefix; your group code completes the full GFS.

### 7.5 Approve an application

1. Confirm the application is **assigned to a phase**.
2. Complete group linking and ward assignment.
3. Click **Approve**.
4. In the approval modal:
   - Read **System Recommended Amount** and formula breakdown.
   - Review **Ward allocation balance** for this phase.
   - Enter **Final Approved Amount** within shown limits.
   - Add optional remarks.
5. Click **Confirm Approval**.

If the modal shows **Decline**, do not approve — reject the application instead.

### 7.6 Reject an application

1. Click **Reject**.
2. Choose a reason (incomplete info, eligibility, duplicate, etc.).
3. Add comments and confirm.

### 7.7 Balance loans across wards

When approving multiple applications in one phase:

1. Open **Approve** and scroll to **Ward allocation balance (this phase)**.
2. Compare each ward's approved total and share % to the equal-share target.
3. Prioritise **under-served wards** before adding more to over-allocated wards.
4. Defer applications in over-allocated wards if needed.

### 7.8 Upload phase documents

On the **phase details** page:

| Document | When required |
|----------|---------------|
| **Signed committee minutes** | Before submit to HQ |
| **Bank reconciliation** | Before submit to HQ |

Upload PDF or Word (max ~2 MB per file). Confirm the file appears in the list after upload.

### 7.9 Submit phase to HQ

1. Open the phase.
2. Confirm **every application** is reviewed (no pending items).
3. Confirm **committee minutes** and **bank reconciliation** are uploaded.
4. Click **Submit to HQ**.
5. Add remarks if prompted and confirm.

The phase is now locked for constituency editing and appears in the HQ queue.

---

## 8. HQ officer — how to process phases

### 8.1 Find submitted phases

1. Log in to the LMS.
2. Go to **HQ → Applications**.
3. Open a phase with status *Submitted to HQ*.

### 8.2 Review applications

For each application verify:

- Approved amount vs applied amount
- GFS code and group code
- Loan type (Wezesha / Endeleza) and loan level
- Ward and constituency remarks

Approve, defer, or return individual items per HQ policy.

### 8.3 Generate or upload processed codelist

1. Open the phase review page.
2. Go to **Generated Files**.
3. Either:
   - Click **Processed Codelist** to download the system-generated Excel, **or**
   - Upload your own processed Excel under **Processed Code List (Excel only)**.

The processed codelist includes GFS codes, loan levels, ward totals, and financial summary (including **Total Approvals** and **Balance in Main Account** when allocations are synced).

### 8.4 Send to Accounts

Once the processed codelist is saved, the phase automatically queues on the **Accounts Dashboard**. Refresh if it does not appear within a few minutes.

### 8.5 Manage constituency allocations

Allocations drive the financial summary on processed codelists.

1. Go to **HQ → Allocations**.
2. Select county and constituency.
3. Enter yearly allocation, transfers, and phase issued amounts — **or**
4. **Download template**, fill offline, and **upload** the completed file.
5. Click **Save** — **Balance in main account** is calculated automatically.

When a constituency submits a phase, the latest phase amount is auto-filled from submitted totals.

### 8.6 After Accounts approval — upload management minutes

1. Open the phase after Accounts has cleared it.
2. Upload **Management Proposal Review Committee minutes** (signed PDF/Word).
3. Confirm the file is attached — the phase then queues for Secretariat.

### 8.7 Return a phase to constituency

1. Open the phase review page.
2. Click **Return to constituency**.
3. Enter remarks explaining required corrections (minimum length may apply).
4. Confirm — the constituency officer is notified to fix and resubmit.

---

## 9. Accountant — how to review phases

### 9.1 Find phases waiting for review

1. Log in — you are redirected to **Accounts Dashboard** (`/accountant/phases`).
2. Open the **Pending review** tab.
3. Phases appear after HQ uploads a **processed codelist**.

> A phase appears in the queue when HQ has uploaded a processed codelist. Bank reconciliation is required **to approve**, not to appear in the list.

### 9.2 Review a phase

1. Click **Review** on a phase row.
2. Download the **processed code list** (HQ upload or **Generate processed codelist**).
3. Download **bank reconciliation** from constituency documents.
4. Compare figures, totals, and group entries line by line.

### 9.3 Approve for HQ management

1. Confirm both documents are correct.
2. Enter **notes for HQ** in the approval box.
3. Click **Approve & send to HQ management**.

Bank reconciliation **must** be on file before approval.

### 9.4 Return to HQ

1. Open the phase review page.
2. Enter **clarification notes** (at least 20 characters).
3. Click **Return to HQ with notes**.

---

## 10. Head of Secretariat — how to approve phases

### 10.1 Find pending phases

1. Log in — open **Secretariat → Pending tasks**.
2. Select a phase from the list.

### 10.2 Review documents

Download and verify:

- Processed codelist (Excel)
- Management proposal review minutes (HQ)
- Constituency committee minutes

### 10.3 Approve and sign

1. Upload your **signature** image.
2. Approve the phase (or **Return to HQ** with remarks if documents are incorrect).
3. After approval, download the **Approved Codelist (PDF)** from Generated Files.

The signed PDF is also available on HQ and constituency phase pages.

---

## 11. After approval — training and disbursement

### Schedule training (constituency)

1. Open the approved phase (status: *Schedule Training* or *Approved*).
2. Go to the **training** section.
3. Enter **training start date**, end date (if required), and **venue**.
4. Save.

Training certificates become available **four days after the training start date**.

### Record disbursement (constituency)

1. Open the phase or individual application after secretariat approval.
2. Click **Mark disbursed** (or open the disbursement modal).
3. Enter:
   - **Cheque number**
   - **Amount issued**
   - **Group identifier**
4. If issued amount ≠ approved amount, provide justification (minimum 20 characters).
5. Confirm and save.

### Download approved codelist (constituency / HQ)

1. Open phase details after secretariat approval.
2. Go to **Generated Files**.
3. Click **Approved Codelist (PDF)**.

---

## 12. Historical data upload

For constituencies migrating legacy loan records, use **Reports → Data upload**. Steps are **sequential** — complete each before the next.

### Step 1 — Import loan phases

1. Download the **Loan Phases** template.
2. Fill exactly two columns: **S/No.** and **Phase*** (e.g. `Phase 1`, `Phase 2`).
3. Upload and confirm success.

### Step 2 — Import groups and loans

1. Download the **Groups / Loans** template.
2. Fill one row per loan disbursement with:
   - Date of disbursement, group name, ward, phase, loan type
   - **GFS code** (must match constituency/county codes)
   - Category, sector, amounts (numbers only — **no commas**)
3. Rules:
   - **One GFS suffix per distinct group** in the file
   - Same group **may** repeat the same GFS across **different phases**
   - Same group **cannot** have two primary loans (New / Resubmission / Reallocation) in the same phase
   - **Refinancing** is the only type allowed multiple times per phase
4. Upload and review success/error messages.

### Step 3 — Import members

1. Download the **Members** template.
2. Map each member to an existing group using **Phase + Loan Type + Group Name + GFS Code**.
3. Every member needs a valid National ID and unique phone number.
4. Upload — members link only to groups that already exist from Step 2.

> **Reference the approved codelist** from treasury records when filling GFS codes and amounts.

---

## 13. GFS codes — rules and data quality

### Rules

| Rule | Detail |
|------|--------|
| Uniqueness | One GFS suffix per group per constituency |
| Persistence | A group's suffix does not change across phases |
| Format | `4-{CCC}-{CCC}-411020{C}-{SSS}{C}-{GGG}` |
| Constituency match | County and constituency codes in GFS must match the uploading constituency |

### Common upload errors

| Error | Cause | Fix |
|-------|-------|-----|
| "GFS suffix already used by a DIFFERENT group" | Two group names share one suffix in Excel | Assign unique suffix to one group; verify against codelist |
| "Wrong county/constituency codes" | GFS copied from another constituency | Regenerate GFS for correct constituency |
| "Group already has an entry in the same phase" | Duplicate row for same group + phase | Remove duplicate or change phase |
| Members rejected — GFS not found | Members uploaded before groups | Complete Step 2 before Step 3 |

### Suspected corrupted GFS in the database

If two **different** groups share one GFS suffix in live data:

1. **Do not** arbitrarily assign a new GFS just to pass upload validation.
2. Determine whether they are **duplicate master records** for the same real group (merge) or **two genuinely different groups** (re-code the wrongly assigned one).
3. Use disbursement records, cheques, ward, and member lists as evidence.
4. Fix database records first, then re-upload.

---

## 14. Loan recommendation formula

When a constituency officer clicks **Approve**, the system calculates a **System Recommended Amount**.

### Formula

```
Recommended = ⅔ × base_amount × repayment_months
```

Where:

- **base_amount** = higher of monthly profit OR monthly table-banking contribution
- **monthly profit** = average monthly income − total monthly expenses
- **repayment_months** = table-banking duration (or proposed repayment period; minimum 1)

### Decision rules

| Condition | Result |
|-----------|--------|
| Monthly loss (negative profit) and no table-banking data | **Decline** |
| Capacity below KES 30,000 | **Decline** |
| Capacity KES 30,000–49,999 | Recommend **KES 50,000** (floor) |
| Wezesha (new group) | Capped at **KES 100,000** |
| Endeleza (returning) | Capped at eligibility maximum from loan history |

The officer may adjust the **Final Approved Amount** within the limits shown in the modal. When the system recommends **Decline**, the confirm button is hidden — reject the application instead.

---

## 15. Reports and exports

### Constituency reports

| Report | Path | Use |
|--------|------|-----|
| Code list / loan sub-form | Reports → Loan sub-form | Phase codelist export |
| Disbursement | Reports → Disbursement | Disbursement tracking |
| Dynamic report | Reports → Dynamic report | Custom filters |
| Data upload | Reports → Data upload | Historical migration |
| Application export | Application review → Export | Institutional data Excel |

### HQ reports

1. Go to **HQ → Reports** or **HQ Reports**.
2. Choose report type and filters.
3. Export to Excel or PDF as offered.

### National group and loan views

HQ menu: **Groups**, **Members**, **Loans** (repayments, phases, receipts, group loans).

---

## 16. Troubleshooting

### Application blocked — uncleared balance

1. Open the application and note the balance shown.
2. Check repayments under **Loans → Receipts** or group loan history.
3. If fully repaid, ask ICT to run a **loan status refresh**.
4. If balance is genuine, reject or defer until repayment is made.

### System recommends decline

1. Read the formula breakdown in the approval modal.
2. Check income vs expenses — a **loss** triggers decline.
3. If table-banking data is missing, ask the group to update their portal application.
4. Do not force-approve when the confirm button is hidden.

### Phase not on Accounts dashboard

1. Confirm phase status is **submitted to HQ**.
2. Confirm HQ uploaded the **processed codelist**.
3. Refresh the Accounts page.
4. If still missing, ask HQ to re-upload the codelist.

### TOTAL APPROVALS = 0 on processed codelist

1. Go to **HQ → Allocations** for that constituency.
2. Save or upload allocation data including **Actual Amount Issued in Phase X**.
3. Re-download the processed codelist.

### Document upload failed

1. Check file type (PDF, XLSX, DOCX as required).
2. Check file size (typically max 2 MB).
3. Refresh and re-upload.
4. Confirm phase status still allows uploads.

### Permission denied

1. Note which page or button triggered the message.
2. Confirm your **role** includes that action.
3. Confirm **phase status** allows the action (e.g. cannot edit after submit to HQ).

---

## 17. Getting help

When reporting a problem, include:

1. **Application ID** or **phase name/slug**
2. Your **role** and **constituency**
3. **Time** of the issue
4. **Screenshot** of the error

Escalate to constituency ICT, HQ LMS admin, or the Uwezo Fund technical team.

---

## Related documentation

| Document | Contents |
|----------|----------|
| [FAQ.md](./FAQ.md) | Quick how-to answers by role |
| [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md) | Server deployment (ICT) |
| [AUTO_SYSTEM_VERIFICATION.md](./AUTO_SYSTEM_VERIFICATION.md) | Automated verification checks |

---

*Last updated: June 2026 · Uwezo Fund LMS v4.x*
