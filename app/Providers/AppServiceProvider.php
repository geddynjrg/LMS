<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Events\RoleAttached;

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
        Event::listen(RoleAttached::class, function (RoleAttached $event): void {
            if (! in_array($event->role->name, ['hq_officer', 'head_of_secretariat'], true)) {
                return;
            }

            if ($event->model instanceof \App\Models\User) {
                $event->model->clearLocalAssignments();
            }
        });

        if (config('app.env') !== 'local') {
            URL::forceScheme('https');

            $appUrl = config('app.url');
            if (is_string($appUrl) && $appUrl !== '') {
                URL::forceRootUrl(rtrim($appUrl, '/'));
            }

            // Vite dev marker breaks CSS/JS on staging/production (loads localhost:5173).
            $hotFile = public_path('hot');
            if (is_file($hotFile)) {
                @unlink($hotFile);
            }
        }

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
