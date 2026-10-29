<?php

namespace App\Http\Controllers;

use App\Models\Constituency;
use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\LoanPhase;
use App\Models\LoanReceipts;
use App\Models\Member;
use App\Models\PhaseApplication;
use App\Jobs\SyncApplicationStatusToDrupal;
use App\Models\TempApplication;
use App\Models\User;
use App\Models\Wards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Constituency-scoped LMS snapshot + mutation sync for the Uwezo Fund mobile app.
 */
class MobileSyncController extends Controller
{
    public function snapshot(Request $request)
    {
        $user = $request->user();
        $constituency = $this->resolveConstituency($user, (string) $request->query('constituency', ''));
        if (!$constituency) {
            return response()->json([
                'ok' => false,
                'detail' => 'No constituency is assigned for offline sync. Headquarters roles should use the live LMS.',
            ], 403);
        }

        $code = (string) $constituency->constituency_code;
        $wardIds = Wards::query()->where('constituency_code', $code)->pluck('id');
        $wards = Wards::query()
            ->where('constituency_code', $code)
            ->get(['id', 'constituency_code', 'ward_name', 'created_at', 'updated_at']);

        $groups = Group::query()
            ->whereIn('ward_id', $wardIds)
            ->get([
                'id', 'ward_id', 'name', 'slug', 'identifier', 'code', 'reg_number',
                'date_formed', 'date_registered', 'bank', 'account', 'location', 'box', 'status',
                'created_at', 'updated_at',
            ]);

        $groupIds = $groups->pluck('id');

        $phases = LoanPhase::query()
            ->where('constituency_code', $code)
            ->get([
                'id', 'phase_ref', 'constituency_code', 'phase', 'slug', 'status',
                'total_amount_applied', 'total_amount_approved', 'amount_disbursed', 'amount_repaid',
                'submitted_to_hq_at', 'hq_approved_at', 'disbursed_at', 'is_locked',
                'created_at', 'updated_at',
            ]);

        $phaseIds = $phases->pluck('id');

        $loans = GroupLoan::query()
            ->where(function ($q) use ($groupIds, $phaseIds) {
                $q->whereIn('group_id', $groupIds);
                if ($phaseIds->isNotEmpty()) {
                    $q->orWhereIn('loan_phase_id', $phaseIds);
                }
            })
            ->get([
                'id', 'loan_phase_id', 'group_id', 'gfs_code', 'category', 'sector', 'sector_desc',
                'loan_type', 'status', 'loan_status', 'loan_level', 'application_date',
                'disbursement_date', 'cheque_no', 'amount_applied', 'amount_approved',
                'amount_issued', 'amount_repaid', 'is_defaulted', 'months_defaulted',
                'total_arrears', 'amount_due', 'created_at', 'updated_at',
            ]);

        $loanIds = $loans->pluck('id');

        $receipts = $loanIds->isEmpty()
            ? collect()
            : LoanReceipts::query()
                ->whereIn('group_loans_id', $loanIds)
                ->get([
                    'id', 'group_loans_id', 'transaction_date', 'amount', 'receipt_number',
                    'payment', 'reference_number', 'status', 'void_reason', 'created_at', 'updated_at',
                ]);

        $applications = $phaseIds->isEmpty()
            ? collect()
            : PhaseApplication::query()
                ->whereIn('loan_phase_id', $phaseIds)
                ->get([
                    'id', 'loan_phase_id', 'temp_application_id', 'group_id', 'group_loan_id',
                    'loan_level', 'group_code', 'gfs_code', 'amount_applied', 'amount_approved',
                    'amount_issued', 'status', 'is_trained', 'recommendation', 'comments',
                    'created_at', 'updated_at',
                ]);

        // Human-facing application numbers live on temp_applications.application_id.
        if ($applications->isNotEmpty()) {
            $tempIds = $applications->pluck('temp_application_id')->filter()->unique()->values();
            $tempNumbers = $tempIds->isEmpty()
                ? collect()
                : TempApplication::query()
                    ->whereIn('id', $tempIds)
                    ->get(['id', 'application_id'])
                    ->keyBy('id');
            $applications = $applications->map(function (PhaseApplication $app) use ($tempNumbers) {
                $row = $app->toArray();
                $temp = $app->temp_application_id ? $tempNumbers->get($app->temp_application_id) : null;
                $number = $temp ? (string) ($temp->application_id ?? '') : '';
                $row['application_number'] = $number;
                $row['application_id'] = $number !== '' ? $number : null;
                return $row;
            })->values();
        }

        $groupMembers = $loanIds->isEmpty()
            ? collect()
            : GroupMember::query()
                ->whereIn('group_loans_id', $loanIds)
                ->with(['groupLoan:id,group_id'])
                ->get(['id', 'member_Id', 'group_loans_id', 'role', 'created_at', 'updated_at'])
                ->map(function (GroupMember $gm) {
                    return [
                        'id' => $gm->id,
                        'member_Id' => $gm->member_Id,
                        'group_loans_id' => $gm->group_loans_id,
                        'group_id' => $gm->groupLoan->group_id ?? null,
                        'role' => $gm->role,
                        'created_at' => $gm->created_at,
                        'updated_at' => $gm->updated_at,
                    ];
                })
                ->values();

        $memberKeys = $groupMembers->pluck('member_Id')->filter()->unique()->values();
        $members = $memberKeys->isEmpty()
            ? collect()
            : Member::query()
                ->where(function ($q) use ($memberKeys) {
                    $q->whereIn('member_Id', $memberKeys)->orWhereIn('id', $memberKeys);
                })
                ->get(['id', 'name', 'member_Id', 'number', 'gender', 'disabled', 'created_at', 'updated_at']);

        $profile = AppSessionController::account($user);

        return response()->json([
            'ok' => true,
            'fetchedAt' => now()->toIso8601String(),
            'constituencyCode' => $code,
            'constituencyName' => (string) $constituency->constituency_name,
            'role' => $profile['role'] ?? 'staff',
            'email' => $user->email,
            'tables' => [
                'wards' => $wards,
                'groups' => $groups,
                'loan_phases' => $phases,
                'group_loans' => $loans,
                'loan_receipts' => $receipts,
                'phase_applications' => $applications,
                'group_members' => $groupMembers,
                'members' => $members,
            ],
            'counts' => [
                'wards' => $wards->count(),
                'groups' => $groups->count(),
                'loan_phases' => $phases->count(),
                'group_loans' => $loans->count(),
                'loan_receipts' => $receipts->count(),
                'phase_applications' => $applications->count(),
                'group_members' => $groupMembers->count(),
                'members' => $members->count(),
            ],
        ]);
    }

