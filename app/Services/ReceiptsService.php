<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\LoanReceipts;
use App\Models\Group;
use App\Models\GroupLoan;
use App\Jobs\UpdateLoanDefaultStatus;
use App\Services\Cache\ReceiptsCacheService;
use App\Services\IdempotencyService;
use App\Services\Group\GroupsService;
use App\Services\Cache\AppCacheService;

class ReceiptsService
{
    protected ReceiptsCacheService $receiptsCacheService;
    protected IdempotencyService $idempotencyService;
    protected GroupsService $groupsService;
    protected AppCacheService $cacheService;

    public function __construct(
        ReceiptsCacheService $receiptsCacheService,
        IdempotencyService $idempotencyService,
        GroupsService $groupsService,
        AppCacheService $appCacheService
        )
    {
        $this->receiptsCacheService = $receiptsCacheService;
        $this->idempotencyService = $idempotencyService;
        $this->groupsService = $groupsService;
        $this->cacheService = $appCacheService;
    }

    public function getBaseQuery(?string $constituencyCode = null)
    {
        $query = DB::table('loan_receipts as lr')
            ->join('group_loans as gl', 'lr.group_loans_id', '=', 'gl.id')
            ->join('groups as g', 'gl.group_id', '=', 'g.id')
            ->join('loan_phases as lp', 'gl.loan_phase_id', '=', 'lp.id')
            ->join('constituencies as c', 'lp.constituency_code', '=', 'c.constituency_code')
            ->join('counties as co', 'c.county_code', '=', 'co.county_code');

        if ($constituencyCode) {
            $query->where('lp.constituency_code', $constituencyCode);
        }

        return $query;
    }

