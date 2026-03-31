<?php

namespace App\Services;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

class UserService
{
    public function getUserActions(User $user, int $perPage = 10, string $search = ''): LengthAwarePaginator
    {
        return Activity::query()
            ->with(['subject', 'causer'])
            ->where('causer_type', User::class)
            ->where('causer_id', $user->id)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('subject_type', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function getManageablePermissions(User $targetUser)
    {
        $viewer = auth()->user();
        $query = Permission::query();

        $targetIsIntern = $targetUser->hasRole('intern');
        $targetIsCO = $targetUser->hasRole('constituency_officer');

        if ($targetIsIntern || $targetIsCO) {
            // Standard restriction for COs and Interns
            $query->where(function($q) use ($targetIsIntern) {
                $q->where('name', 'NOT LIKE', '%activities%')
                ->where('name', 'NOT LIKE', '%permissions%')
                ->where('name', '!=', 'approve application');

                // Extra restriction: Interns cannot have ANY User-related data at all
                if ($targetIsIntern) {
                    $q->where('name', 'NOT LIKE', '%data%');
                    $q->where('name', 'NOT LIKE', '%user%');
                }
            });
        } else {
            /* Logic for "Other Roles" (e.g., Regional Managers, Finance, etc.)
            Only show 'view' permissions + 'generate reports' + 'approve application'
            */
            $query->where(function($q) {
                $q->where('name', 'LIKE', 'view %')
                ->orWhere('name', '=', 'generate reports')
                ->orWhere('name', '=', 'approve application');
            });
        }

        if ($viewer->hasRole('constituency-officer')) {
            $query->where('name', '!=', 'approve application');
        }

        return $query->get()->groupBy(function($perm) {
            $parts = explode(' ', $perm->name);
            $subject = collect($parts)->last();

            return match($subject) {
                'application' => 'Applications',
                'receipt'     => 'Receipts',
                'phase'       => 'Phases',
                'member'      => 'Members',
                'group'       => 'Groups',
                'reports'     => 'Reporting',
                'activities'  => 'Activity Logs',
                'data'        => 'User Data',
                default       => Str::headline($subject)
            };
        });
    }

    public function syncAllPermissions(User $targetUser)
    {
        $manageablePermissions = $this->getManageablePermissions($targetUser)
            ->flatten()
            ->pluck('name')
            ->toArray();

        $targetUser->givePermissionTo($manageablePermissions);

        return true;
    }

    /**
     * Revoke only the manageable permissions from a specific user.
     */
    public function revokeAllPermissions(User $targetUser)
    {
        $manageablePermissions = $this->getManageablePermissions($targetUser)
            ->flatten()
            ->pluck('name')
            ->toArray();

        $targetUser->revokePermissionTo($manageablePermissions);

        return true;
    }

    /**
     * Sync a specific group (Category) of permissions.
     */
    public function syncPermissionGroup(User $targetUser, string $category)
    {
        $allManageable = $this->getManageablePermissions($targetUser);

        if (isset($allManageable[$category])) {
            $permissionNames = $allManageable[$category]->pluck('name')->toArray();
            $targetUser->givePermissionTo($permissionNames);
        }

        return true;
    }

    public function getUserWithContext($userId)
    {
        return User::with([
            'roles',
            'permissions',
            'constituencies',
            'county'
        ])->findOrFail($userId);
    }
}
