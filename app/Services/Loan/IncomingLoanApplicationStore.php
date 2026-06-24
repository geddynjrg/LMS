<?php

namespace App\Services\Loan;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IncomingLoanApplicationStore
{
    private const DIRECTORY = 'incoming-loan-applications';

    private function ensureDirectory(): void
    {
        if (!Storage::disk('local')->exists(self::DIRECTORY)) {
            Storage::disk('local')->makeDirectory(self::DIRECTORY);
        }
    }

    public function pathFor(string $applicationId): string
    {
        $safeId = preg_replace('/[^A-Za-z0-9._-]/', '_', $applicationId) ?: 'unknown';

        return self::DIRECTORY . '/' . $safeId . '.json';
    }

    public function put(string $applicationId, array $payload): string
    {
        $this->ensureDirectory();

        $path = $this->pathFor($applicationId);
        $written = Storage::disk('local')->put(
            $path,
            json_encode($payload, JSON_UNESCAPED_UNICODE),
        );

        if ($written === false || !Storage::disk('local')->exists($path)) {
            Log::error('Failed to persist incoming loan application payload', [
                'application_id' => $applicationId,
                'path' => $path,
            ]);

            throw new \RuntimeException(
                "Could not store loan application payload for {$applicationId}",
            );
        }

        return $path;
    }

    public function exists(string $applicationId): bool
    {
        return Storage::disk('local')->exists($this->pathFor($applicationId));
    }

    public function get(string $applicationId): ?array
    {
        $path = $this->pathFor($applicationId);
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $decoded = json_decode(Storage::disk('local')->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function delete(string $applicationId): void
    {
        $path = $this->pathFor($applicationId);
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * @return list<string>
     */
    public function listApplicationIds(): array
    {
        $files = Storage::disk('local')->files(self::DIRECTORY);
        $ids = [];

        foreach ($files as $file) {
            if (!str_ends_with($file, '.json')) {
                continue;
            }

            $basename = basename($file, '.json');
            if ($basename !== '') {
                $ids[] = $basename;
            }
        }

        return $ids;
    }
}
