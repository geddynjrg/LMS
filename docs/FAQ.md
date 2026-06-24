# Uwezo LMS — How-To FAQ

Step-by-step answers for common tasks in the Uwezo Loan Management System. Each item tells you **what to do**, not background definitions.

---

## Getting started

### How do I log in?

1. Open the LMS URL in Chrome, Firefox, or Edge.
2. Enter your **official government email** and password.
3. Click **Enter Portal**.

If login fails, ask your administrator to confirm your account is active and your role is assigned.

### How do I know which dashboard I should use?

After login, the system sends you to the dashboard for your role:

| If you are… | You land on… |
|-------------|--------------|
| Constituency Officer | Constituency dashboard |
| HQ Officer | HQ applications / national dashboard |
| Accountant | Accounts dashboard (`/accountant/phases`) |
| Head of Secretariat | Secretariat pending tasks |

Use the left menu if you need to switch areas (only if your role allows it).

---

## Constituency Officer

### How do I find new applications?

1. Log in and open your **constituency dashboard**.
2. Go to **Applications**.
3. Use filters/tabs to see **submitted**, **under review**, or **assigned to phase** applications.
4. Click an application ID to open the full review page.

### How do I assign applications to a phase?

1. Open **Applications** or **Loans → Phases**.
2. Open the target **phase** (or create one from **Loans → Phases**).
3. From the applications list, select applications and **add to phase**, or open an application and assign it to the phase from the phase selector.
4. Confirm the application appears in the phase application list.

### How do I link a returning group (Endeleza)?

1. Open the application review page.
2. Search for the group by name or registration number.
3. Select the matching **master group** from suggestions.
4. Confirm the link — the system sets loan type to Endeleza and increments the loan level.

### How do I register a new group (Wezesha)?

1. Open the application review page.
2. Confirm no master group match exists.
3. Mark as **new group** / no match.
4. On approval, enter a **group code** (3+ digits, unique in the constituency).
5. The system generates the **GFS code** prefix; the group code completes it.

### How do I approve an application?

1. Open the application.
2. Confirm it is **assigned to a phase**.
3. Link the group (Endeleza) or assign a group code (Wezesha).
4. Assign the **ward** if it is missing.
5. Click **Approve**.
6. Read the **System Recommended Amount** in the modal (profit/table-banking formula).
7. Adjust the **Final Approved Amount** if needed (within the limits shown).
8. Add optional remarks and click **Confirm Approval**.

If the modal shows **Decline**, do not approve — reject the application instead.

### How do I reject an application?

1. Open the application.
2. Click **Reject**.
3. Choose a reason (incomplete info, eligibility, duplicate, etc.).
4. Add comments if prompted.
5. Confirm rejection.

### How do I use the system loan recommendation?

1. Click **Approve** on the application.
2. In the modal, check **System Recommended Amount**:
   - **Decline** → reject the application.
   - **KES amount** → use as your starting point for the final approved amount.
3. Review the formula line (⅔ × base amount × months) and ward balance panel.
4. Enter your final amount and confirm.

### How do I balance loans across wards in a phase?

1. Open **Approve** on an application in the phase.
2. Scroll to **Ward allocation balance (this phase)** in the modal.
3. Compare each ward’s approved total and share % to the equal-share target.
4. Prioritise approvals in **under-served wards** before adding more to wards already above target.
5. Defer (do not approve yet) applications in over-allocated wards if needed.

### How do I upload committee minutes?

1. Open the **phase details** page.
2. Go to the documents section.
3. Click **Upload** for signed committee minutes (PDF/Word).
4. Wait for the success message and confirm the file appears in the list.

### How do I upload bank reconciliation?

1. Open the **phase details** page.
2. Find **Bank reconciliation** under documents.
3. Upload the file from the constituency accounts team.
4. Confirm it shows as on file before submitting to HQ.

### How do I submit a phase to HQ?

1. Open the phase.
2. Confirm **every application** is reviewed (no pending items).
3. Confirm **committee minutes** and **bank reconciliation** are uploaded.
4. Click **Submit to HQ**.
5. Add remarks if prompted and confirm.

