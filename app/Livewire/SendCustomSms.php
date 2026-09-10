<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\TempApplication;
use App\Services\CustomSmsService;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class SendCustomSms extends Component
{
    public bool $isOpen = false;
    public string $message = '';
    public string $recipientType = 'member';
    public ?string $memberId = null;
    public ?string $memberName = null;
    public ?string $memberPhone = null;
    public ?int $groupId = null;
    public ?string $groupName = null;
    public ?int $groupLoanId = null;
    public ?int $tempGroupMemberId = null;
    public ?string $applicationId = null;
    public string $constituencyCode = '';
    public bool $sending = false;
    public ?string $statusMessage = null;
    public string $statusType = 'info';
    public ?string $smsResultKey = null;

    protected CustomSmsService $customSmsService;

    public function boot(CustomSmsService $customSmsService): void
    {
        $this->customSmsService = $customSmsService;
    }

    #[On('open-send-sms-modal')]
    public function openModal(
        string $recipientType,
        string $constituencyCode,
        ?string $memberId = null,
        ?string $memberName = null,
        ?string $memberPhone = null,
        ?int $groupId = null,
        ?string $groupName = null,
        ?int $groupLoanId = null,
        ?int $tempGroupMemberId = null,
        ?string $applicationId = null,
    ): void {
        if (!$this->customSmsService->userCanSendCustomSms(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Not Authorized',
                message: 'You are not authorized to send custom SMS.',
                type: 'error'
            );

            return;
        }

        $this->reset(['message', 'statusMessage', 'sending', 'smsResultKey']);
        $this->recipientType = $recipientType;
        $this->constituencyCode = $constituencyCode;
        $this->memberId = $memberId;
        $this->memberName = $memberName;
        $this->memberPhone = $memberPhone;
        $this->groupId = $groupId;
        $this->groupName = $groupName;
        $this->groupLoanId = $groupLoanId;
        $this->tempGroupMemberId = $tempGroupMemberId;
        $this->applicationId = $applicationId;
        $this->statusType = 'info';
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['message', 'statusMessage', 'sending', 'smsResultKey']);
    }

    public function send(): void
    {
        $this->validate([
            'message' => 'required|string|min:5|max:480',
        ]);

        $this->sending = true;
        $this->statusMessage = null;

        try {
            $user = auth()->user();

            if ($this->recipientType === 'member' || $this->recipientType === 'application_member') {
                if ($this->recipientType === 'application_member') {
                    $result = $this->customSmsService->sendToApplicationMember(
                        $user,
                        $this->tempGroupMemberId,
                        $this->constituencyCode,
                        $this->message,
                    );
                } else {
                    $result = $this->customSmsService->sendToMember(
                        $user,
                        $this->memberId,
                        $this->constituencyCode,
                        $this->message,
                    );
                }

                if ($result['sms_sent'] > 0) {
                    $this->statusType = 'success';
                    $this->statusMessage = 'SMS sent successfully to ' . ($this->memberName ?? 'member') . '.';
                    $this->message = '';
                } else {
                    $this->statusType = 'danger';
                    $this->statusMessage = 'Failed to send SMS. Please try again or check the contact number.';
                }
            } elseif ($this->recipientType === 'application_group') {
                $application = TempApplication::query()
                    ->where('application_id', $this->applicationId)
                    ->where('constituency_code', $this->constituencyCode)
                    ->firstOrFail();

                $result = $this->customSmsService->sendToApplicationGroup(
                    $user,
                    $application,
                    $this->constituencyCode,
                    $this->message,
                );

                $skipNote = ($result['skipped'] ?? 0) > 0
                    ? " ({$result['skipped']} member(s) skipped — no phone number)"
                    : '';
                if (($result['sms_queued'] ?? 0) > 0) {
                    $this->statusType = 'success';
                    $this->smsResultKey = $result['result_key'] ?? null;
                    $this->statusMessage = "Queued SMS for {$result['sms_queued']} member(s). Delivery continues in the background.{$skipNote}";
                    $this->message = '';
                } elseif ($result['sms_sent'] > 0) {
                    $this->statusType = 'success';
                    $failNote = $result['sms_failed'] > 0
                        ? " {$result['sms_failed']} failed."
                        : '';
                    $this->statusMessage = "SMS sent to {$result['sms_sent']} member(s).{$failNote}{$skipNote}";
                    $this->message = '';
                } elseif ($result['skipped'] > 0 && $result['sms_failed'] === 0) {
                    $this->statusType = 'warning';
                    $this->statusMessage = 'No members have contact numbers on file.';
                } else {
                    $this->statusType = 'danger';
                    $this->statusMessage = 'Failed to send SMS to group members.';
                }
            } else {
                $group = Group::findOrFail($this->groupId);

                $result = $this->customSmsService->sendToGroup(
                    $user,
                    $group,
                    $this->constituencyCode,
                    $this->message,
                    $this->groupLoanId,
                );

                $skipNote = ($result['skipped'] ?? 0) > 0
                    ? " ({$result['skipped']} member(s) skipped — no phone number)"
                    : '';
                if (($result['sms_queued'] ?? 0) > 0) {
                    $this->statusType = 'success';
                    $this->smsResultKey = $result['result_key'] ?? null;
                    $this->statusMessage = "Queued SMS for {$result['sms_queued']} member(s). Delivery continues in the background.{$skipNote}";
                    $this->message = '';
                } elseif ($result['sms_sent'] > 0) {
                    $this->statusType = 'success';
                    $failNote = $result['sms_failed'] > 0
                        ? " {$result['sms_failed']} failed."
                        : '';
                    $this->statusMessage = "SMS sent to {$result['sms_sent']} member(s).{$failNote}{$skipNote}";
                    $this->message = '';
                } elseif ($result['skipped'] > 0 && $result['sms_failed'] === 0) {
                    $this->statusType = 'warning';
                    $this->statusMessage = 'No members have contact numbers on file.';
                } else {
                    $this->statusType = 'danger';
                    $this->statusMessage = 'Failed to send SMS to group members.';
                }
            }
        } catch (\InvalidArgumentException $e) {
            $this->statusType = 'danger';
            $this->statusMessage = $e->getMessage();
        } catch (\RuntimeException $e) {
            $this->statusType = 'danger';
            $this->statusMessage = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->statusType = 'danger';
            $this->statusMessage = 'An unexpected error occurred. Please try again.';
        } finally {
            $this->sending = false;
        }
    }


    public function pollSmsResult(): void
    {
        if (! $this->smsResultKey) {
            return;
        }

        $result = Cache::get($this->smsResultKey);
        if (! is_array($result) || ($result['status'] ?? '') !== 'done') {
            return;
        }

        $sent = (int) ($result['sms_sent'] ?? 0);
        $failed = (int) ($result['sms_failed'] ?? 0);
        if (! empty($result['credits_exhausted'])) {
            $this->statusType = 'danger';
            $this->statusMessage = "SMS delivery stopped: EBIS bulk credits are exhausted. Sent {$sent}, not sent {$failed}. Top up the EBIS SMS account, then retry.";
        } elseif ($failed > 0 && $sent > 0) {
            $this->statusType = 'warning';
            $this->statusMessage = "SMS delivery finished: {$sent} sent, {$failed} failed.";
        } elseif ($sent > 0) {
            $this->statusType = 'success';
            $this->statusMessage = "SMS delivery finished: {$sent} sent.";
        } else {
            $this->statusType = 'danger';
            $this->statusMessage = $result['error'] ?? 'SMS delivery failed.';
        }

        $this->smsResultKey = null;
    }

    public function render()
    {
        return view('livewire.send-custom-sms');
    }
}
