<?php
// app/Models/PhaseApplicationDocument.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PhaseApplicationDocument extends Model
{
    protected $fillable = [
        'phase_application_id',
        'document_source',
        'title',
        'document_type',
        'original_filename',
        'filename',
        'path',
        'url',
        'mime_type',
        'size',
        'description',
        'statement_period',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function phaseApplication(): BelongsTo
    {
        return $this->belongsTo(PhaseApplication::class);
    }

    public function loanPhase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class, 'loan_phase_id');
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

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

    public function isMinutes(): bool
    {
        return $this->document_type === 'minutes';
    }

    public function getIconAttribute()
    {
        $extension = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));

        return match($extension) {
            'pdf'          => 'fa-file-pdf',
            'doc', 'docx'  => 'fa-file-word',
            'xls', 'xlsx'  => 'fa-file-excel',
            'jpg', 'jpeg',
            'png', 'gif'   => 'fa-file-image',
            default        => 'fa-file-alt text-muted'
        };
    }

    public function getFormattedSizeAttribute()
    {
        return number_format($this->size / 1024, 1) . ' KB';
    }

    public function getCanViewInlineAttribute(): bool
    {
        if ($this->mime_type === 'application/pdf' || str_starts_with((string) $this->mime_type, 'image/')) {
            return true;
        }

        return in_array($this->resolvedExtension(), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function resolvedExtension(): string
    {
        foreach ([$this->filename, $this->original_filename, $this->path] as $candidate) {
            if (!$candidate) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $candidate, PATHINFO_EXTENSION));
            if ($extension !== '') {
                return $extension;
            }
        }

        return '';
    }

    public function getStatementPeriodLabelAttribute(): ?string
    {
        if (!$this->statement_period || !preg_match('/^\d{4}-\d{2}$/', $this->statement_period)) {
            return null;
        }

        return \Carbon\Carbon::createFromFormat('Y-m', $this->statement_period)->format('F Y');
    }
}
