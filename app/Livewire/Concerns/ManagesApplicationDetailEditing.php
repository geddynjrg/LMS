<?php

namespace App\Livewire\Concerns;

use App\Models\TempApplication;
use App\Services\Application\TempApplicationDetailEditService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

trait ManagesApplicationDetailEditing
{
    public ?int $editingApplicationId = null;

    public string $editGroupName = '';
    public ?string $editGroupRegistrationNumber = null;
    public ?int $editGroupWardId = null;
    public ?string $editGfsCode = null;
    public ?string $editAmountApplied = null;
    public ?string $editAmountApproved = null;
    public ?string $editAmountIssued = null;
    public ?string $editAmountRepaid = null;
    public ?string $editDateRegistered = null;
    public ?string $editDisbursementDate = null;

    public function canEditApplication(TempApplication $application): bool
    {
        return app(TempApplicationDetailEditService::class)->canEdit($application, auth()->user());
    }

    public function openEditApplicationModal(int $applicationId): void
    {
        $application = $this->findEditableApplication($applicationId);

        if (!app(TempApplicationDetailEditService::class)->canEdit($application, auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot edit this application.',
                type: 'error'
            );
            return;
        }

        $phaseApplication = $application->phaseApplication;
        $groupLoan = $phaseApplication?->groupLoan;
        $group = $phaseApplication?->linkedGroup ?? $groupLoan?->group;

        $this->editingApplicationId = $application->id;
        $this->editGroupName = $application->group_name ?? $group?->name ?? '';
        $this->editGroupRegistrationNumber = $application->group_registration_number ?? $group?->reg_number;
        $this->editGroupWardId = $application->ward_id ?? $group?->ward_id;
        $this->editGfsCode = $application->gfs_code ?? $phaseApplication?->gfs_code ?? $groupLoan?->gfs_code;
        $this->editAmountApplied = $this->stringifyAmount($phaseApplication?->amount_applied ?? $application->loan_amount);
        $this->editAmountApproved = $this->stringifyAmount($phaseApplication?->amount_approved ?? $application->amount_approved);
        $this->editAmountIssued = $this->stringifyAmount($phaseApplication?->amount_issued ?? $groupLoan?->amount_issued);
        $this->editAmountRepaid = $this->stringifyAmount($groupLoan?->amount_repaid);
        $this->editDateRegistered = $application->date_registered?->format('Y-m-d');
        $this->editDisbursementDate = optional($phaseApplication?->disbursement_date ?? $groupLoan?->disbursement_date)->format('Y-m-d');

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'edit-application-details-modal');
    }

    public function saveApplicationDetails(): void
    {
        if (!$this->editingApplicationId) {
            return;
        }

        $application = $this->findEditableApplication($this->editingApplicationId);

        try {
            app(TempApplicationDetailEditService::class)->updateDetails($application, auth()->user(), [
                'groupName' => $this->editGroupName,
                'groupRegistrationNumber' => $this->editGroupRegistrationNumber,
                'wardId' => $this->editGroupWardId,
                'gfsCode' => $this->editGfsCode,
                'amountApplied' => $this->editAmountApplied,
                'amountRepaid' => $this->editAmountRepaid,
                'dateRegistered' => $this->editDateRegistered,
                'disbursementDate' => $this->editDisbursementDate,
            ]);

            $this->resetApplicationEditForm();
            $this->dispatch('close-modal', name: 'edit-application-details-modal');
            $this->dispatch('swal:toast',
                title: 'Application Updated',
                message: 'Application details saved successfully.',
                type: 'success'
            );

            if (method_exists($this, 'refreshData')) {
                $this->refreshData();
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            $this->dispatch('swal:toast',
                title: 'Validation Error',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast',
                title: 'Update Failed',
                message: $e->getMessage() ?: 'Could not save application details.',
                type: 'error'
            );
        }
    }

    public function editingApplication(): ?TempApplication
    {
        if (!$this->editingApplicationId) {
            return null;
        }

        return TempApplication::query()
            ->with(['phaseApplication.reviewPhase', 'phaseApplication.groupLoan'])
            ->find($this->editingApplicationId);
    }

    public function editingApplicationAllows(string $field): bool
    {
        $application = $this->editingApplication();

        return $application
            ? in_array($field, app(TempApplicationDetailEditService::class)->editableFieldsFor($application), true)
            : false;
    }

    #[Computed]
    public function applicationEditWardsOptions(): array
    {
        $this->constituency->loadMissing('wards');

        return $this->constituency->wards
            ->map(fn ($ward) => [
                'id' => $ward->id,
                'name' => ucwords(strtolower($ward->ward_name)),
            ])
            ->values()
            ->all();
    }

    protected function findEditableApplication(int $applicationId): TempApplication
    {
        return TempApplication::query()
            ->with([
                'ward',
                'phaseApplication.reviewPhase',
                'phaseApplication.groupLoan.group.ward',
                'phaseApplication.linkedGroup',
            ])
            ->where('constituency_code', $this->constituency->constituency_code)
            ->findOrFail($applicationId);
    }

    protected function stringifyAmount(mixed $value): ?string
    {
        return $value !== null && $value !== '' ? (string) $value : null;
    }

    protected function resetApplicationEditForm(): void
    {
        $this->reset([
            'editingApplicationId',
            'editGroupName',
            'editGroupRegistrationNumber',
            'editGroupWardId',
            'editGfsCode',
            'editAmountApplied',
            'editAmountApproved',
            'editAmountIssued',
            'editAmountRepaid',
            'editDateRegistered',
            'editDisbursementDate',
        ]);
    }
}
