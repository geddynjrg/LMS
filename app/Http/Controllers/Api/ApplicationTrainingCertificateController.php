<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhaseApplication;
use App\Models\TempApplication;
use App\Services\Loan\TrainingCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationTrainingCertificateController extends Controller
{
    public function status(
        string $applicationId,
        TrainingCertificateService $certificateService,
    ): JsonResponse {
        $phaseApplication = $this->resolvePhaseApplication($applicationId);

        if (!$phaseApplication) {
            return response()->json([
                'success' => false,
                'data' => [
                    'available' => false,
                    'eligible_from' => null,
                    'application_id' => $applicationId,
                    'disbursed' => false,
                ],
            ], 404);
        }

        $phaseApplication->loadMissing('loanPhase', 'tempApplication:id,application_id');

        $eligibleFrom = $certificateService->certificateEligibleFrom($phaseApplication->loanPhase);

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $certificateService->isPortalCertificateAvailable($phaseApplication),
                'is_trained' => (bool) $phaseApplication->is_trained,
                'eligible_from' => $eligibleFrom?->toDateString(),
                'application_id' => $applicationId,
                'disbursed' => $phaseApplication->status === 'disbursed',
            ],
        ]);
    }

    public function download(
        string $applicationId,
        TrainingCertificateService $certificateService,
    ): StreamedResponse|JsonResponse {
        $phaseApplication = $this->resolvePhaseApplication($applicationId);

        if (!$phaseApplication) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $phaseApplication->loadMissing('loanPhase', 'tempApplication:id,application_id');

        if (!$certificateService->isPortalCertificateAvailable($phaseApplication)) {
            return response()->json(['error' => 'Training certificate not available'], 403);
        }

        try {
            $path = $certificateService->generateAndStoreIfEligible($phaseApplication)
                ?? $phaseApplication->training_certificate_path;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'error' => collect($exception->errors())->flatten()->first()
                    ?? 'Training certificate not available yet',
            ], 403);
        }

        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'Training certificate not found'], 404);
        }

        $filename = 'training-certificate-'
            . ($phaseApplication->tempApplication?->application_id ?? $applicationId)
            . '.pdf';

        return Storage::disk('local')->download($path, $filename);
    }

    protected function resolvePhaseApplication(string $applicationId): ?PhaseApplication
    {
        $tempApplication = TempApplication::query()
            ->where('application_id', $applicationId)
            ->first();

        if (!$tempApplication) {
            return null;
        }

        $baseQuery = PhaseApplication::query()
            ->where('temp_application_id', $tempApplication->id);

        return (clone $baseQuery)
            ->where('is_trained', true)
            ->latest('id')
            ->first()
            ?? PhaseApplication::query()
                ->where('temp_application_id', $tempApplication->id)
                ->whereIn('status', ['pending_disbursement', 'disbursed'])
                ->latest('id')
                ->first()
            ?? $baseQuery->latest('id')->first();
    }
}
