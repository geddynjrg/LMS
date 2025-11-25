<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MeReporting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'constituency_code',
        'financial_year',
        'data_required',
        'unit_of_measure',
        'q1_achieved',
        'q2_achieved',
        'q3_achieved',
        'q4_achieved',
        'annual_achieved',
        'remarks_comments'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('me-indicator')
            ->logOnly(['financial_year', 'data_required', 'q1_achieved', 'q2_achieved', 'q3_achieved', 'q4_achieved', 'remarks_comments'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function constituency()
    {
        return $this->belongsTo(Constituency::class, 'constituency_code', 'constituency_code');
    }

    // Helper method to get current financial year
    public static function getCurrentFinancialYear()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        // Financial year runs from July 1 to June 30
        if ($currentMonth >= 7) {
            // July-Dec: Current year - Next year
            return $currentYear . '-' . ($currentYear + 1);
        } else {
            // Jan-June: Previous year - Current year
            return ($currentYear - 1) . '-' . $currentYear;
        }
    }

    // Helper method to get current quarter
    public static function getCurrentQuarter()
    {
        $month = date('n');
        if ($month >= 7 && $month <= 9) return 'Q1';
        if ($month >= 10 && $month <= 12) return 'Q2';
        if ($month >= 1 && $month <= 3) return 'Q3';
        return 'Q4'; // April-June
    }

}
