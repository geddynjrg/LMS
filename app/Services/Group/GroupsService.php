<?php

namespace App\Services\Group;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use App\Models\Group;
use App\Models\Wards;
use App\Models\GroupLoan;
use App\Services\Cache\AppCacheService;

class GroupsService
{
    protected $cacheService;

    public function  __construct(AppCacheService $appCacheService)
    {
        $this->cacheService = $appCacheService;
    }

    private function getGroupLoansBaseQuery(?string $constituencyCode = null)
    {
        $query = DB::table('group_loans as gl')
            ->join('groups as g', 'gl.group_id', '=', 'g.id')
            ->join('wards as w', 'g.ward_id', '=', 'w.id')
            ->join('constituencies as c', 'w.constituency_code', '=', 'c.constituency_code')
            ->join('counties as co', 'c.county_code', '=', 'co.county_code')
            ->join('loan_phases as lp', 'gl.loan_phase_id', '=', 'lp.id')
            ->whereIn('loan_status', ['financed', 'cancelled']);

        if ($constituencyCode) {
            $query->where('w.constituency_code', $constituencyCode);
        }

        return $query;
    }

    private function getGroupsBaseQuery(?string $constituencyCode = null)
    {
        $latestBeneficiaryLoans = DB::table('group_loans')
            ->select('group_id', DB::raw('MAX(id) as latest_loan_id'))
            ->whereIn('loan_status', ['financed', 'cancelled'])
            ->groupBy('group_id');

        $query = DB::table('groups as g')
            ->join('wards as w', 'g.ward_id', '=', 'w.id')
            ->join('constituencies as c', 'w.constituency_code', '=', 'c.constituency_code')
            ->join('counties as co', 'c.county_code', '=', 'co.county_code')
            ->joinSub($latestBeneficiaryLoans, 'latest_gl', function ($join) {
                $join->on('g.id', '=', 'latest_gl.group_id');
            })
            ->join('group_loans as gl', 'latest_gl.latest_loan_id', '=', 'gl.id');

        if ($constituencyCode) {
            $query->where('w.constituency_code', $constituencyCode);
        }

        return $query;
    }

