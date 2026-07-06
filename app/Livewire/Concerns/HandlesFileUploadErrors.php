<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\WithFileUploads;

trait HandlesFileUploadErrors
{
    use WithFileUploads {
        _finishUpload as protected livewireFinishUpload;
    }

    public function _finishUpload($name, $tmpPath, $isMultiple, $append = false)
    {
        if ($isMultiple) {
            if (empty($tmpPath) || ! is_array($tmpPath)) {
                $this->failFileUpload($name);
            }
        } elseif (empty($tmpPath) || ! isset($tmpPath[0])) {
            $this->failFileUpload($name);
        }

        $this->livewireFinishUpload($name, $tmpPath, $isMultiple, $append);
    }

    protected function failFileUpload(string $name): void
    {
        $this->dispatch('upload:errored', name: $name)->self();

        throw ValidationException::withMessages([
            $name => 'The file failed to upload. Please remove it, select it again, and wait for the upload to finish.',
        ]);
    }

    protected function assertValidUploadedFile(string $property, string $fieldName): void
    {
        $value = $this->{$property};

        if (is_array($value)) {
            throw ValidationException::withMessages([
                $fieldName => 'Invalid upload state. Please remove the file, re-select it, and try again.',
            ]);
        }

        if (! $value || ! is_object($value) || ! method_exists($value, 'getRealPath')) {
            throw ValidationException::withMessages([
                $fieldName => 'No file selected or upload incomplete. Please wait for the upload to finish.',
            ]);
        }

        $uploadPath = $value->getRealPath();
        if (! $uploadPath || ! is_readable($uploadPath)) {
            throw ValidationException::withMessages([
                $fieldName => 'Upload file could not be read. Please re-select the file and try again.',
            ]);
        }
    }
}
