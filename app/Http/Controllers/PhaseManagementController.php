<?php
// app/Http/Controllers/PhaseManagementController.php
namespace App\Http\Controllers;

use App\Models\LoanReviewPhase;
use App\Models\PhaseApplication;
use App\Models\PhaseDocument;
use App\Models\TempApplication;
use App\Models\CommitteeMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PhaseManagementController extends Controller
{
    // Create new phase
    public function createPhase(Request $request)
    {
        $request->validate([
            'phase_name' => 'required|string|max:255',
            'review_date' => 'required|date',
            'review_venue' => 'nullable|string|max:255',
            'constituency_code' => 'required|exists:constituencies,constituency_code',
        ]);

        // Generate phase number
        $phaseCount = LoanReviewPhase::where('constituency_code', $request->constituency_code)
            ->count();

        $phase = LoanReviewPhase::create([
            'constituency_code' => $request->constituency_code,
            'phase_number' => 'Phase ' . ($phaseCount + 1) . ' - ' . date('Y'),
            'phase_name' => $request->phase_name,
            'review_date' => $request->review_date,
            'review_venue' => $request->review_venue,
            'constituency_officer_id' => auth()->id(),
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'phase' => $phase,
            'message' => 'Phase created successfully'
        ], 201);
    }

    // Add applications to phase with file extraction
    public function addApplicationsToPhase(Request $request, $phaseId)
    {
        $phase = LoanReviewPhase::findOrFail($phaseId);

        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:temp_applications,id',
        ]);

        DB::transaction(function () use ($phase, $request) {
            foreach ($request->application_ids as $applicationId) {
                $tempApp = TempApplication::with('groupMembers')->find($applicationId);

                // Create phase application
                $phaseApp = PhaseApplication::updateOrCreate(
                    [
                        'loan_review_phase_id' => $phase->id,
                        'temp_application_id' => $applicationId,
                    ],
                    [
                        'amount_applied' => $tempApp->loan_amount,
                        'status' => 'pending',
                    ]
                );

                // Extract and store original application documents
                $this->extractApplicationDocuments($phaseApp, $tempApp);
            }

            // Recalculate phase stats
            $phase->calculateStats();
        });

        return response()->json([
            'success' => true,
            'message' => 'Applications added to phase successfully'
        ]);
    }

    // Extract documents from original application
    private function extractApplicationDocuments(PhaseApplication $phaseApp, TempApplication $tempApp): void
    {
        $documentTypes = [
            'group_registration_certificate' => 'registration_certificate',
            'list_of_members_certificate' => 'members_list',
            'group_meeting_minutes' => 'meeting_minutes',
            'group_credit_history' => 'credit_history',
        ];

        foreach ($documentTypes as $field => $docType) {
            $files = $tempApp->{$field} ?? [];

            if (is_array($files) && !empty($files)) {
                foreach ($files as $file) {
                    if (isset($file['path']) && Storage::exists($file['path'])) {
                        // Create document record
                        \App\Models\PhaseApplicationDocument::create([
                            'phase_application_id' => $phaseApp->id,
                            'document_source' => 'original_application',
                            'document_type' => $docType,
                            'original_filename' => $file['original_filename'] ?? 'Unknown',
                            'filename' => $file['filename'] ?? basename($file['path']),
                            'path' => $file['path'],
                            'url' => $file['url'] ?? null,
                            'mime_type' => $file['mime_type'] ?? 'application/octet-stream',
                            'size' => $file['size'] ?? 0,
                            'description' => 'Original application document',
                        ]);
                    }
                }
            }
        }
    }

    // Upload phase documents (minutes, credit reports, etc.)
    public function uploadPhaseDocument(Request $request, $phaseId)
    {
        $phase = LoanReviewPhase::findOrFail($phaseId);

        $request->validate([
            'document_type' => 'required|in:committee_minutes,additional_notes,credit_reports,supporting_docs,approval_letter,other',
            'document_file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt|max:10240',
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('document_file');
        $originalName = $file->getClientOriginalName();
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();

        // Store in phase-specific directory
        $path = $file->storeAs(
            "phase-documents/{$phase->constituency_code}/{$phase->phase_number}",
            $filename,
            'public'
        );

        $document = PhaseDocument::create([
            'loan_review_phase_id' => $phase->id,
            'document_type' => $request->document_type,
            'original_filename' => $originalName,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'description' => $request->description,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'document' => $document,
            'message' => 'Document uploaded successfully'
        ]);
    }

    // Upload application-specific document in phase
    public function uploadApplicationDocument(Request $request, $phaseId, $applicationId)
    {
        $phaseApp = PhaseApplication::where('loan_review_phase_id', $phaseId)
            ->where('temp_application_id', $applicationId)
            ->firstOrFail();

        $request->validate([
            'document_type' => 'required|string|max:100',
            'document_file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('document_file');
        $originalName = $file->getClientOriginalName();
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();

        // Store in application-specific directory
        $path = $file->storeAs(
            "phase-app-docs/{$phaseApp->reviewPhase->constituency_code}/{$phaseApp->reviewPhase->phase_number}/{$phaseApp->id}",
            $filename,
            'public'
        );

        $document = \App\Models\PhaseApplicationDocument::create([
            'phase_application_id' => $phaseApp->id,
            'document_source' => 'committee_added',
            'document_type' => $request->document_type,
            'original_filename' => $originalName,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'description' => $request->description,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'document' => $document,
            'message' => 'Application document uploaded successfully'
        ]);
    }

    // Get all documents for a phase
    public function getPhaseDocuments($phaseId)
    {
        $phase = LoanReviewPhase::with(['phaseDocuments', 'phaseApplications.documents'])
            ->findOrFail($phaseId);

        $documents = [
            'phase_documents' => $phase->phaseDocuments,
            'application_documents' => $phase->phaseApplications->flatMap(function ($app) {
                return $app->documents;
            }),
            'original_application_docs' => $phase->phaseApplications->flatMap(function ($app) {
                return $app->getAllDocumentsAttribute();
            }),
        ];

        return response()->json([
            'success' => true,
            'documents' => $documents
        ]);
    }

    // Committee review - update application decision
    public function updateCommitteeDecision(Request $request, $phaseId, $applicationId)
    {
        $phaseApp = PhaseApplication::where([
            'loan_review_phase_id' => $phaseId,
            'temp_application_id' => $applicationId,
        ])->firstOrFail();

        $request->validate([
            'status' => 'required|in:approved,rejected,adjusted,deferred',
            'amount_approved' => 'required_if:status,approved,adjusted|numeric|min:0',
            'comments' => 'nullable|string',
            'rejection_reason' => 'required_if:status,rejected|string',
            'is_priority' => 'nullable|boolean',
            'recommendation' => 'nullable|in:strongly_recommend,recommend,neutral,not_recommend',
        ]);

        DB::transaction(function () use ($phaseApp, $request, $phaseId) {
            $phaseApp->update([
                'status' => $request->status,
                'amount_approved' => $request->amount_approved ?? null,
                'comments' => $request->comments,
                'rejection_reason' => $request->rejection_reason,
                'is_priority' => $request->is_priority ?? false,
                'recommendation' => $request->recommendation,
            ]);

            // Update phase stats
            $phase = LoanReviewPhase::find($phaseId);
            $phase->calculateStats();
        });

        return response()->json([
            'success' => true,
            'message' => 'Decision updated successfully'
        ]);
    }

    // Add committee members to phase
    public function addCommitteeMembers(Request $request, $phaseId)
    {
        $phase = LoanReviewPhase::findOrFail($phaseId);

        $request->validate([
            'members' => 'required|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.role' => 'required|string|max:100',
            'members.*.comments' => 'nullable|string',
        ]);

        foreach ($request->members as $memberData) {
            CommitteeMember::updateOrCreate(
                [
                    'loan_review_phase_id' => $phase->id,
                    'user_id' => $memberData['user_id'],
                ],
                [
                    'role' => $memberData['role'],
                    'comments' => $memberData['comments'] ?? null,
                    'attended' => true,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Committee members added successfully'
        ]);
    }

    // Submit phase to HQ
    public function submitToHq($phaseId)
    {
        $phase = LoanReviewPhase::with(['phaseApplications', 'phaseDocuments'])
            ->findOrFail($phaseId);

        // Validation checks
        if (!$phase->canSubmitToHq()) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot submit to HQ. Check that:
                    1. Phase is approved by committee
                    2. Committee minutes are uploaded
                    3. All applications have decisions'
            ], 400);
        }

        $pendingApplications = $phase->phaseApplications()
            ->where('status', 'pending')
            ->count();

        if ($pendingApplications > 0) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot submit to HQ. Some applications are still pending committee decision.'
            ], 400);
        }

        // Update phase status
        $phase->update([
            'status' => 'submitted_to_hq',
            'submitted_to_hq_at' => now(),
        ]);

        // Generate phase summary document
        $summaryPath = $this->generatePhaseSummary($phase);

        return response()->json([
            'success' => true,
            'message' => 'Phase submitted to HQ successfully',
            'summary_path' => $summaryPath,
        ]);
    }

    // Generate comprehensive phase summary
    private function generatePhaseSummary(LoanReviewPhase $phase): string
    {
        $phase->load([
            'constituency',
            'constituencyOfficer',
            'committeeMembers.user',
            'phaseApplications.tempApplication.groupMembers',
            'phaseDocuments',
        ]);

        $data = [
            'phase' => $phase,
            'stats' => $phase->stats,
            'date' => now()->format('F j, Y'),
            'applications' => $phase->phaseApplications,
            'documents' => $phase->phaseDocuments->groupBy('document_type'),
        ];

        // Generate PDF (using DomPDF or similar)
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.phase-summary', $data);

        $filename = "phase-summary-{$phase->constituency_code}-{$phase->phase_number}.pdf";
        $path = "phase-summaries/{$phase->constituency_code}/{$phase->phase_number}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        // Add summary to phase documents
        PhaseDocument::create([
            'loan_review_phase_id' => $phase->id,
            'document_type' => 'approval_letter',
            'original_filename' => $filename,
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'mime_type' => 'application/pdf',
            'size' => Storage::size($path),
            'description' => 'Phase Summary Document for HQ Submission',
            'uploaded_by' => auth()->id(),
        ]);

        return $path;
    }
}
