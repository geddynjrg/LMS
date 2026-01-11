<?php
// app/Livewire/PhaseManager.php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\LoanReviewPhase;
use App\Models\TempApplication;
use App\Models\PhaseDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use Livewire\Attributes\Computed;


class PhaseManager extends Component
{
    use WithPagination, WithFileUploads;

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;

    public $constituency;
    public $selectedPhase = null;

    public $activeTab = 'tab-pending';

    // Phase creation
    public $phaseName = '';
    public $reviewDate;
    public $reviewVenue = '';

    // Document uploads
    public $documentFile;
    public $documentType = 'committee_minutes';
    public $documentDescription = '';

    // Modals
    public $showCreateModal = false;
    public $showAddAppsModal = false;
    public $showUploadDocModal = false;
    public $showCommitteeModal = false;

    // Selection
    public $selectedApplications = [];
    public $selectAll = false;

    protected $listeners = ['phaseUpdated' => '$refresh'];

    public function boot(LoanPhaseService $phaseService, TempApplicationService $appService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
    }

    public function mount($constituency)
    {
        $this->constituency = $constituency;
        $this->reviewDate = now()->addDays(7)->format('Y-m-d');
    }

    #[Computed]
    public function activePhases()
    {
        return $this->phaseService->getActivePhasesForConstituency(
            $this->constituency->constituency_code
        );
    }

    #[Computed]
    public function allPhases()
    {
        return $this->phaseService->getDetailedPhases(
            $this->constituency->constituency_code
        );
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedApplications = $this->getPendingApplications()->pluck('id')->toArray();
        } else {
            $this->selectedApplications = [];
        }
    }

    public function createPhase()
    {
        $this->validate([
            'phaseName' => 'required|string|max:255',
            'reviewDate' => 'required|date|after:today',
            'reviewVenue' => 'nullable|string|max:255',
        ]);

        $response = Http::post(route('phase.create'), [
            'constituency_code' => $this->constituency->constituency_code,
            'phase_name' => $this->phaseName,
            'review_date' => $this->reviewDate,
            'review_venue' => $this->reviewVenue,
        ]);

        if ($response->successful()) {
            $this->showCreateModal = false;
            $this->reset(['phaseName', 'reviewDate', 'reviewVenue']);
            session()->flash('success', 'Phase created successfully');
            $this->dispatch('phaseUpdated');
        } else {
            session()->flash('error', 'Failed to create phase');
        }
    }

    public function getPendingApplications()
    {
        return TempApplication::where('constituency_code', $this->constituency->constituency_code)
            ->where('status', 'submitted')
            ->whereDoesntHave('phaseApplication')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function uploadDocument()
    {
        $this->validate([
            'documentFile' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'documentType' => 'required|in:committee_minutes,additional_notes,credit_reports,supporting_docs',
            'documentDescription' => 'nullable|string|max:500',
        ]);

        $response = Http::attach(
            'document_file',
            $this->documentFile->get(),
            $this->documentFile->getClientOriginalName()
        )->post(route('phase.upload-document', $this->selectedPhase), [
            'document_type' => $this->documentType,
            'description' => $this->documentDescription,
        ]);

        if ($response->successful()) {
            $this->showUploadDocModal = false;
            $this->reset(['documentFile', 'documentType', 'documentDescription']);
            session()->flash('success', 'Document uploaded successfully');
            $this->dispatch('phaseUpdated');
        } else {
            session()->flash('error', 'Failed to upload document');
        }
    }

    #[Computed]
public function pendingPhases()
{
    return LoanPhase::where('status', 'draft')
        ->when($this->search, fn($q) => $q->where('phase_name', 'like', "%{$this->search}%"))
        ->withCount('phaseApplications')
        ->latest('created_at')
        ->paginate(10, pageName: 'pending');
}

#[Computed]
public function processingPhases()
{
    return LoanPhase::where('status', 'submitted')
        ->when($this->search, fn($q) => $q->where('phase_name', 'like', "%{$this->search}%"))
        ->withCount('phaseApplications')
        ->latest('submitted_at')
        ->paginate(10, pageName: 'processing');
}

// ... similar for approved, declined, disbursed ...

#[Computed]
public function pendingCount()
{
    return LoanPhase::where('status', 'draft')->count();
}

#[Computed]
public function processingCount()
{
    return LoanPhase::where('status', 'submitted')->count();
}
// Inside your Livewire Component
public function getCountsProperty()
{
    return [
        'pending'    => LoanPhase::where('status', 'draft')->count(),
        'processing' => LoanPhase::where('status', 'submitted')->count(),
        'approved'   => LoanPhase::where('status', 'hq_approved')->count(),
        'declined'   => LoanPhase::where('status', 'hq_declined')->count(),
        'disbursed'  => LoanPhase::where('status', 'completed')->count(),
    ];
}

    public function render()
    {
        $phases = LoanReviewPhase::withCount('phaseApplications')
            ->where('constituency_code', $this->constituency->constituency_code)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.loan.app.phase-manager', [
            'phases' => $this->allPhases(),
            'pendingApplications' => $this->getPendingApplications(),
        ]);
    }
}
