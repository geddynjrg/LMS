<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\TempApplication;

class FileUploadHelper
{
    /**
     * Save base64 encoded file to storage
     *
     * @param array $fileData Array containing 'filename' and 'data' (base64)
     * @param string $directory Directory to store the file (e.g., 'applications')
     * @param string|null $disk Storage disk (default: 'local')
     * @return array|null Array with path and url, or null if failed
     */
    public static function saveBase64File(array $fileData, string $directory, ?string $disk = 'local', bool $cleanupDirectory = false): ?array
    {
        if (!isset($fileData['data']) || empty($fileData['data'])) {
            return null;
        }

        try {
            // Cleanup old files in directory if requested
            if ($cleanupDirectory && Storage::disk($disk)->exists($directory)) {
                Storage::disk($disk)->deleteDirectory($directory);
                Storage::disk($disk)->makeDirectory($directory);
                Log::info("Cleaned up directory before saving", ['directory' => $directory]);
            }

            $base64String = $fileData['data'];

            // Remove data URI scheme if present
            if (strpos($base64String, ';base64,') !== false) {
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
            }

            // Decode the base64 string
            $fileContent = base64_decode($base64String);

            if ($fileContent === false) {
                Log::error('Failed to decode base64 file', ['filename' => $fileData['filename'] ?? 'unknown']);
                return null;
            }

            // Generate a unique filename with timestamp
            $originalFilename = $fileData['filename'] ?? 'file';
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $filename = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME))
                . '_' . Str::random(8)
                . '_' . time()
                . '.' . $extension;

            // Ensure directory exists
            $fullPath = $directory . '/' . $filename;

            // Store the file
            Storage::disk($disk)->put($fullPath, $fileContent);

            // Get the file path and URL
            $path = $fullPath;
            $url = Storage::url($fullPath);

            return [
                'original_filename' => $originalFilename,
                'filename' => $filename,
                'path' => $path,
                'url' => $url,
                'mime_type' => self::getMimeTypeFromExtension($extension),
                'size' => strlen($fileContent),
                'saved_at' => time()
            ];

        } catch (\Exception $e) {
            Log::error('Error saving base64 file', [
                'error' => $e->getMessage(),
                'filename' => $fileData['filename'] ?? 'unknown'
            ]);
            return null;
        }
    }

    /**
     * Save multiple base64 files
     */
    public static function saveBase64Files(array $filesData, string $directory, ?string $disk = 'local', bool $cleanupDirectory = false): array
    {
        $savedFiles = [];

        // Cleanup old files in directory if requested
        if ($cleanupDirectory && Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->deleteDirectory($directory);
            Storage::disk($disk)->makeDirectory($directory);
            Log::info("Cleaned up directory before saving multiple files", ['directory' => $directory]);
        }

        foreach ($filesData as $index => $fileData) {
            if (is_array($fileData) && isset($fileData['data'])) {
                $savedFile = self::saveBase64File($fileData, $directory, $disk);
                if ($savedFile) {
                    $savedFiles[] = $savedFile;
                }
            }
        }

        return $savedFiles;
    }

    /**
     * Delete old files from storage if they're no longer referenced
    */
    public static function cleanupUnusedFiles(TempApplication $application, array $newFileData, string $fileType): void
    {
        $oldFiles = $application->{$fileType} ?? [];
        $newFiles = $newFileData[$fileType] ?? [];

        if (empty($oldFiles)) {
            return;
        }

        // If new files are empty but old files exist, keep old files (no change)
        if (empty($newFiles)) {
            Log::info("No new files provided for {$fileType}, keeping existing files", [
                'application_id' => $application->application_id
            ]);
            return;
        }

        // Extract old file paths for comparison
        $oldFilePaths = [];
        if (isset($oldFiles['path'])) {
            // Single file
            $oldFilePaths[] = $oldFiles['path'];
        } elseif (is_array($oldFiles)) {
            // Array of files
            foreach ($oldFiles as $file) {
                if (isset($file['path'])) {
                    $oldFilePaths[] = $file['path'];
                }
            }
        }

        // Extract new file original names for comparison (we'll compare by original filename)
        $newFileNames = [];
        if (isset($newFiles['original_filename'])) {
            // Single file
            $newFileNames[] = $newFiles['original_filename'];
        } elseif (is_array($newFiles)) {
            // Array of files
            foreach ($newFiles as $file) {
                if (isset($file['original_filename'])) {
                    $newFileNames[] = $file['original_filename'];
                }
            }
        }

        // Check if files have actually changed by comparing original filenames
        $oldFileNames = [];
        if (isset($oldFiles['original_filename'])) {
            $oldFileNames[] = $oldFiles['original_filename'];
        } elseif (is_array($oldFiles)) {
            foreach ($oldFiles as $file) {
                if (isset($file['original_filename'])) {
                    $oldFileNames[] = $file['original_filename'];
                }
            }
        }

        // If filenames are the same, don't delete old files
        if (empty(array_diff($oldFileNames, $newFileNames)) && empty(array_diff($newFileNames, $oldFileNames))) {
            Log::info("Files for {$fileType} haven't changed, keeping existing files", [
                'application_id' => $application->application_id,
                'filenames' => $oldFileNames
            ]);
            return;
        }

        // Delete old files
        foreach ($oldFilePaths as $oldPath) {
            if (Storage::exists($oldPath)) {
                Storage::delete($oldPath);
                Log::info("Deleted old file", [
                    'application_id' => $application->application_id,
                    'file_type' => $fileType,
                    'path' => $oldPath
                ]);
            }
        }
    }

    /**
     * Delete directory and all its contents
    */
    public static function deleteDirectory(string $directory, ?string $disk = 'local'): bool
    {
        try {
            if (Storage::disk($disk)->exists($directory)) {
                Storage::disk($disk)->deleteDirectory($directory);
                Log::info("Deleted directory", ['directory' => $directory]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error deleting directory', [
                'error' => $e->getMessage(),
                'directory' => $directory
            ]);
            return false;
        }
    }

    private static function getMimeTypeFromExtension(string $extension): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}
