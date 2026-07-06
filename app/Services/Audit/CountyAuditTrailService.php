<?php

namespace App\Services\Audit;

use App\Models\Constituency;
use App\Models\County;
use App\Models\GroupLoan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class CountyAuditTrailService
{
    /** @var list<string> */
    protected const AUDIT_LOG_NAMES = ['phase_audit', 'group_audit', 'restore_audit'];

    public function userCanView(?User $user, County $county): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (!$user->hasRole('constituency_officer')) {
            return false;
        }

        return $user->county_code === $county->county_code
            && $user->constituencies->isNotEmpty();
    }

    /**
     * @return Collection<int, Constituency>
     */
    public function getFilterConstituencies(County $county, User $viewer): Collection
    {
        $query = Constituency::query()
            ->where('county_code', $county->county_code)
            ->orderBy('constituency_name');

        if (!$viewer->hasRole('super-admin')) {
            $assignedCodes = $viewer->constituencies->pluck('constituency_code')->all();
            $query->whereIn('constituency_code', $assignedCodes);
        }

        return $query->get();
    }

    /**
     * @return list<string>
     */
    public function getAllowedConstituencyCodes(County $county, User $viewer): array
    {
        return $this->getFilterConstituencies($county, $viewer)
            ->pluck('constituency_code')
            ->all();
    }

    /**
     * @param  array{
     *   constituency_code?: ?string,
     *   log_name?: ?string,
     *   causer_id?: ?int,
     *   date_from?: ?string,
     *   date_to?: ?string,
     *   search?: ?string,
     * }  $filters
     */
    public function getAuditEntries(
        County $county,
        User $viewer,
        array $filters,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $allowedCodes = $this->getAllowedConstituencyCodes($county, $viewer);

        if ($allowedCodes === []) {
            return Activity::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $constituencyCode = $filters['constituency_code'] ?? null;
        if ($constituencyCode && !in_array($constituencyCode, $allowedCodes, true)) {
            $constituencyCode = null;
        }

        $scopedCodes = $constituencyCode ? [$constituencyCode] : $allowedCodes;

        $query = Activity::query()
            ->with([
                'causer',
                'subject' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        GroupLoan::class => ['group'],
                    ]);
                },
            ])
            ->whereIn('log_name', self::AUDIT_LOG_NAMES)
            ->where(function ($q) use ($scopedCodes) {
                foreach ($scopedCodes as $code) {
                    $q->orWhere('properties->constituency_code', $code);
                }
            });

        if (!empty($filters['log_name']) && in_array($filters['log_name'], self::AUDIT_LOG_NAMES, true)) {
            $query->where('log_name', $filters['log_name']);
        }

        if (!empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhereHas('causer', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @return Collection<int, User>
     */
    public function getCountyOfficers(County $county, User $viewer): Collection
    {
        $allowedCodes = $this->getAllowedConstituencyCodes($county, $viewer);

        if ($allowedCodes === []) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['constituency_officer', 'intern']))
            ->where(function ($q) use ($county, $allowedCodes) {
                $q->where('county_code', $county->county_code)
                    ->whereHas('constituencies', fn ($constituencyQuery) => $constituencyQuery
                        ->whereIn('constituencies.constituency_code', $allowedCodes));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