After submission, the phase is locked for constituency editing and appears in the HQ queue.

### How do I schedule training for a phase?

1. Open the approved phase.
2. Go to the training section.
3. Enter **training start date**, end date (if required), and **venue**.
4. Save — certificates become available four days after the **start date**.

### How do I record disbursement (issue a cheque)?

1. Open the phase or application after HQ/secretariat approval.
2. Click **Mark disbursed** (or open the disbursement modal).
3. Enter **cheque number**, **amount issued**, and **group identifier**.
4. If issued amount ≠ approved amount, write a justification (minimum 20 characters).
5. Confirm and save.

### How do I download the approved codelist (signed PDF)?

1. Open the **phase details** page after secretariat approval.
2. Go to **Generated Files** or phase documents.
3. Click **Approved Codelist (PDF)** to download.

### How do I export application data to Excel?

1. Open the application review page.
2. Click **Export** (institutional data).
3. Save the downloaded `.xlsx` file.

---

## HQ Officer

### How do I find phases submitted by constituencies?

1. Log in to HQ.
2. Go to **HQ → Applications**.
3. Open a phase from the list (status: submitted to HQ).
4. Review applications, documents, and constituency notes.

### How do I process applications in a submitted phase?

1. Open the phase review page.
2. Review each application (amounts, GFS codes, loan levels, remarks).
3. Approve, defer, or return individual items as per HQ policy.
4. Complete any HQ-specific data entry (GFS codes, board remarks, etc.).

### How do I generate a processed codelist?

1. Open the phase review page.
2. Go to **Generated Files**.
3. Click **Processed Codelist** to download the system-generated Excel, **or**
4. Upload your own processed Excel under **Processed Code List (Excel only)**.

### How do I send a phase to Accounts for review?

1. Finish HQ processing for the phase.
2. Upload the **processed codelist** (Excel).
3. The phase automatically queues on the **Accounts dashboard** once the file is saved.
4. Notify accounts if the phase does not appear within a few minutes (refresh first).

### How do I upload management committee minutes?

1. Open the phase after **accounts has cleared** it.
2. Find **Management proposal review committee minutes**.
3. Upload the signed PDF/Word document.
4. Confirm the file is attached before forwarding to secretariat.

### How do I download the approved codelist after secretariat signs?

1. Open the phase review page.
2. Go to **Generated Files**.
3. Click **Approved Codelist (PDF)** (available only after Head of Secretariat approval).

### How do I manage constituency allocations?

1. Go to **HQ → Allocations**.
2. Select **county** and **constituency**.
3. Enter yearly allocation figures, transfer amounts, and phase issued amounts — **or**
4. Click **Download template**, fill Excel offline, and **upload** the completed file.
5. Click **Save** — **Balance in main account** is calculated automatically.

The latest phase amount is auto-filled when a constituency submits that phase to HQ.

### How do I return a phase to the constituency?

1. Open the phase review page.
2. Click **Return to constituency** (or equivalent action).
3. Enter remarks explaining what must be corrected (minimum length may apply).
4. Confirm — the constituency officer is notified to fix and resubmit.

---

## Accountant

### How do I find phases waiting for my review?

1. Log in — you are redirected to **Accounts Dashboard** (`/accountant/phases`).
2. Open the **Pending review** tab.
3. Phases appear after HQ uploads a **processed codelist**.

### How do I review a phase?

1. Click **Review** on a phase row.
2. Download or open the **processed code list** (HQ upload or **Generate processed codelist**).
3. Download the **bank reconciliation** from constituency documents.
4. Compare figures, totals, and group entries.

### How do I approve a phase for HQ management?

1. Complete your review (both documents correct).
2. Enter **notes for HQ** in the approval box.
3. Click **Approve & send to HQ management**.

Bank reconciliation **must** be on file before you can approve.

### How do I return a phase to HQ?

1. Open the phase review page.
2. Enter **clarification notes** (at least 20 characters) explaining what is wrong.
3. Click **Return to HQ with notes**.
4. HQ follows up with the constituency if needed.