    public function getGroupsDashboardStarts(?string $constituencyCode = null): array
    {
        return $this->cacheService->rememberGroupsDashboardStats(
            function () use ($constituencyCode) {
                $groupsStats = $this->getGroupLoansBaseQuery($constituencyCode)
                    ->selectRaw("
                        COUNT(gl.id) as total_beneficiearies,
                        SUM(CASE WHEN LOWER(gl.status) = 'financed' THEN 1 ELSE 0 END) as total_financed_count,

                        SUM(CASE WHEN LOWER(gl.category) = 'youth' THEN 1 ELSE 0 END) as youth_count,
                        SUM(CASE WHEN LOWER(gl.category) = 'women' THEN 1 ELSE 0 END) as women_count,
                        SUM(CASE WHEN LOWER(gl.category) = 'pwd' THEN 1 ELSE 0 END) as pwd_count,

                        SUM(CASE WHEN LOWER(gl.category) = 'youth' THEN gl.amount_issued ELSE 0 END) as youth_amount,
                        SUM(CASE WHEN LOWER(gl.category) = 'women' THEN gl.amount_issued ELSE 0 END) as women_amount,
                        SUM(CASE WHEN LOWER(gl.category) = 'pwd' THEN gl.amount_issued ELSE 0 END) as pwd_amount,

                        SUM(gl.amount_issued) as total_issued
                    ")
                    ->first();

                return [
                    [
                        'label' => 'Women Groups',
                        'val' => $groupsStats->women_count ?? 0,
                        'amount' => $groupsStats->women_amount,
                        'color' => 'pink',
                        'icon' => 'fa-female',
                        'percentage' => $groupsStats->total_issued > 0 ? min(100, round(($groupsStats->women_amount/$groupsStats->total_issued) * 100)) : 0,
                        'cat' => 'Women'
                    ],
                    [
                        'label' => 'Youth Groups',
                        'val' => $groupsStats->youth_count ?? 0,
                        'amount' => $groupsStats->youth_amount,
                        'percentage' => $groupsStats->total_issued > 0 ? min(100, round(($groupsStats->youth_amount/$groupsStats->total_issued) * 100)) : 0,
                        'color' => 'orange',
                        'icon' => 'fa-child',
                        'cat' => 'Youth'
                    ],
                    [
                        'label' => 'PWD Groups',
                        'val' => $groupsStats->pwd_count ?? 0,
                        'amount' => $groupsStats->pwd_amount,
                        'percentage' => $groupsStats->total_issued > 0 ? min(100, round(($groupsStats->pwd_amount/$groupsStats->total_issued) * 100)) : 0,
                        'color' => 'purple',
                        'icon' => 'fa-wheelchair',
                        'cat' => 'PWD'
                    ],
                    [
                        'label' => 'Registry Size',
                        'val' => $groupsStats->total_beneficiearies,
                        'amount' => $groupsStats->total_issued,
                        'color' => 'primary',
                        'icon' => 'fa-users',
                        'cat' => null
                    ]
                ];
            }
        );
    }


    public function getGroupLoansDashboardStats(?string $constituencyCode = null): array
    {
        return $this->cacheService->rememberGroupLoansDashboardStats(
            function() use ($constituencyCode) {
                $loanStats = $this->getGroupLoansBaseQuery($constituencyCode)
                    ->selectRaw("
                        COUNT(gl.id) as total_loans,
                        SUM(CASE WHEN gl.loan_status = 'financed' THEN 1 ELSE 0 END) as total_financed_count,
                        SUM(gl.amount_issued) as total_issued,
                        SUM(gl.amount_repaid) as total_repaid,
                        SUM(gl.amount_approved) as total_approved,
                        SUM(gl.total_arrears) as total_arrears
                    ")
                    ->first();

                return [
                    'approved' => [
                        'title' => 'Total Approved',
                        'val'   => $loanStats->total_approved ?? 0,
                        'icon'  => 'fa-check-circle',
                        'color' => 'primary',
                        'desc'  => $loanStats->total_loans. ' Total approved loans'
                    ],
                    'issued' => [
                        'title' => 'Amount Issued',
                        'val'   => $loanStats->total_issued ?? 0,
                        'icon'  => 'fa-hand-holding-usd',
                        'color' => 'info',
                        'desc'  => $loanStats->total_financed_count. ' Total principal disbursed'
                    ],
                    'repaid' => [
                        'title' => 'Amount Repaid',
                        'val'   => $loanStats->total_repaid ?? 0,
                        'icon'  => 'fa-receipt',
                        'color' => 'success',
                        'desc'  => 'Total collections to date'
                    ],
                    'arrears' => [
                        'title' => 'Cummulative Arrears',
                        'val'   => $loanStats->total_arrears ?? 0,
                        'icon'  => 'fa-exclamation-triangle',
                        'color' => 'danger',
                        'desc'  => 'Cummulative Arrears'
                    ],
                ];
            },
            $constituencyCode
        );
    }

    public function getGroupLoansQuery(
        ?string $constituencyCode = null,
        array $filters = [],
        string $sortField = 'disbursement_date',
        string $sortDirection = 'desc'
    ): QueryBuilder {
        $query = $this->getGroupLoansBaseQuery($constituencyCode)
            ->select(
                'g.id as grp_id',
                'g.name',
                'g.ward_id',
                'w.id as wrd_id',
                'w.ward_name',
                'w.constituency_code',
                'c.id as constituency_id',
                'c.constituency_code',
                'c.constituency_name',
                'c.county_code',
                'co.county_code',
                'co.county_name',
                'gl.id as gl_id',
                'gl.disbursement_date',
                'gl.status',
                'gl.amount_issued',
                'gl.amount_repaid',
                DB::raw('(gl.amount_issued - gl.amount_repaid) as balance'),
                'lp.id as lph_id',
                'lp.phase',
            );

        if (!empty($filters)) {
            $query->where(function($q) use ($filters) {
                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $q->where('g.name', 'like', "%{$search}%")
                    ->orWhere('g.identifier', 'like', "%{$search}%");
                }

                if (!empty($filters['status'])) {
                    $q->where('gl.status', $filters['status']);
                }

                if (!empty($filters['date_from'])) {
                    $q->where('gl.disbursement_date', '>=', $filters['date_from']);
                }

                if (!empty($filters['date_to'])) {
                    $q->where('gl.disbursement_date', '<=', $filters['date_to']);
                }

                if (!empty($filters['phase'])) {
                    $q->where('lp.slug', $filters['phase']);
                }

                if (!empty($filters['county_code'])) {
                    $q->where('co.county_code', $filters['county_code']);
                }

                if (!empty($filters['constituency_code'])) {
                    $q->where('c.constituency_code', $filters['constituency_code']);
                }

                if (!empty($filters['ward'])) {
                    $q->where('w.id', $filters['ward']);
                }
            });
        }

        if ($sortField && $sortDirection) {
            $this->applyOptimizedSorting($constituencyCode, $query, $sortField, $sortDirection);
        } else {
            $query->orderBy('gl.disbursement_date', 'desc');
        }

        return $query;
    }

    protected function applyOptimizedSorting(?string $constituencyCode, QueryBuilder $query, ?string $sortField, ?string $sortDirection): void
    {
        if ($sortField && $sortDirection) {
            $sortMap = [
                'ward' => 'w.ward_name',
                'groupName' => 'g.name',
                'phase' => 'lp.phase',
                'status' => 'gl.status',
                'disbursement_date' => 'gl.disbursement_date',
                'issued' => 'gl.amount_issued',
                'repaid' => 'gl.amount_repaid',
                'balance' => 'balance',
                'category' => 'gl.category',
                'sector' => 'gl.sector',
                'gfs' => 'gl.gfs_code',
                'county' => 'co.county_name',
            ];

            if (array_key_exists($sortField, $sortMap)) {
                $column = $sortMap[$sortField];
                $query->orderBy($column, $sortDirection);
                return;
            }
        }

        $query->orderBy('gl.disbursement_date', 'desc');
    }

    public function getGroupLoanHistory(int $groupLoanId, ?string $constituencyCode = null)
    {
        return GroupLoan::query()
            ->select([
                'id',
                'group_id',
                'loan_phase_id',
                'amount_issued',
                'amount_repaid',
                'total_arrears',
                'disbursement_date',
                'status'
            ])
            ->when($constituencyCode, function ($query) use ($constituencyCode) {
                $query->whereHas('group.ward', function ($q) use ($constituencyCode) {
                    $q->where('constituency_code', $constituencyCode);
                });
            })
            ->with([
                'loanReceipts' => function ($query) {
                    $query->where('status', 'active')
                        ->orderBy('transaction_date', 'desc');
                },
                'loanPhase' => function ($q) {
                    $q->select(['id', 'phase']);
                },
                'group' => function ($q) {
                    $q->select(['id', 'name']);
                }

            ])
            ->withSum(['loanReceipts as total_active_repayments' => function ($query) {
                $query->where('status', 'active');
            }], 'amount')

            ->findOrFail($groupLoanId);
    }

    public function getGroupsQuery (
        ?string $constituencyCode = null,
        array $filters = [],
        ?string $sortField,
        ?string $sortDirection
    ): QueryBuilder {
        $query = $this->getGroupsBaseQuery($constituencyCode)
            ->select(
                'g.id as grp_id',
                'g.name',
                'g.reg_number',
                'g.identifier',
                'g.slug',
                'g.ward_id',
                'g.date_registered',
                'w.id as wrd_id',
                'w.ward_name',
                'w.constituency_code',
                'c.id as constituency_id',
                'c.constituency_code',
                'c.constituency_name',
                'c.county_code',
                'co.county_code',
                'co.county_name',
                'gl.id as gl_id',
                'gl.gfs_code',
                'gl.category',
                'gl.sector',
            );

        if (!empty($filters)) {
            $query->where(function($q) use ($filters) {
                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $q->where('g.name', 'like', "%{$search}%")
                    ->orWhere('g.identifier', 'like', "%{$search}%")
                    ->orWhere('gl.gfs_code', 'like', "%{$search}%");
                }

                if (!empty($filters['category'])) {
                    $category = $filters['category'];
                    $q->where('gl.category', 'like', "%{$category}%");
                }

                if (!empty($filters['sector'])) {
                    $sector = $filters['sector'];
                    $q->where('gl.sector', 'like', "%{$sector}%");
                }

                if (!empty($filters['county_code'])) {
                    $q->where('co.county_code', $filters['county_code']);
                }

                if (!empty($filters['constituency_code'])) {
                    $q->where('c.constituency_code', $filters['constituency_code']);
                }

                if (!empty($filters['ward'])) {
                    $q->where('w.id', $filters['ward']);
                }
            });
        }

        if ($sortField && $sortDirection) {
            $this->applyOptimizedSorting($constituencyCode, $query, $sortField, $sortDirection);
        } else {
            $query->orderBy('gl.disbursement_date', 'desc');
        }

        return $query;
    }

