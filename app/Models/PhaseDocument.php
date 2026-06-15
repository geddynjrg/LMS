<?php
// app/Models/PhaseDocument.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PhaseDocument extends Model
{
    protected $fillable = [
        'loan_phase_id',
        'document_type',
        'original_filename',
        'filename',
        'path',
        'url',
        'mime_type',
        'size',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    // Relationships
    public function phase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class, 'loan_phase_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        return $this->url ?: Storage::url($this->path);
    }

    public function getFileSizeAttribute(): string
    {
        if ($this->size < 1024) {
            return $this->size . ' bytes';
        } elseif ($this->size < 1048576) {
            return round($this->size / 1024, 2) . ' KB';
        } else {
            return round($this->size / 1048576, 2) . ' MB';
        }
    }

    public function getFileIconAttribute(): string
    {
        $extension = pathinfo($this->original_filename, PATHINFO_EXTENSION);

        $icons = [
            'pdf' => 'fa-file-pdf text-danger',
            'doc' => 'fa-file-word text-primary',
            'docx' => 'fa-file-word text-primary',
            'xls' => 'fa-file-excel text-success',
            'xlsx' => 'fa-file-excel text-success',
            'jpg' => 'fa-file-image text-warning',
            'jpeg' => 'fa-file-image text-warning',
            'png' => 'fa-file-image text-warning',
            'txt' => 'fa-file-alt text-secondary',
        ];

        return $icons[$extension] ?? 'fa-file text-secondary';
    }

    public function getDocumentTypeText(): string
    {
        $types = [
            'committee_minutes' => 'Committee Minutes',
            'additional_notes' => 'Additional Notes',
            'credit_reports' => 'Credit Reports',
            'supporting_docs' => 'Supporting Documents',
            'approval_letter' => 'Approval Letter',
            'other' => 'Other Documents',
        ];

        return $types[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type));
    }
}