    public function getDashboardStats(?string $constituencyCode = null): array
    {
        $today = now()->toDateString();

        $cacheKey = 'receipts_dashboard_stats_' . ($constituencyCode ?? 'national');

        $tagType = $constituencyCode ? 'constituency_stats' : 'national_stats';

        return $this->cacheService->rememberReceipts(
            $cacheKey,
            $tagType,
            function() use ($constituencyCode, $today) {
                $stats = $this->getBaseQuery($constituencyCode)
                    ->where('lr.status', 'active')
                    ->selectRaw("
                        SUM(CASE WHEN lr.transaction_date = ? THEN lr.amount ELSE 0 END) as today_val,
                        SUM(CASE WHEN lr.transaction_date >= DATE_SUB(?, INTERVAL 7 DAY) THEN lr.amount ELSE 0 END) as weekly_val,
                        SUM(CASE WHEN lr.transaction_date >= DATE_FORMAT(?, '%Y-%m-01') THEN lr.amount ELSE 0 END) as monthly_val,
                        SUM(lr.amount) as total_val
                    ", [$today, $today, $today])
                    ->first();

                // Map to your KPI format
                return [
                    'today' => [
                        'title' => 'Today\'s Receipts',
                        'val'   => $stats->today_val ?? 0,
                        'icon'  => 'fa-calendar-check',
                        'color' => 'primary',
                        'desc'  => 'Collections for ' . date('d M Y')
                    ],
                    'weekly' => [
                        'title' => 'Weekly Total',
                        'val'   => $stats->weekly_val ?? 0,
                        'icon'  => 'fa-chart-line',
                        'color' => 'info',
                        'desc'  => 'Last 7 rolling days'
                    ],
                    'monthly' => [
                        'title' => 'Monthly Total',
                        'val'   => $stats->monthly_val ?? 0,
                        'icon'  => 'fa-calendar-alt',
                        'color' => 'success',
                        'desc'  => date('F') . ' collection to date'
                    ],
                    'total' => [
                        'title' => 'Lifetime Total',
                        'val'   => $stats->total_val ?? 0,
                        'icon'  => 'fa-coins',
                        'color' => 'dark',
                        'desc'  => 'Total collection in receipt'
                    ],
                ];
            },
            $constituencyCode
        );
    }

    private function getTodayCollection($query, Carbon $today): array
    {
        $result = (clone $query)
            ->whereDate('lr.transaction_date', $today)
            ->select(DB::raw('COALESCE(SUM(lr.amount), 0) as total'))
            ->first();

        return [
            'amount' => $result->total ?? 0,
            'count' => (clone $query)
                ->whereDate('lr.transaction_date', $today)
                ->count(),
            'formatted' => number_format($result->total ?? 0, 2)
        ];
    }

    private function getWeeklyCollection($query, Carbon $weekAgo, Carbon $today): array
    {
        $result = (clone $query)
            ->whereBetween('lr.transaction_date', [$weekAgo, $today])
            ->select(DB::raw('COALESCE(SUM(lr.amount), 0) as total'))
            ->first();

        return [
            'amount' => $result->total ?? 0,
            'count' => (clone $query)
                ->whereBetween('lr.transaction_date', [$weekAgo, $today])
                ->count(),
            'formatted' => number_format($result->total ?? 0, 2),
            'start_date' => $weekAgo->format('Y-m-d'),
            'end_date' => $today->format('Y-m-d')
        ];
    }

    private function getMonthlyCollection($query, Carbon $monthStart, Carbon $today): array
    {
        $result = (clone $query)
            ->whereBetween('lr.transaction_date', [$monthStart, $today])
            ->select(DB::raw('COALESCE(SUM(lr.amount), 0) as total'))
            ->first();

        return [
            'amount' => $result->total ?? 0,
            'count' => (clone $query)
                ->whereBetween('lr.transaction_date', [$monthStart, $today])
                ->count(),
            'formatted' => number_format($result->total ?? 0, 2),
            'month' => $monthStart->format('F Y')
        ];
    }

    private function getTotalCollection($query): array
    {
        $result = (clone $query)
            ->select(DB::raw('COALESCE(SUM(lr.amount), 0) as total'))
            ->first();

        $count = (clone $query)->count();

        return [
            'amount' => $result->total ?? 0,
            'count' => $count,
            'formatted' => number_format($result->total ?? 0, 2)
        ];
    }

    private function getCollectionTrends($query, Carbon $weekAgo, Carbon $today): Collection
    {
        return (clone $query)
            ->whereBetween('lr.transaction_date', [$weekAgo, $today])
            ->select(
                DB::raw('DATE(lr.transaction_date) as date'),
                DB::raw('COALESCE(SUM(lr.amount), 0) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DATE(lr.transaction_date)'))
            ->orderBy('date', 'ASC')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total' => $item->total,
                    'formatted' => number_format($item->total, 2),
                    'count' => $item->count
                ];
            });
    }

    private function getTopCollectingGroups($query, Carbon $monthStart, Carbon $today): Collection
    {
        return (clone $query)
            ->whereBetween('lr.transaction_date', [$monthStart, $today])
            ->select(
                'g.id',
                'g.name as group_name',
                'g.code',
                DB::raw('COALESCE(SUM(lr.amount), 0) as total_collected'),
                DB::raw('COUNT(DISTINCT lr.id) as receipt_count')
            )
            ->groupBy('g.id', 'g.name', 'g.code')
            ->orderBy('total_collected', 'DESC')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->group_name,
                    'code' => $item->code,
                    'total' => $item->total_collected,
                    'formatted' => number_format($item->total_collected, 2),
                    'receipt_count' => $item->receipt_count
                ];
            });
    }

    public function getRecentReceiptsQuery(
        ?string $constituencyCode = null,
        array $filters = [],
        string $sortField = 'transaction_date',
        string $sortDirection = 'desc'
    ): Builder {
        $query = $this->getBaseQuery($constituencyCode)
                ->select(
                    'lr.id',
                    'lr.receipt_number',
                    'lr.transaction_date',
                    'lr.amount',
                    'lr.payment',
                    'lr.reference_number',
                    'lr.status',
                    'lr.void_reason',
                    'g.name as group_name',
                    'g.code as group_code',

                    'gl.loan_type',
                    'gl.disbursement_date',
                    'c.constituency_code',
                    'c.county_code',
                    'c.constituency_name as constituency_name',
                    'co.county_code',
                    'co.county_name as county_name',
                    'lp.phase',
                    'lp.id as loan_phase_id'
                )
            ->where('gl.loan_status', 'financed');

        if (!empty($filters)) {
            $query->where(function($q) use ($filters) {
                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $q->where('lr.receipt_number', 'like', "%{$search}%")
                    ->orWhere('lr.reference_number', 'like', "%{$search}%")
                    ->orWhere('g.name', 'like', "%{$search}%")
                    ->orWhere('g.identifier', 'like', "%{$search}%");
                }

                if (!empty($filters['status'])) {
                    $q->where('lr.status', '>=', $filters['status']);
                }

                if (!empty($filters['date_from'])) {
                    $q->where('lr.transaction_date', '>=', $filters['date_from']);
                }

                if (!empty($filters['date_to'])) {
                    $q->where('lr.transaction_date', '<=', $filters['date_to']);
                }

                if (!empty($filters['payment_method'])) {
                    $q->where('lr.payment', $filters['payment_method']);
                }

                if (!empty($filters['county_code'])) {
                    $q->where('co.code', $filters['county_code']);
                }

                if (!empty($filters['constituency_code'])) {
                    $q->where('c.code', $filters['constituency_code']);
                }

            });
        }

        if ($sortField && $sortDirection) {
            $this->applyOptimizedSorting($constituencyCode, $query, $sortField, $sortDirection);
        } else {
            $query->orderBy('lr.transaction_date', 'desc');
        }

        return $query;
    }

    protected function applyOptimizedSorting(?string $constituencyCode, Builder $query, ?string $sortField, ?string $sortDirection): void
    {
        if ($sortField && $sortDirection) {
            $sortMap = [
                'receipt' => 'lr.receipt_number',
                'transaction' => 'lr.transaction_date',
                'group' => 'g.name',
                'amount' => 'lr.amount',
                'method' => 'lr.payment',
                'reference' => 'lr.reference_number',
                'status' => 'lr.status',
            ];

            if (array_key_exists($sortField, $sortMap)) {
                $column = $sortMap[$sortField];
                $query->orderBy($column, $sortDirection);
                return;
            }
        }

        $query->orderBy('lr.transaction_date', 'desc');
    }


    public function getDateRangeSummary(
        string $startDate,
        string $endDate,
        ?string $constituencyCode = null
    ): array {
        $baseQuery = $this->getBaseQuery($constituencyCode);

        $result = (clone $baseQuery)
            ->whereBetween('lr.transaction_date', [$startDate, $endDate])
            ->select(
                DB::raw('COALESCE(SUM(lr.amount), 0) as total_amount'),
                DB::raw('COUNT(*) as total_receipts'),
                DB::raw('AVG(lr.amount) as average_amount'),
                DB::raw('COUNT(DISTINCT gl.group_id) as unique_groups')
            )
            ->first();

        return [
            'total_amount' => $result->total_amount ?? 0,
            'total_amount_formatted' => number_format($result->total_amount ?? 0, 2),
            'total_receipts' => $result->total_receipts ?? 0,
            'average_amount' => number_format($result->average_amount ?? 0, 2),
            'unique_groups' => $result->unique_groups ?? 0,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    public function getDailyBreakdown(
        string $startDate,
        string $endDate,
        ?string $constituencyCode = null
    ): Collection {
        $baseQuery = $this->getBaseQuery($constituencyCode);

        return (clone $baseQuery)
            ->whereBetween('lr.transaction_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(lr.transaction_date) as date'),
                DB::raw('COALESCE(SUM(lr.amount), 0) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DATE(lr.transaction_date)'))
            ->orderBy('date', 'ASC')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total' => $item->total,
                    'formatted' => number_format($item->total, 2),
                    'count' => $item->count
                ];
            });
    }

    public function editReceipt(int $receiptId, array $data, $idempotencyKey) : void
    {
        DB::transaction(function () use ($receiptId, $data, $idempotencyKey) {
            $receipt = LoanReceipts::with(['groupLoan' => function ($query) {
                    $query->select([
                        'id',
                        'group_id',
                        'amount_repaid',
                    ]);
                }])
                ->lockForUpdate()
                ->findOrFail($receiptId);

            $groupLoan = $receipt->groupLoan;

            $newAmount = $data['amountRepaid'];
            $oldAmount = $receipt->amount;

            if ($oldAmount != $newAmount) {
                $difference = $newAmount - $oldAmount;

                $groupLoan->amount_repaid += $difference;
                $groupLoan->save();

                UpdateLoanDefaultStatus::dispatch($receipt->groupLoan->group_id)->onQueue('financial-updates')->afterCommit();
            }

            $receipt->update([
                'receipt_number'   => $data['receiptNumber'],
                'transaction_date' => $data['transactionDate'],
                'amount'           => $newAmount,
                'payment'          => $data['repaymentMethod'],
                'reference_number' => $data['referenceNumber'],
            ]);

            activity()
                ->performedOn($receipt)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_amount' => $receipt->amount,
                    'new_amount' => $data['amountRepaid'],
                    'ip_address' => request()->ip()
                ])
                ->log('Receipt updated by ' . auth()->user()->name);

            $this->idempotencyService->release($idempotencyKey);
        });
    }

    public function cancelReceipt(int $receiptId, $idempotencyKey, $reason) : void
    {
        DB::transaction(function () use ($receiptId, $idempotencyKey, $reason) {
            $receipt = LoanReceipts::select([
                'id',
                'amount',
                'group_loans_id',
                'status',
                'void_reason',
                'voided_by'
            ])
            ->with([
                'groupLoan' => function ($query) {
                    $query->select([
                        'id',
                        'group_id',
                        'amount_repaid',
                    ]);
                },
                'groupLoan.group' => function ($query) {
                    $query->select([
                        'id',
                        'name'
                    ]);
                }
            ])
            ->lockForUpdate()
            ->findOrFail($receiptId);

            $oldAmount = $receipt->groupLoan->amount_repaid;
            $newAmount = $oldAmount - $receipt->amount;

            $receipt->groupLoan->amount_repaid = $newAmount;
            $receipt->groupLoan->save();

            $receipt->update([
                'status' => 'voided',
                'void_reason' => $reason,
                'voided_by' => auth()->id(),
            ]);

            UpdateLoanDefaultStatus::dispatch($receipt->groupLoan->group_id)->onQueue('financial-updates')->afterCommit();

            activity()
                ->performedOn($receipt)
                ->causedBy(auth()->user())
                ->withProperties([
                    'reason' => $reason,
                    'amount_reversed' => $receipt->amount,
                    'group_name' => $receipt->groupLoan->group->name
                ])
                ->log('Receipt canceled by ' . auth()->user()->name);

            $this->idempotencyService->release($idempotencyKey);

        });


    }

    public function getGroups(?string $constituencyCode = null) : EloquentCollection
    {
        return Group::query()
            ->from('groups')
            ->select([
                'groups.id',
                'groups.name',
                'groups.code',
                'groups.identifier',
            ])
            ->with([
                'currentLoan' => function ($q) {
                    $q->select([
                        'group_loans.id',
                        'group_loans.group_id',
                        'group_loans.loan_phase_id'
                    ])
                    ->with(['loanPhase' => function ($sq) {
                        $sq->select([
                            'loan_phases.id',
                            'loan_phases.phase',
                            'loan_phases.constituency_code'
                        ]);
                    }]);
                },
            ])
            ->when($constituencyCode, function ($query) use ($constituencyCode) {
                $query->whereHas('currentLoan.loanPhase', function ($q) use ($constituencyCode) {
                    $q->where('loan_phases.constituency_code', $constituencyCode);
                });
            })
            ->get();
    }


    public function createReceipt(int $groupId, array $data, $idempotencyKey): void
    {
        DB::transaction(function () use ($groupId, $data, $idempotencyKey) {
            $group_loan = GroupLoan::select([
                'id',
                'group_id',
                'amount_repaid',
            ])
            ->with([
                'group' => function($g) {
                    $g->select([
                        'id',
                        'name',
                        'code'
                    ]);
                }
            ])
            ->lockForUpdate()
            ->findOrFail($groupId);

            $oldAmount = $group_loan->amount_repaid;
            $newAmount = $oldAmount + $data['amountRepaid'];

            $group_loan->update([
                'amount_repaid' => $newAmount,
            ]);

            LoanReceipts::create([
                'group_loans_id' => $group_loan->id,
                'receipt_number' => $data['receiptNumber'],
                'transaction_date' => $data['transactionDate'],
                'amount' => $data['amountRepaid'],
                'payment' => $data['repaymentMethod'],
                'reference_number' => $data['referenceNumber'],
            ]);

            UpdateLoanDefaultStatus::dispatch($group_loan->group_id)->onQueue('financial-updates')->afterCommit();

            activity()
                ->performedOn($group_loan)
                ->causedBy(auth()->user())
                ->withProperties([
                    'receipt_number' => $data['receiptNumber'],
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'group_name' => $group_loan->group->name,
                    'group_code' => $group_loan->group->code,
                ])
                ->log('Receipt Added by ' . auth()->user()->name);

            $this->idempotencyService->release($idempotencyKey);
        });

    }
}
