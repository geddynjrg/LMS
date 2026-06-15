<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Storage;

class StoragePathHelper
{
    /**
     * Resolve a stored file path across local/public disks and common path variants.
     */
    public static function resolveAbsolutePath(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/');
        $relativePath = preg_replace('#^(storage/|public/)#', '', $relativePath) ?? $relativePath;

        $candidates = array_unique([
            $relativePath,
            'applications/' . ltrim(str_replace('applications/', '', $relativePath), '/'),
        ]);

        foreach (['local', 'public'] as $disk) {
            foreach ($candidates as $candidate) {
                if (Storage::disk($disk)->exists($candidate)) {
                    return Storage::disk($disk)->path($candidate);
                }
            }
        }

        $storageRoot = storage_path('app');
        foreach ($candidates as $candidate) {
            $full = $storageRoot . '/' . $candidate;
            if (is_file($full)) {
                return $full;
            }
        }

        // Fallback: DB path may not match stored filename; use sole file in document folder
        $directory = dirname($relativePath);
        if ($directory !== '.' && $directory !== '') {
            foreach (['local', 'public'] as $disk) {
                if (! Storage::disk($disk)->exists($directory)) {
                    continue;
                }

                $files = Storage::disk($disk)->files($directory);
                if (count($files) === 1) {
                    return Storage::disk($disk)->path($files[0]);
                }
            }
        }

        return null;
    }
}
