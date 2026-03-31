<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // if (config('app.env') !== 'local' && !str_starts_with(request()->ip(), '172.')) {
        //     URL::forceScheme('https');
        // }

        // Share the $currentFY variable with EVERY view (*)
        View::composer('*', function ($view) {
            $month = date('n');
            $year = date('Y');

            // Logic: July (7) starts a new FY
            $startYear = ($month >= 7) ? $year : $year - 1;
            $endYear = $startYear + 1;

            // Jul-Sep = Q1, Oct-Dec = Q2, Jan-Mar = Q3, Apr-Jun = Q4
            $quarter = ceil((($month + 5) % 12 + 1) / 3);

            $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
            $suffix = (($quarter % 100) >= 11 && ($quarter % 100) <= 13)
                ? 'th'
                : $ends[$quarter % 10];

            $view->with('currentFY', (object)[
                'start'   => $startYear,
                'end'     => $endYear,
                'label'   => $startYear . '/' . $endYear,
                'quarter' => $quarter,
                'quarter_suffix'  => $quarter . $suffix,
                'full'    => "FY " . $startYear . "/" . $endYear
            ]);
        });
    }
}
