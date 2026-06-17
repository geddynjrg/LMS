<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupLoan;
use App\Services\Loan\LoanDebtCheckService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoanHistoryController extends Controller
{
    public function __construct(
        private readonly LoanDebtCheckService $debtCheckService,
        private readonly SmsService $smsService,
    ) {
    }

    public function checkMember(string $nationalId): JsonResponse
    {
        $result = $this->debtCheckService->checkMemberByNationalId($nationalId);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function checkGroup(string $registrationNumber): JsonResponse
    {
        $result = $this->debtCheckService->checkGroupByRegistrationNumber($registrationNumber);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function showGroupLoan(int $groupLoanId): JsonResponse
    {
        $loan = GroupLoan::query()
            ->where('loan_status', 'financed')
            ->with(['group.ward.constituency', 'groupMembers.member'])
            ->find($groupLoanId);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Group loan not found',
                'data' => $this->debtCheckService->checkMemberByNationalId(''),
            ], 404);
        }

        $status = $this->debtCheckService->getDebtStatusForGroupLoan($loan);
        $group = $loan->group;

        return response()->json([
            'success' => true,
            'data' => array_merge($status, [
                'group_loan_id' => $loan->id,
                'group_id' => $loan->group_id,
                'group_name' => $group?->name,
                'group_registration_number' => $group?->reg_number,
                'constituency_name' => $group?->ward?->constituency?->constituency_name,
                'has_debt' => $status['blocks_proceed'],
            ]),
        ]);
    }

    public function sendDebtReminderSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_loan_id' => 'required|integer|exists:group_loans,id',
            'outstanding_balance' => 'required|numeric|min:0',
            'invoice_link' => 'nullable|url|max:2048',
            'application_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $loan = GroupLoan::query()
            ->with(['groupMembers.member'])
            ->findOrFail((int) $request->input('group_loan_id'));

        $outstanding = number_format((float) $request->input('outstanding_balance'), 2);
        $invoiceLink = trim((string) $request->input('invoice_link', ''));
        $applicationId = trim((string) $request->input('application_id', ''));

        $message = "Uwezo Fund reminder: Your group has an outstanding loan balance of KES {$outstanding}. "
            . 'Please repay via Pesaflow to continue with your loan application.';

        if ($invoiceLink !== '') {
            $message .= " Pay here: {$invoiceLink}";
        } elseif ($applicationId !== '') {
            $message .= " Application ref: {$applicationId}.";
        }

        $sent = 0;
        $failed = 0;

        foreach ($loan->groupMembers as $groupMember) {
            $phone = $groupMember->member?->number;
            if (!$phone) {
                $failed++;
                continue;
            }

            if ($this->smsService->send($phone, $message)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        Log::info('Debt reminder SMS dispatched', [
            'group_loan_id' => $loan->id,
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'sms_sent' => $sent,
                'sms_failed' => $failed,
            ],
        ]);
    }

    public function recordRepayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_loan_id' => 'required|integer|exists:group_loans,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'required|string|max:255',
            'bill_ref_number' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                return $this->debtCheckService->recordPortalRepayment(
                    (int) $request->input('group_loan_id'),
                    (float) $request->input('amount'),
                    (string) $request->input('reference_number'),
                    (string) $request->input('bill_ref_number'),
                );
            });

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Portal repayment recording failed', [
                'error' => $e->getMessage(),
                'group_loan_id' => $request->input('group_loan_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
