<?php

namespace App\Livewire\Reports;

use App\Models\Constituency;
use App\Services\Restore\RestoreFromBackupService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RestoreDeletedData extends Component
{
  public Constituency $constituency;

  public ?string $selectedBackupPath = null;

  /** @var list<string> */
  public array $conflictPreview = [];

  /** @var array<string, int> */
  public array $restoreSummary = [];

  public bool $showConfirmModal = false;

  public string $statusMessage = '';

  public string $statusType = '';

  public function mount(Constituency $constituency): void
  {
    $this->constituency = $constituency;
  }

  public function selectBackup(string $backupPath, RestoreFromBackupService $restoreService): void
  {
    $this->resetSelectionState();
    $this->selectedBackupPath = $backupPath;

    try {
      $preview = $restoreService->previewRestore($backupPath);
      $this->conflictPreview = $preview['conflicts'];
      $this->restoreSummary = $preview['summary'];
    } catch (ValidationException $exception) {
      $this->statusType = 'danger';
      $this->statusMessage = collect($exception->errors())->flatten()->first()
        ?? 'Could not preview this backup.';
    }
  }

  public function openConfirmModal(): void
  {
    if (!$this->selectedBackupPath || $this->conflictPreview !== []) {
      return;
    }

    $this->showConfirmModal = true;
  }

  public function closeConfirmModal(): void
  {
    $this->showConfirmModal = false;
  }

  public function confirmRestore(RestoreFromBackupService $restoreService): void
  {
    $user = auth()->user();

    if (!$this->selectedBackupPath) {
      return;
    }

    try {
      $restoreService->restore(
        $this->selectedBackupPath,
        $user,
        $this->constituency->constituency_code,
      );

      $this->statusType = 'success';
      $this->statusMessage = 'Data was restored successfully from backup.';
      $this->closeConfirmModal();
      $this->resetSelectionState();
    } catch (ValidationException $exception) {
      $this->conflictPreview = collect($exception->errors())->flatten()->all();
      $this->statusType = 'danger';
      $this->statusMessage = 'Restore was blocked because of conflicts.';
      $this->closeConfirmModal();
    }
  }

  public function render(RestoreFromBackupService $restoreService)
  {
    $user = auth()->user();
    $canRestore = $restoreService->userCanRestore($user, $this->constituency->constituency_code);

    return view('livewire.reports.restore-deleted-data', [
      'backups' => $canRestore
        ? $restoreService->listBackupsForConstituency($this->constituency->constituency_code)
        : collect(),
      'canRestore' => $canRestore,
    ]);
  }

  protected function resetSelectionState(): void
  {
    $this->selectedBackupPath = null;
    $this->conflictPreview = [];
    $this->restoreSummary = [];
  }
}