    public function mutations(Request $request)
    {
        $user = $request->user();
        $constituency = $this->resolveConstituency($user, (string) $request->input('constituency', ''));
        if (!$constituency) {
            return response()->json(['ok' => false, 'detail' => 'No constituency for sync.'], 403);
        }

        $items = $request->input('mutations', []);
        if (!is_array($items) || $items === []) {
            return response()->json(['ok' => false, 'detail' => 'No mutations to apply.'], 422);
        }

        $results = [];
        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? Str::uuid());
            $type = (string) ($item['type'] ?? '');
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
            try {
                $handlers = [
                    'receipt.create' => 'createReceipt',
                    'receipt.update' => 'updateReceipt',
                    'receipt.void' => 'voidReceipt',
                    'member.update' => 'updateMember',
                    'member.create' => 'createMember',
                    'member.delete' => 'deleteMember',
                    'group.update' => 'updateGroup',
                    'application.approve' => 'approveApplication',
                    'application.reject' => 'rejectApplication',
                    'application.link' => 'linkApplication',
                    'application.unlink' => 'unlinkApplication',
                    'application.assign_phase' => 'assignApplicationPhase',
                    'application.remove_phase' => 'removeApplicationPhase',
                    'application.return' => 'returnApplication',
                    'application.review' => 'reviewApplication',
                    'phase.create' => 'createPhase',
                    'phase.update' => 'updatePhase',
                    'phase.submit' => 'submitPhase',
                ];
                if (isset($handlers[$type])) {
                    $method = $handlers[$type];
                    $results[] = array_merge(['id' => $id, 'type' => $type], $this->{$method}($constituency, $payload));
                } else {
                    $results[] = ['id' => $id, 'type' => $type, 'ok' => false, 'detail' => 'Unsupported mutation type.'];
                }
            } catch (\Throwable $e) {
                $results[] = ['id' => $id, 'type' => $type, 'ok' => false, 'detail' => 'Could not apply this change.'];
            }
        }

        return response()->json([
            'ok' => true,
            'results' => $results,
            'accepted' => collect($results)->where('ok', true)->count(),
            'rejected' => collect($results)->where('ok', false)->count(),
        ]);
    }

    private function createReceipt(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'group_loans_id' => 'required|integer|exists:group_loans,id',
            'receipt_number' => 'required|string|max:64',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'payment' => 'required|in:Bank,Mpesa',
            'reference_number' => 'required|string|max:128',
            'client_key' => 'nullable|string|max:128',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }

        $loanId = (int) $payload['group_loans_id'];
        $receiptNumber = strtoupper(trim((string) $payload['receipt_number']));
        $clientKey = trim((string) ($payload['client_key'] ?? ''));

        $loan = GroupLoan::query()
            ->where('id', $loanId)
            ->whereHas('loanPhase', fn ($q) => $q->where('constituency_code', $constituency->constituency_code))
            ->first();
        if (!$loan) {
            $wardIds = Wards::query()->where('constituency_code', $constituency->constituency_code)->pluck('id');
            $loan = GroupLoan::query()
                ->where('id', $loanId)
                ->whereHas('group', fn ($q) => $q->whereIn('ward_id', $wardIds))
                ->first();
        }
        if (!$loan) {
            return ['ok' => false, 'detail' => 'That loan is not in your constituency.'];
        }

        if ($clientKey !== '') {
            $existingKey = DB::table('idempotency_keys')->where('key', $clientKey)->first();
            if ($existingKey && ($existingKey->status ?? '') === 'completed') {
                $payloadOld = json_decode((string) ($existingKey->request_payload ?? ''), true) ?: [];
                return ['ok' => true, 'detail' => 'Already synced.', 'receipt_id' => $payloadOld['receipt_id'] ?? null, 'duplicate' => true];
            }
        }

        $exists = LoanReceipts::query()
            ->where('receipt_number', $receiptNumber)
            ->whereHas('groupLoan.loanPhase', fn ($q) => $q->where('constituency_code', $constituency->constituency_code))
            ->exists();
        if ($exists) {
            return ['ok' => false, 'detail' => 'The receipt number is already recorded.'];
        }

        return DB::transaction(function () use ($loan, $payload, $receiptNumber, $clientKey) {
            $receipt = new LoanReceipts();
            $receipt->group_loans_id = $loan->id;
            $receipt->receipt_number = $receiptNumber;
            $receipt->transaction_date = $payload['transaction_date'];
            $receipt->amount = $payload['amount'];
            $receipt->payment = $payload['payment'];
            $receipt->reference_number = strtoupper(trim((string) $payload['reference_number']));
            $receipt->status = 'active';
            $receipt->save();

            $loan->amount_repaid = (float) $loan->amount_repaid + (float) $payload['amount'];
            $loan->save();

            if ($clientKey !== '') {
                try {
                    DB::table('idempotency_keys')->updateOrInsert(
                        ['key' => $clientKey],
                        [
                            'status' => 'completed',
                            'user_id' => auth()->id(),
                            'request_payload' => json_encode(['receipt_id' => $receipt->id, 'type' => 'receipt.create']),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            return [
                'ok' => true,
                'detail' => 'Receipt saved on LMS.',
                'receipt_id' => $receipt->id,
            ];
        });
    }


    private function updateMember(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:members,id',
            'name' => 'required|string|max:255',
            'number' => 'nullable|string|max:32',
            'gender' => 'nullable|in:Male,Female,M,F,male,female',
            'disabled' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }

        $member = Member::query()->find((int) $payload['id']);
        if (!$member) {
            return ['ok' => false, 'detail' => 'Member not found.'];
        }

        $wardIds = Wards::query()->where('constituency_code', $constituency->constituency_code)->pluck('id');
        $inConstituency = GroupMember::query()
            ->where(function ($q) use ($member) {
                $q->where('member_Id', $member->member_Id)->orWhere('member_Id', $member->id);
            })
            ->whereHas('groupLoan.group', fn ($q) => $q->whereIn('ward_id', $wardIds))
            ->exists();
        if (!$inConstituency) {
            $inConstituency = GroupMember::query()
                ->where(function ($q) use ($member) {
                    $q->where('member_Id', $member->member_Id)->orWhere('member_Id', $member->id);
                })
                ->whereHas('groupLoan.loanPhase', fn ($q) => $q->where('constituency_code', $constituency->constituency_code))
                ->exists();
        }
        if (!$inConstituency) {
            return ['ok' => false, 'detail' => 'That member is not in your constituency.'];
        }

        $gender = trim((string) ($payload['gender'] ?? $member->gender ?? ''));
        if (strcasecmp($gender, 'm') === 0 || strcasecmp($gender, 'male') === 0) {
            $gender = 'Male';
        } elseif (strcasecmp($gender, 'f') === 0 || strcasecmp($gender, 'female') === 0) {
            $gender = 'Female';
        }

        $member->name = trim((string) $payload['name']);
        if (array_key_exists('number', $payload)) {
            $member->number = trim((string) $payload['number']);
        }
        if ($gender !== '') {
            $member->gender = $gender;
        }
        if (array_key_exists('disabled', $payload)) {
            $member->disabled = (bool) $payload['disabled'];
        }
        $member->save();

        return ['ok' => true, 'detail' => 'Member updated on LMS.', 'member_id' => $member->id];
    }

    private function updateGroup(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:groups,id',
            'name' => 'required|string|max:255',
            'bank' => 'nullable|string|max:128',
            'account' => 'nullable|string|max:64',
            'location' => 'nullable|string|max:255',
            'box' => 'nullable|string|max:64',
            'status' => 'nullable|string|max:64',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }

        $wardIds = Wards::query()->where('constituency_code', $constituency->constituency_code)->pluck('id');
        $group = Group::query()
            ->where('id', (int) $payload['id'])
            ->whereIn('ward_id', $wardIds)
            ->first();
        if (!$group) {
            return ['ok' => false, 'detail' => 'That group is not in your constituency.'];
        }

        $group->name = trim((string) $payload['name']);
        foreach (['bank', 'account', 'location', 'box', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $group->{$field} = trim((string) ($payload[$field] ?? ''));
            }
        }
        $group->save();

        return ['ok' => true, 'detail' => 'Group updated on LMS.', 'group_id' => $group->id];
    }


    private function updateReceipt(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:loan_receipts,id',
            'receipt_number' => 'required|string|max:64',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'payment' => 'required|in:Bank,Mpesa',
            'reference_number' => 'required|string|max:128',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }

        $receipt = LoanReceipts::query()->find((int) $payload['id']);
        if (!$receipt || $receipt->status !== 'active') {
            return ['ok' => false, 'detail' => 'Only active receipts can be edited.'];
        }
        $loan = GroupLoan::query()->find($receipt->group_loans_id);
        if (!$loan || !$this->loanInConstituency($loan, $constituency)) {
            return ['ok' => false, 'detail' => 'That receipt is not in your constituency.'];
        }

        return DB::transaction(function () use ($receipt, $loan, $payload) {
            $oldAmount = (float) $receipt->amount;
            $newAmount = (float) $payload['amount'];
            $receipt->receipt_number = strtoupper(trim((string) $payload['receipt_number']));
            $receipt->transaction_date = $payload['transaction_date'];
            $receipt->amount = $newAmount;
            $receipt->payment = $payload['payment'];
            $receipt->reference_number = strtoupper(trim((string) $payload['reference_number']));
            $receipt->save();
            $loan->amount_repaid = max(0, (float) $loan->amount_repaid - $oldAmount + $newAmount);
            $loan->save();
            return ['ok' => true, 'detail' => 'Receipt updated on LMS.', 'receipt_id' => $receipt->id];
        });
    }

    private function voidReceipt(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:loan_receipts,id',
            'void_reason' => 'required|string|min:10|max:500',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $receipt = LoanReceipts::query()->find((int) $payload['id']);
        if (!$receipt || $receipt->status !== 'active') {
            return ['ok' => false, 'detail' => 'Only active receipts can be voided.'];
        }
        $loan = GroupLoan::query()->find($receipt->group_loans_id);
        if (!$loan || !$this->loanInConstituency($loan, $constituency)) {
            return ['ok' => false, 'detail' => 'That receipt is not in your constituency.'];
        }
        return DB::transaction(function () use ($receipt, $loan, $payload) {
            $receipt->status = 'canceled';
            $receipt->void_reason = trim((string) $payload['void_reason']);
            $receipt->save();
            $loan->amount_repaid = max(0, (float) $loan->amount_repaid - (float) $receipt->amount);
            $loan->save();
            return ['ok' => true, 'detail' => 'Receipt voided on LMS.', 'receipt_id' => $receipt->id];
        });
    }

    private function createMember(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'group_loans_id' => 'required|integer|exists:group_loans,id',
            'name' => 'required|string|max:255',
            'member_Id' => 'required|string|max:64',
            'number' => 'nullable|string|max:32',
            'gender' => 'nullable|string|max:16',
            'role' => 'nullable|string|max:64',
            'disabled' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $loan = GroupLoan::query()->find((int) $payload['group_loans_id']);
        if (!$loan || !$this->loanInConstituency($loan, $constituency)) {
            return ['ok' => false, 'detail' => 'That loan is not in your constituency.'];
        }
        return DB::transaction(function () use ($loan, $payload) {
            $memberId = trim((string) $payload['member_Id']);
            $member = Member::query()->where('member_Id', $memberId)->first();
            if (!$member) {
                $member = new Member();
                $member->member_Id = $memberId;
            }
            $member->name = trim((string) $payload['name']);
            $member->number = trim((string) ($payload['number'] ?? ''));
            $gender = trim((string) ($payload['gender'] ?? 'Male'));
            $member->gender = strcasecmp($gender, 'f') === 0 || strcasecmp($gender, 'female') === 0 ? 'Female' : 'Male';
            $member->disabled = (bool) ($payload['disabled'] ?? false);
            $member->save();
            $link = GroupMember::query()
                ->where('group_loans_id', $loan->id)
                ->where('member_Id', $member->member_Id)
                ->first();
            if (!$link) {
                $link = new GroupMember();
                $link->group_loans_id = $loan->id;
                $link->member_Id = $member->member_Id;
            }
            $link->role = trim((string) ($payload['role'] ?? 'Member')) ?: 'Member';
            $link->save();
            return ['ok' => true, 'detail' => 'Member added on LMS.', 'member_id' => $member->id, 'group_member_id' => $link->id];
        });
    }

    private function deleteMember(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'group_member_id' => 'required|integer|exists:group_members,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $link = GroupMember::query()->find((int) $payload['group_member_id']);
        if (!$link) {
            return ['ok' => false, 'detail' => 'Member link not found.'];
        }
        $loan = GroupLoan::query()->find($link->group_loans_id);
        if (!$loan || !$this->loanInConstituency($loan, $constituency)) {
            return ['ok' => false, 'detail' => 'That member is not in your constituency.'];
        }
        $link->delete();
        return ['ok' => true, 'detail' => 'Member removed from the loan group on LMS.'];
    }

    private function approveApplication(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
            'amount_approved' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string|max:500',
            'group_id' => 'nullable|integer|exists:groups,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        if (array_key_exists('group_id', $payload) && $payload['group_id']) {
            $wardIds = Wards::query()->where('constituency_code', $constituency->constituency_code)->pluck('id');
            $group = Group::query()->where('id', (int) $payload['group_id'])->whereIn('ward_id', $wardIds)->first();
            if (!$group) {
                return ['ok' => false, 'detail' => 'Selected group is not in your constituency.'];
            }
            $app->group_id = $group->id;
            $app->group_code = $group->code;
        }
        $approved = $payload['amount_approved'] ?? $app->amount_approved ?? $app->amount_applied;
        $app->amount_approved = $approved;
        $app->status = 'pending_submission_to_hq';
        $app->level = 'constituency';
        $app->action = 'approved_at_constituency';
        $app->user_id = auth()->id();
        if (!empty($payload['comments'])) {
            $app->comments = trim((string) $payload['comments']);
        }
        $app->recommendation = $app->recommendation ?: 'recommended';
        $app->save();
        return ['ok' => true, 'detail' => 'Application approved at constituency.', 'application_id' => $app->id];
    }

    private function rejectApplication(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
            'rejection_reason' => 'required|string|max:128',
            'rejection_message' => 'required|string|min:10|max:500',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        $app->status = 'rejected';
        $app->level = 'constituency';
        $app->action = 'declined_at_constituency';
        $app->rejection_reason = trim((string) $payload['rejection_reason']);
        $app->rejection_message = trim((string) $payload['rejection_message']);
        $app->user_id = auth()->id();
        $app->save();
        return ['ok' => true, 'detail' => 'Application rejected.', 'application_id' => $app->id];
    }

    private function linkApplication(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
            'group_id' => 'required|integer|exists:groups,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        $wardIds = Wards::query()->where('constituency_code', $constituency->constituency_code)->pluck('id');
        $group = Group::query()->where('id', (int) $payload['group_id'])->whereIn('ward_id', $wardIds)->first();
        if (!$group) {
            return ['ok' => false, 'detail' => 'Selected group is not in your constituency.'];
        }
        $app->group_id = $group->id;
        $app->group_code = $group->code;
        $app->save();
        return ['ok' => true, 'detail' => 'Application linked to group.', 'application_id' => $app->id, 'group_id' => $group->id];
    }



    private function unlinkApplication(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        if (!$app->group_id) {
            return ['ok' => false, 'detail' => 'This application is not linked to a master group.'];
        }
        $app->group_id = null;
        $app->group_code = null;
        $app->save();
        return ['ok' => true, 'detail' => 'Application unlinked from master group.', 'application_id' => $app->id];
    }

    private function assignApplicationPhase(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
            'loan_phase_id' => 'required|integer|exists:loan_phases,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        $phase = LoanPhase::query()
            ->where('id', (int) $payload['loan_phase_id'])
            ->where('constituency_code', $constituency->constituency_code)
            ->first();
        if (!$phase) {
            return ['ok' => false, 'detail' => 'Phase not found in your constituency.'];
        }
        if (in_array($phase->status, ['submitted', 'hq_approved', 'disbursed'], true)) {
            return ['ok' => false, 'detail' => 'This phase can no longer accept new applications from the phone.'];
        }
        $app->loan_phase_id = $phase->id;
        $app->save();
        return ['ok' => true, 'detail' => 'Application assigned to phase.', 'application_id' => $app->id, 'loan_phase_id' => $phase->id];
    }

    private function removeApplicationPhase(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        if (!$app->loan_phase_id) {
            return ['ok' => false, 'detail' => 'This application is not assigned to a phase.'];
        }
        $phase = LoanPhase::query()->find($app->loan_phase_id);
        if ($phase && in_array($phase->status, ['submitted', 'hq_approved', 'disbursed'], true)) {
            return ['ok' => false, 'detail' => 'Cannot remove applications from a submitted or closed phase.'];
        }
        $app->loan_phase_id = null;
        $app->save();
        return ['ok' => true, 'detail' => 'Application removed from phase.', 'application_id' => $app->id];
    }

    private function returnApplication(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
            'comment' => 'required|string|min:20|max:500',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        if ($app->status !== 'pending') {
            return ['ok' => false, 'detail' => 'Only applications awaiting constituency decision can be returned to the group.'];
        }
        $comment = trim((string) $payload['comment']);
        DB::transaction(function () use ($app, $comment) {
            $app->status = 'deferred';
            $app->level = 'constituency';
            $app->action = 'returned_to_applicant';
            $app->rejection_reason = 'incomplete_information';
            $app->rejection_message = $comment;
            $app->comments = $comment;
            $app->user_id = auth()->id();
            $app->save();

            if ($app->temp_application_id) {
                $temp = TempApplication::query()->find($app->temp_application_id);
                if ($temp) {
                    $temp->status = 'draft';
                    $temp->lms_to_loan_app_sync_status = 'pending';
                    $temp->save();
                    if (class_exists(SyncApplicationStatusToDrupal::class)) {
                        SyncApplicationStatusToDrupal::pushAfterCommit($temp->id);
                    }
                }
            }
        });
        return ['ok' => true, 'detail' => 'Application returned to group for correction.', 'application_id' => $app->id];
    }

    private function reviewApplication(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:phase_applications,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $app = PhaseApplication::query()->find((int) $payload['id']);
        if (!$app || !$this->phaseAppInConstituency($app, $constituency)) {
            return ['ok' => false, 'detail' => 'That application is not in your constituency.'];
        }
        if (!in_array($app->status, ['rejected', 'pending_submission_to_hq'], true)) {
            return ['ok' => false, 'detail' => 'Current status does not allow reviewing the decision.'];
        }
        DB::transaction(function () use ($app) {
            $app->status = 'pending';
            $app->level = 'constituency';
            $app->action = 'review';
            $app->amount_approved = 0;
            $app->comments = null;
            $app->rejection_reason = null;
            $app->rejection_message = null;
            $app->user_id = auth()->id();
            $app->save();

            if ($app->temp_application_id) {
                $temp = TempApplication::query()->find($app->temp_application_id);
                if ($temp) {
                    $temp->status = 'under_constituency_review';
                    $temp->lms_to_loan_app_sync_status = 'pending';
                    $temp->risk_level = null;
                    $temp->assessment_data = null;
                    $temp->save();
                    if (class_exists(SyncApplicationStatusToDrupal::class)) {
                        SyncApplicationStatusToDrupal::pushAfterCommit($temp->id);
                    }
                }
            }
        });
        return ['ok' => true, 'detail' => 'Decision reviewed — application is pending again.', 'application_id' => $app->id];
    }

    private function createPhase(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'phase' => 'required|string|max:128',
            'phase_ref' => 'nullable|string|max:64',
            'review_date' => 'nullable|date',
            'review_venue' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $phase = new LoanPhase();
        $phase->constituency_code = $constituency->constituency_code;
        $phase->phase = trim((string) $payload['phase']);
        $phase->phase_ref = trim((string) ($payload['phase_ref'] ?? '')) ?: ('MOB-' . now()->format('YmdHis'));
        $phase->slug = Str::slug($phase->phase . '-' . $phase->phase_ref);
        $phase->status = 'draft';
        $phase->review_date = $payload['review_date'] ?? null;
        $phase->review_venue = trim((string) ($payload['review_venue'] ?? ''));
        $phase->constituency_officer_id = auth()->id();
        $phase->save();
        return ['ok' => true, 'detail' => 'Phase created on LMS.', 'phase_id' => $phase->id];
    }

    private function updatePhase(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:loan_phases,id',
            'phase' => 'required|string|max:128',
            'review_date' => 'nullable|date',
            'review_venue' => 'nullable|string|max:255',
            'committee_remarks' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $phase = LoanPhase::query()
            ->where('id', (int) $payload['id'])
            ->where('constituency_code', $constituency->constituency_code)
            ->first();
        if (!$phase) {
            return ['ok' => false, 'detail' => 'Phase not found in your constituency.'];
        }
        if (in_array($phase->status, ['submitted', 'hq_approved', 'disbursed'], true)) {
            return ['ok' => false, 'detail' => 'This phase can no longer be edited from the phone.'];
        }
        $phase->phase = trim((string) $payload['phase']);
        if (array_key_exists('review_date', $payload)) {
            $phase->review_date = $payload['review_date'];
        }
        if (array_key_exists('review_venue', $payload)) {
            $phase->review_venue = trim((string) ($payload['review_venue'] ?? ''));
        }
        if (array_key_exists('committee_remarks', $payload)) {
            $phase->committee_remarks = trim((string) ($payload['committee_remarks'] ?? ''));
        }
        $phase->save();
        return ['ok' => true, 'detail' => 'Phase updated on LMS.', 'phase_id' => $phase->id];
    }

    private function submitPhase(Constituency $constituency, array $payload): array
    {
        $validator = Validator::make($payload, [
            'id' => 'required|integer|exists:loan_phases,id',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'detail' => $validator->errors()->first()];
        }
        $phase = LoanPhase::query()
            ->where('id', (int) $payload['id'])
            ->where('constituency_code', $constituency->constituency_code)
            ->first();
        if (!$phase) {
            return ['ok' => false, 'detail' => 'Phase not found in your constituency.'];
        }
        $approvedCount = PhaseApplication::query()
            ->where('loan_phase_id', $phase->id)
            ->whereIn('status', ['pending_submission_to_hq', 'approved', 'submitted'])
            ->count();
        if ($approvedCount < 1) {
            return ['ok' => false, 'detail' => 'Approve at least one application before submitting the phase to HQ.'];
        }
        $phase->status = 'submitted';
        $phase->submitted_to_hq_at = now();
        $phase->save();
        PhaseApplication::query()
            ->where('loan_phase_id', $phase->id)
            ->where('status', 'pending_submission_to_hq')
            ->update(['status' => 'submitted', 'level' => 'hq', 'action' => 'submitted_to_hq']);
        return ['ok' => true, 'detail' => 'Phase submitted to HQ.', 'phase_id' => $phase->id];
    }

    private function loanInConstituency(GroupLoan $loan, Constituency $constituency): bool
    {
        $wardIds = Wards::query()->where('constituency_code', $constituency->constituency_code)->pluck('id');
        if ($loan->group_id && Group::query()->where('id', $loan->group_id)->whereIn('ward_id', $wardIds)->exists()) {
            return true;
        }
        return LoanPhase::query()
            ->where('id', $loan->loan_phase_id)
            ->where('constituency_code', $constituency->constituency_code)
            ->exists();
    }

    private function phaseAppInConstituency(PhaseApplication $app, Constituency $constituency): bool
    {
        return LoanPhase::query()
            ->where('id', $app->loan_phase_id)
            ->where('constituency_code', $constituency->constituency_code)
            ->exists();
    }

    private function resolveConstituency(User $user, string $requestedName): ?Constituency
    {
        $current = $user->currentConstituency()->first()
            ?: $user->constituencies()->first();
        if (!$current) {
            return null;
        }
        if ($requestedName !== '') {
            $match = $user->constituencies()
                ->where(function ($q) use ($requestedName) {
                    $q->where('constituency_name', $requestedName)
                        ->orWhere('constituency_code', $requestedName);
                })
                ->first();
            if ($match) {
                return $match;
            }
        }
        return $current;
    }
}
