@if($reconciliationType === 'main')
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-amber-50 border-0 py-2">
            <span class="text-xs font-weight-bold text-uppercase text-navy">
                <i class="fas fa-university mr-1"></i> Main Account Bank Reconciliation
            </span>
        </div>
        <div class="card-body pt-1 pb-3">
            @if(($detailedPhase->status ?? null) === 'submitted_to_hq')
                <p class="text-xs text-gray-500">Upload the current main account reconciliation for HQ and Accounts review.</p>
            @else
                <p class="text-xs text-gray-500">Upload the current main constituency account bank reconciliation. Mandatory before HQ submission.</p>
            @endif
            <div class="form-group my-3">
                <label class="small font-weight-bold text-uppercase text-muted mb-2">Statement month</label>
                <input type="month" wire:model="bankReconciliationMainPeriod" max="{{ now()->format('Y-m') }}" min="{{ now()->subMonths(2)->format('Y-m') }}" class="form-control form-control-sm" style="border-radius: 10px;">
                @error('bankReconciliationMainPeriod')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
            </div>
            <div class="form-group my-3 premium-file-container">
                <label class="small font-weight-bold text-uppercase text-muted mb-2">Document (PDF or Image)</label>
                <x-file-upload model="bankReconciliationMainFile" height="48px" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" />
                @error('bankReconciliationMainFile')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button wire:click="uploadBankReconciliationMain" wire:loading.attr="disabled" wire:target="bankReconciliationMainFile, uploadBankReconciliationMain" class="btn btn-refresh-premium btn-sm shadow-sm">
                    <span wire:loading.remove wire:target="uploadBankReconciliationMain"><i class="fas fa-cloud-upload-alt mr-2"></i> Upload main account reconciliation</span>
                    <span wire:loading wire:target="uploadBankReconciliationMain"><i class="fas fa-spinner fa-spin mr-2"></i> Uploading...</span>
                </button>
            </div>
        </div>
    </div>
@else
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-amber-50 border-0 py-2">
            <span class="text-xs font-weight-bold text-uppercase text-navy">
                <i class="fas fa-hand-holding-usd mr-1"></i> Loan Repayment Bank Reconciliation
            </span>
        </div>
        <div class="card-body pt-1 pb-3">
            @if(($detailedPhase->status ?? null) === 'submitted_to_hq')
                <p class="text-xs text-gray-500">Upload the current loan repayment account reconciliation for HQ and Accounts review.</p>
            @else
                <p class="text-xs text-gray-500">Upload the current loan repayment bank reconciliation from the constituency. Mandatory before HQ submission.</p>
            @endif
            <div class="form-group my-3">
                <label class="small font-weight-bold text-uppercase text-muted mb-2">Statement month</label>
                <input type="month" wire:model="bankReconciliationRepaymentPeriod" max="{{ now()->format('Y-m') }}" min="{{ now()->subMonths(2)->format('Y-m') }}" class="form-control form-control-sm" style="border-radius: 10px;">
                @error('bankReconciliationRepaymentPeriod')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
            </div>
            <div class="form-group my-3 premium-file-container">
                <label class="small font-weight-bold text-uppercase text-muted mb-2">Document (PDF or Image)</label>
                <x-file-upload model="bankReconciliationRepaymentFile" height="48px" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" />
                @error('bankReconciliationRepaymentFile')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button wire:click="uploadBankReconciliationLoanRepayment" wire:loading.attr="disabled" wire:target="bankReconciliationRepaymentFile, uploadBankReconciliationLoanRepayment" class="btn btn-refresh-premium btn-sm shadow-sm">
                    <span wire:loading.remove wire:target="uploadBankReconciliationLoanRepayment"><i class="fas fa-cloud-upload-alt mr-2"></i> Upload loan repayment reconciliation</span>
                    <span wire:loading wire:target="uploadBankReconciliationLoanRepayment"><i class="fas fa-spinner fa-spin mr-2"></i> Uploading...</span>
                </button>
            </div>
        </div>
    </div>
@endif
