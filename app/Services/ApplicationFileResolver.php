<?php

namespace App\Services;

use App\Http\Helpers\StoragePathHelper;
use App\Models\TempApplication;
use App\Services\Loan\LoanApplicationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationFileResolver
{
    public function resolve(string $applicationId, string $decodedPath): ?string
    {
        $decodedPath = ltrim($decodedPath, '/');
        $cleanPath = preg_replace('#^(storage/|public/)#', '', $decodedPath) ?? $decodedPath;

        $finalPath = $this->readablePath($cleanPath);
        if ($finalPath) {
            return $finalPath;
        }

        $application = TempApplication::where('application_id', $applicationId)
            ->with('groupMembers')
            ->first();

        if (!$application) {
            return null;
        }

        if (!empty($application->raw_data)) {
            try {
                app(LoanApplicationService::class)->repairApplicationFiles($application->fresh(['groupMembers']));
                $application = $application->fresh(['groupMembers']);

                $finalPath = $this->readablePath($cleanPath);
                if ($finalPath) {
                    return $finalPath;
                }
            } catch (\Throwable $e) {
                Log::warning('Could not repair missing application files', [
                    'application_id' => $applicationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $directoryKey = basename(dirname($cleanPath));
        foreach ($application->all_files ?? [] as $file) {
            $path = $file['data']['path'] ?? null;
            if (!$path || !str_contains($path, $directoryKey)) {
                continue;
            }

            $resolved = $this->readablePath($path);
            if ($resolved) {
                return $resolved;
            }
        }

        if (str_contains($cleanPath, '/member_photos/')) {
            return $this->resolveMemberPhotoPath($application, basename($cleanPath));
        }

        return StoragePathHelper::resolveAbsolutePath($cleanPath);
    }

    private function resolveMemberPhotoPath(TempApplication $application, string $requestedFilename): ?string
    {
        foreach ($application->groupMembers as $member) {
            $photo = $member->photo_member_id;
            if (!is_array($photo) || empty($photo['path'])) {
                continue;
            }

            if (!$this->memberPhotoMatchesRequest($photo, $requestedFilename)) {
                continue;
            }

            $resolved = $this->readablePath($photo['path']);
            if ($resolved) {
                return $resolved;
            }
        }

        return null;
    }

    private function memberPhotoMatchesRequest(array $photo, string $requestedFilename): bool
    {
        $requestedStem = pathinfo($requestedFilename, PATHINFO_FILENAME);
        $knownNames = array_filter([
            basename($photo['path'] ?? ''),
            $photo['filename'] ?? null,
            isset($photo['url']) ? basename($photo['url']) : null,
            $photo['original_filename'] ?? null,
        ]);

        if (in_array($requestedFilename, $knownNames, true)) {
            return true;
        }

        $originalFilename = $photo['original_filename'] ?? null;
        if ($originalFilename) {
            $originalStem = pathinfo($originalFilename, PATHINFO_FILENAME);
            $originalSlug = Str::slug(str_replace(['_', ' '], '-', $originalStem));

            if ($originalSlug !== '' && str_contains($requestedStem, $originalSlug)) {
                return true;
            }

            if (str_starts_with($requestedFilename, $originalStem)) {
                return true;
            }
        }

        return false;
    }

    private function readablePath(string $path): ?string
    {
        $resolved = StoragePathHelper::resolveAbsolutePath($path);

        return $resolved && is_readable($resolved) ? $resolved : null;
    }
}