    /**
     * Get a query builder for a single group by slug, with all required relationships pre-loaded.
     *
     * @param string $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */

    public function getGroupQuery(string $slug): Builder
    {
        return Group::query()
            ->where('slug', $slug)
            ->with([
                'ward' => fn($q) => $q->select('id', 'ward_name', 'constituency_code')
                    ->with('constituency:constituency_code,constituency_name'),
                'groupLoans' => function ($q) {
                    $q->select([
                        'id',
                        'group_id',
                        'loan_phase_id',
                        'loan_status',
                        'amount_applied',
                        'amount_approved',
                        'amount_issued',
                        'amount_repaid',
                        'total_arrears',
                        'amount_due',
                    ])
                    ->withSum(['loanReceipts as total_receipt_amount'], 'amount')
                    ->withCount([
                        'groupMembers as male_count' => function ($query) {
                            $query->whereHas('member', function($m) {
                                $m->where('gender', 'Male');
                            });
                        },
                        'groupMembers as female_count' => function ($query) {
                            $query->whereHas('member', function($m) {
                                $m->where('gender', 'Female');
                            });
                        },
                        'groupMembers as pwd_count' => function ($query) {
                            $query->whereHas('member', function($m) {
                                $m->where('disabled', true);
                            });
                        },
                        'groupMembers as total_members_count'
                    ])
                    ->with([
                        'groupMembers' => fn($qm) => $qm->select([
                            'id', 'group_loans_id', 'member_Id', 'role',
                        ])->with([
                            'member' => fn($m) => $m->select('id', 'name', 'member_Id', 'number', 'gender', 'disabled')
                        ]),
                        'loanPhase' => fn($qlp) => $qlp->select('id', 'phase', 'constituency_code'),
                        'loanReceipts' => fn($lr) =>$lr->select([
                            'id', 'group_loans_id', 'receipt_number', 'transaction_date', 'amount', 'payment', 'reference_number',
                        ])
                    ]);
                },

                'currentLoan' => fn($q) => $q->select([
                    'group_loans.id', 'group_loans.group_id', 'group_loans.category', 'group_loans.sector', 'group_loans.gfs_code', 'group_loans.sector_desc', 'group_loans.is_defaulted', 'group_loans.disbursement_date'
                ])->with([
                    'groupMembers' => fn($qm) => $qm->select([
                            'id', 'group_loans_id', 'member_Id', 'role',
                        ])->with([
                            'member' => fn($m) => $m->select('id', 'name', 'member_Id', 'number', 'gender', 'disabled')
                        ]),
                        'loanPhase' => fn($qlp) => $qlp->select('id', 'phase', 'constituency_code'),
                ])
            ])
            ->withCount([
                'groupLoans as successful_applications_count' => fn($qc) => $qc->where('loan_status', 'financed'),
                'groupLoans as unsuccessful_applications_count' => fn($qc) => $qc->where('loan_status', 'cancelled'),
            ])
            ->withSum(['groupLoans as total_amount_applied'], 'amount_applied')
            ->withSum(['groupLoans as total_amount_issued'], 'amount_issued')
            ->withSum(['groupLoans as total_amount_repaid'], 'amount_repaid')
            ->withSum(['groupLoans as total_arrears'], 'total_arrears')
            ->withSum(['groupLoans as total_amount_due'], 'amount_due')
            ->orderBy('created_at', 'desc')
            ->limit(1);
    }

}
