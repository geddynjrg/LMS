<?php

namespace App\Services\Group;

use App\Models\Group;
use App\Models\Wards;
use App\Models\GroupLoan;
use Illuminate\Database\Eloquent\Builder;

class GroupsService
{
    public function getGroupsQuery(string $constituencyCode, array $filters = [], ?string $sortField, ?string $sortDirection): Builder
    {
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'asc';

        return Group::query()
            ->from('groups')
            ->select([
                'groups.id',
                'groups.name',
                'groups.slug',
                'groups.ward_id',
                'groups.identifier',
                'groups.date_registered',
                'groups.reg_number'
            ])
            ->whereHas('ward', function ($q) use ($constituencyCode) {
                $q->where('constituency_code', $constituencyCode);
            })
            ->whereHas('groupLoans', function ($q) {
                $q->whereIn('loan_status', ['financed', 'cancelled']);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $searchTerm = '%' . trim($search) . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('groups.name', 'like', $searchTerm)
                    ->orWhere('groups.identifier', 'like', $searchTerm)
                    ->orWhereHas('currentLoan', function ($sub) use ($searchTerm) {
                        $sub->where('group_loans.gfs_code', 'like', $searchTerm);
                    });
                });
            })
            ->when($filters['ward_name'] ?? null, function ($query, $wardName) {
                $query->whereHas('ward', function ($q) use ($wardName) {
                    $q->where('wards.ward_name', 'like', '%' . $wardName . '%');
                });
            })
            ->when($filters['category'] ?? null, function ($query, $category) {
                $query->whereHas('currentLoan', function ($q) use ($category) {
                    $q->where('group_loans.category', $category);
                });
            })
            ->when($filters['sector'] ?? null, function ($query, $sector) {
                $query->whereHas('currentLoan', function ($q) use ($sector) {
                    $q->where('group_loans.sector', $sector);
                });
            })
            ->with(['ward' => function ($q) {
                $q->select('id', 'ward_name', 'constituency_code');
            }])
            ->with(['currentLoan' => function ($q) {
                $q->select('group_loans.id', 'group_loans.group_id', 'group_loans.category', 'group_loans.sector', 'group_loans.gfs_code', 'group_loans.disbursement_date');
            }])
            // Dynamic Sorting (fully qualified columns)
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                switch ($sortField) {
                    case 'identifier':
                        $query->orderBy('groups.identifier', $sortDirection);
                        break;

                    case 'ward_name':
                        $query->orderBy(
                            Wards::select('wards.ward_name')
                                ->whereColumn('wards.id', 'groups.ward_id'),
                            $sortDirection
                        );
                        break;

                    case 'gfs_code':
                        $query->orderBy(
                            GroupLoan::select('group_loans.gfs_code')
                                ->whereColumn('group_loans.group_id', 'groups.id')
                                ->limit(1),
                            $sortDirection
                        );
                        break;

                    case 'disbursement_date':
                        $query->orderBy(
                            GroupLoan::select('group_loans.disbursement_date')
                                ->whereColumn('group_loans.group_id', 'groups.id')
                                ->limit(1),
                            $sortDirection
                        );
                        break;

                    default:
                        $query->orderBy('groups.name', $sortDirection);
                        break;
                }
            }, function ($query) {
                $query->orderBy('groups.name', 'asc');
            });
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