### How do I generate a processed codelist for comparison?

1. Open the phase review page.
2. Click **Generate processed codelist**.
3. Compare the downloaded Excel with the HQ-uploaded version.

---

## Head of Secretariat

### How do I find phases pending my approval?

1. Log in — open **Secretariat → Pending tasks**.
2. Select a phase from the list.

### How do I review and approve a phase?

1. Open the phase **review** page.
2. Download and check:
   - Processed codelist
   - Management proposal review minutes
   - Constituency committee minutes
3. Click **Download processed codelist** if you need the latest Excel.
4. Upload your **signature** image.
5. Approve the phase (or **Return to HQ** with remarks if documents are incorrect).

### How do I download the signed approved codelist?

1. After you approve and sign, click **Download approved codelist** on the review page.
2. The same PDF is also available on HQ and constituency phase pages.

---

## Reports (all roles)

### How do I run a constituency code list report?

1. Go to **Reports → Loan sub-form / Code list** (constituency menu).
2. Select filters (phase, date range, etc.).
3. Click **Generate** or **Export**.
4. Save the file.

### How do I run HQ national reports?

1. Log in as HQ.
2. Go to **HQ → Reports** or **HQ Reports**.
3. Choose the report type and filters.
4. Export to Excel or PDF as offered.

### How do I upload legacy loan data (constituency)?

1. Go to **Reports → Data upload**.
2. Download the template if provided.
3. Fill data offline (use numbers only — no commas in amounts).
4. Upload the completed file and confirm success messages.

---

## Troubleshooting — how to fix common problems

### How do I fix “application blocked — uncleared balance”?

1. Open the application and note the balance shown.
2. Confirm repayments in **Loans → Receipts** or group loan history.
3. If the group has fully repaid, ask ICT to run a **loan status refresh** on the group.
4. If balance is genuine, **reject** or defer until repayment is made.

### How do I fix “system recommends decline” on approval?

1. Open the approval modal and read the formula breakdown.
2. Check monthly income vs expenses on the application — a **loss** triggers decline.
3. If table banking data is missing, ask the group to update their portal application.
4. If capacity is below KES 30,000, reject the application.
5. Do not force-approve when the confirm button is hidden.

### How do I fix a phase not showing on the Accounts dashboard?

1. Confirm the phase status is **submitted to HQ**.
2. Confirm HQ has uploaded the **processed codelist**.
3. Refresh the Accounts page.
4. If still missing, ask HQ to re-upload the codelist or contact LMS support.

### How do I fix TOTAL APPROVALS = 0 on a processed codelist?

1. Go to **HQ → Allocations** for that constituency.
2. Save or upload allocation data including **Actual Amount Issued in Phase X** rows.
3. Re-download the processed codelist — totals sync from submitted phase amounts.

### How do I fix a document upload that did not save?

1. Check file type (PDF, XLSX, DOCX as required).
2. Check file size (many documents max 2 MB).
3. Refresh the page and re-upload.
4. Confirm the phase is still in a status that allows uploads (not closed).

### How do I fix “Permission Denied”?

1. Note which button or page triggered the message.
2. Confirm your **role** includes that action.
3. Confirm the **phase status** allows the action (e.g. cannot edit after submit to HQ).
4. Contact your administrator with the page URL and screenshot.

### How do I report a problem to support?

1. Copy the **application ID** or **phase name/slug**.
2. Note your **role**, **constituency**, and **time** of the issue.
3. Take a **screenshot** of the error.
4. Email or escalate to constituency ICT, HQ LMS admin, or Uwezo Fund technical team.

---

## Quick reference — end-to-end flow

```
Group applies (portal)
    → Constituency reviews & approves applications in a phase
    → Constituency uploads minutes + bank recon
    → Constituency submits phase to HQ
    → HQ processes & uploads processed codelist
    → Accounts reviews & clears to HQ management
    → HQ uploads management minutes
    → Head of Secretariat signs & approves
    → Constituency schedules training
    → Constituency records disbursement (cheque)
```

---

*Last updated: June 2026 · Uwezo Fund LMS v4.x*
