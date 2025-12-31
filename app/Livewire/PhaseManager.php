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
// use Illuminate\Http\File;


class PhaseManager extends Component
{
    use WithPagination, WithFileUploads;

    public $constituency;
    public $selectedPhase = null;

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

    public function mount($constituency)
    {
        $this->constituency = $constituency;
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

    public function render()
    {
        $phases = LoanReviewPhase::withCount('phaseApplications')
            ->where('constituency_code', $this->constituency->constituency_code)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.phase-manager', [
            'phases' => $phases,
            'pendingApplications' => $this->getPendingApplications(),
        ]);
    }
}
