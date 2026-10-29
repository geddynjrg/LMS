<?php

namespace App\Http\Controllers;

use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Online-first JSON APIs for HQ / Secretariat / Accountant mobile screens.
 */
class MobileNationalController extends Controller
{
    public function phases(Request $request)
    {
        $role = $this->roleOf($request->user());
        $query = LoanPhase::query()->orderByDesc('id');

        // Align with live Livewire queues / PhaseWorkflowStatus (DB values), not invented enums.
        if ($role === 'secretariat') {
            $query->whereIn('status', ['approved', 'in_training', 'pending_disbursement']);
        } elseif ($role === 'accountant') {
            $query->where('status', 'submitted_to_hq');
        } elseif ($role === 'headquarters' || $role === 'hq') {
            $query->whereIn('status', ['submitted_to_hq', 'approved', 'in_training']);
        } else {
            return response()->json(['ok' => false, 'detail' => 'This role uses constituency sync, not national lists.'], 403);
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $rows = $query->limit(200)->get([
            'id', 'constituency_code', 'phase', 'phase_ref', 'status',
            'total_amount_applied', 'total_amount_approved', 'amount_disbursed',
            'submitted_to_hq_at', 'hq_approved_at', 'accountant_review_status',
            'hq_remarks', 'accountant_notes', 'created_at', 'updated_at',
        ]);

        return response()->json([
            'ok' => true,
            'role' => $role,
            'rows' => $rows,
            'count' => $rows->count(),
        ]);
    }

    public function applications(Request $request)
    {
        $role = $this->roleOf($request->user());
        if (!in_array($role, ['headquarters', 'hq', 'secretariat', 'accountant'], true)) {
            return response()->json(['ok' => false, 'detail' => 'National applications list is for HQ roles.'], 403);
        }

        $query = PhaseApplication::query()->orderByDesc('id');
        $phaseId = (int) $request->query('phase_id', 0);
        if ($phaseId > 0) {
            $query->where('loan_phase_id', $phaseId);
        }
        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $rows = $query->limit(300)->get([
            'id', 'loan_phase_id', 'group_id', 'group_code', 'gfs_code',
            'amount_applied', 'amount_approved', 'amount_issued', 'status',
            'comments', 'rejection_reason', 'created_at', 'updated_at',
        ]);

        return response()->json(['ok' => true, 'role' => $role, 'rows' => $rows, 'count' => $rows->count()]);
    }

    public function decide(Request $request)
    {
        $user = $request->user();
        $role = $this->roleOf($user);
        $validator = Validator::make($request->all(), [
            'phase_id' => 'required|integer|exists:loan_phases,id',
            'decision' => 'required|in:approve,return',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'detail' => $validator->errors()->first()], 422);
        }

        $phase = LoanPhase::query()->find((int) $request->input('phase_id'));
        if (!$phase) {
            return response()->json(['ok' => false, 'detail' => 'Phase not found.'], 404);
        }

        $decision = (string) $request->input('decision');
        $notes = trim((string) $request->input('notes', ''));

        if ($role === 'secretariat') {
            if ($decision === 'approve') {
                $phase->status = 'hq_approved';
                $phase->hq_approved_at = now();
                $phase->hq_remarks = $notes ?: $phase->hq_remarks;
            } else {
                $phase->status = 'returned_to_hq';
                $phase->hq_remarks = $notes ?: 'Returned by secretariat.';
            }
        } elseif ($role === 'accountant') {
            if ($decision === 'approve') {
                $phase->status = 'ready_for_payment';
                $phase->accountant_review_status = 'approved';
                $phase->accountant_notes = $notes ?: $phase->accountant_notes;
                $phase->accountant_reviewed_at = now();
                $phase->accountant_reviewed_by = $user->id;
            } else {
                $phase->status = 'returned_for_clarification';
                $phase->accountant_review_status = 'returned';
                $phase->accountant_notes = $notes ?: 'Returned for clarification.';
                $phase->accountant_reviewed_at = now();
                $phase->accountant_reviewed_by = $user->id;
            }
        } elseif (in_array($role, ['headquarters', 'hq'], true)) {
            if ($decision === 'approve') {
                $phase->status = 'hq_approved';
                $phase->hq_approved_at = now();
                $phase->hq_remarks = $notes ?: $phase->hq_remarks;
            } else {
                $phase->status = 'returned_to_constituency';
                $phase->hq_remarks = $notes ?: 'Returned to constituency.';
            }
        } else {
            return response()->json(['ok' => false, 'detail' => 'This role cannot decide national phases.'], 403);
        }

        $phase->save();

        return response()->json([
            'ok' => true,
            'detail' => $decision === 'approve' ? 'Phase approved.' : 'Phase returned.',
            'phase' => $phase->only(['id', 'status', 'hq_remarks', 'accountant_notes', 'accountant_review_status']),
        ]);
    }

    private function roleOf(User $user): string
    {
        $profile = AppSessionController::account($user);
        return strtolower((string) ($profile['role'] ?? 'staff'));
    }
}
