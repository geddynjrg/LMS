<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use App\Models\TempApplication;
use App\Models\TempGroupMember;
use App\Models\User;
use App\Support\SystemRoles;
use Illuminate\Support\Facades\Log;

class CustomSmsService
{
    public function __construct(protected SmsService $smsService)
    {
    }

    public function userCanSendCustomSms(User $user): bool
    {
        return $user->hasAnyRole([
            SystemRoles::CONSTITUENCY_OFFICER,
            'intern',
            'super-admin',
        ]);
    }

    public function userHasConstituencyAccess(User $user, string $constituencyCode): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->constituencies()
            ->wherePivot('constituency_code', $constituencyCode)
            ->exists();
    }

    public function memberBelongsToConstituency(string $memberId, string $constituencyCode): bool
    {
        return GroupMember::query()
            ->where('member_Id', $memberId)
            ->whereHas('groupLoan.loanPhase', function ($query) use ($constituencyCode) {
                $query->where('constituency_code', $constituencyCode);
            })
            ->exists();
    }

    public function groupBelongsToConstituency(Group $group, string $constituencyCode): bool
    {
        $group->loadMissing('ward');

        return $group->ward
            && $group->ward->constituency_code === $constituencyCode;
    }

    public function applicationBelongsToConstituency(TempApplication $application, string $constituencyCode): bool
    {
        return $application->constituency_code === $constituencyCode;
    }

    /**
     * @return array{sms_sent: int, sms_failed: int, skipped: int}
     */
    public function sendToMember(
        User $user,
        string $memberId,
        string $constituencyCode,
        string $message,
    ): array {
        $this->assertCanSend($user, $constituencyCode);

        if (!$this->memberBelongsToConstituency($memberId, $constituencyCode)) {
            throw new \InvalidArgumentException('Member is not in your constituency scope.');
        }

        $member = Member::where('member_Id', $memberId)->first();
        if (!$member || empty($member->number)) {
            throw new \InvalidArgumentException('Member has no contact number on file.');
        }

        $formattedMessage = $this->formatMessage($message);
        $sent = $this->smsService->send($member->number, $formattedMessage);

        Log::info('Custom SMS sent to member', [
            'user_id' => $user->id,
            'member_id' => $memberId,
            'constituency_code' => $constituencyCode,
            'success' => $sent,
        ]);

        return [
            'sms_sent' => $sent ? 1 : 0,
            'sms_failed' => $sent ? 0 : 1,
            'skipped' => 0,
        ];
    }

    /**
     * @return array{sms_sent: int, sms_failed: int, skipped: int}
     */
    public function sendToGroup(
        User $user,
        Group $group,
        string $constituencyCode,
        string $message,
        ?int $groupLoanId = null,
    ): array {
        $this->assertCanSend($user, $constituencyCode);

        if (!$this->groupBelongsToConstituency($group, $constituencyCode)) {
            throw new \InvalidArgumentException('Group is not in your constituency scope.');
        }

        $query = GroupMember::query()
            ->whereHas('groupLoan', function ($q) use ($group, $groupLoanId) {
                $q->where('group_id', $group->id);
                if ($groupLoanId) {
                    $q->where('id', $groupLoanId);
                }
            })
            ->with('member');

        $members = $query->get()->unique('member_Id');
        $formattedMessage = $this->formatMessage($message);

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($members as $groupMember) {
            $phone = $groupMember->member?->number;
            if (empty($phone)) {
                $skipped++;
                continue;
            }

            if ($this->smsService->send($phone, $formattedMessage)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        Log::info('Custom SMS sent to group', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'group_loan_id' => $groupLoanId,
            'constituency_code' => $constituencyCode,
            'sms_sent' => $sent,
            'sms_failed' => $failed,
            'skipped' => $skipped,
        ]);

        return [
            'sms_sent' => $sent,
            'sms_failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{sms_sent: int, sms_failed: int, skipped: int}
     */
    public function sendToApplicationMember(
        User $user,
        int $tempGroupMemberId,
        string $constituencyCode,
        string $message,
    ): array {
        $this->assertCanSend($user, $constituencyCode);

        $member = TempGroupMember::with('application')->find($tempGroupMemberId);
        if (!$member || !$member->application) {
            throw new \InvalidArgumentException('Application member not found.');
        }

        if (!$this->applicationBelongsToConstituency($member->application, $constituencyCode)) {
            throw new \InvalidArgumentException('Application is not in your constituency scope.');
        }

        if (empty($member->phone_number)) {
            throw new \InvalidArgumentException('Member has no contact number on file.');
        }

        $formattedMessage = $this->formatMessage($message);
        $sent = $this->smsService->send($member->phone_number, $formattedMessage);

        Log::info('Custom SMS sent to application member', [
            'user_id' => $user->id,
            'temp_group_member_id' => $tempGroupMemberId,
            'application_id' => $member->application->application_id,
            'constituency_code' => $constituencyCode,
            'success' => $sent,
        ]);

        return [
            'sms_sent' => $sent ? 1 : 0,
            'sms_failed' => $sent ? 0 : 1,
            'skipped' => 0,
        ];
    }

    /**
     * @return array{sms_sent: int, sms_failed: int, skipped: int}
     */
    public function sendToApplicationGroup(
        User $user,
        TempApplication $application,
        string $constituencyCode,
        string $message,
    ): array {
        $this->assertCanSend($user, $constituencyCode);

        if (!$this->applicationBelongsToConstituency($application, $constituencyCode)) {
            throw new \InvalidArgumentException('Application is not in your constituency scope.');
        }

        $application->loadMissing('groupMembers');
        $formattedMessage = $this->formatMessage($message);

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($application->groupMembers as $member) {
            if (empty($member->phone_number)) {
                $skipped++;
                continue;
            }

            if ($this->smsService->send($member->phone_number, $formattedMessage)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        Log::info('Custom SMS sent to application group', [
            'user_id' => $user->id,
            'application_id' => $application->application_id,
            'constituency_code' => $constituencyCode,
            'sms_sent' => $sent,
            'sms_failed' => $failed,
            'skipped' => $skipped,
        ]);

        return [
            'sms_sent' => $sent,
            'sms_failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    protected function assertCanSend(User $user, string $constituencyCode): void
    {
        if (!$this->userCanSendCustomSms($user)) {
            throw new \InvalidArgumentException('You are not authorized to send custom SMS.');
        }

        if (!$this->userHasConstituencyAccess($user, $constituencyCode)) {
            throw new \InvalidArgumentException('You do not have access to this constituency.');
        }

        if (!$this->smsService->isConfigured()) {
            throw new \RuntimeException('SMS gateway is not configured. Contact your administrator.');
        }
    }

    protected function formatMessage(string $message): string
    {
        $message = trim($message);

        if (!str_starts_with($message, 'Uwezo Fund')) {
            return 'Uwezo Fund: ' . $message;
        }

        return $message;
    }
}
