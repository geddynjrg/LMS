<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConstituencyController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\LoansController;
use App\Http\Controllers\MeReportingController;
use App\Http\Controllers\Hq\HqReviewController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Hq\HqApplications;
use App\Http\Controllers\Hq\HqGroupController;
use App\Http\Controllers\Hq\HqLoanController;
use App\Http\Controllers\Hq\HqMemberController;
use App\Http\Controllers\Hq\HqReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
use Illuminate\Http\Request;

Route::get('/debug-ip', function (Request $request) {
    return response()->json([
        'ip_laravel_sees' => $request->ip(),
        'all_ips_in_chain' => $request->ips(),
        'is_ipv4' => filter_var($request->ip(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? true : false,
        'is_ipv6' => filter_var($request->ip(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? true : false,
        'user_agent' => $request->userAgent(),
    ]);
});

Route::get('/', function () {
    return view('auth.login');
})->middleware('guest');

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:clear');
    Artisan::call('optimize');
    dd('Cache cleared successfully');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- HQ / National Administration Routes ---
    Route::middleware(['role:super-admin|hq_officer'])
    ->prefix('hq')
    ->name('hq.')
    ->group(function () {

        // Submissions Management
        Route::prefix('applications')->name('submissions.')->group(function () {
            // View all phases submitted to HQ
            Route::get('/', [HqApplications::class, 'index'])->name('apps.index');

            // 2. Approval Tasks (The Batches/Phases)
            Route::get('/approval-tasks', [HqApplications::class, 'tasks'])->name('tasks.phases');

            // Approval/Action routes (Likely Livewire methods, but route defined for direct access)
            Route::get('/{phase}/review', [HqApplications::class, 'review'])->name('phase.review');
        });

        Route::prefix('groups')->name('groups.')->group(function () {
            Route::get('/dashboard', [HqGroupController::class, 'dashboard'])->name('dashboard');
            Route::get('/list', [HqGroupController::class, 'index'])->name('index');
        });

        // Members
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/dashboard', [HqMemberController::class, 'dashboard'])->name('dashboard');
            Route::get('/list', [HqMemberController::class, 'index'])->name('index');
            Route::get('/contagious', [HqMemberController::class, 'contagious'])->name('contagious');
        });

        // Loans
        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/repayments', [HqLoanController::class, 'repayments'])->name('repayments');
            Route::get('/phases', [HqLoanController::class, 'phases'])->name('phases');
            Route::get('/receipts', [HqLoanController::class, 'receipts'])->name('receipts');
        });

        // Reports
        Route::get('/reports', [HqReportController::class, 'index'])->name('reports.index');


        // Global Reports (National level)
        Route::get('/hq-reports', [ReportsController::class, 'nationalIndex'])->name('reports');
    });

    Route::middleware('role:super-admin|constituency_officer')->prefix('{county}/users')
        ->where([
            // optional regex constraints (slug‑style: letters, numbers, hyphens) users.viewuser
            'county'       => '[A-Za-z0-9\-]+',
        ])->name('users.')->group(function () {
            Route::get('/', [UsersController::class, 'index'])->name('viewusers');
            Route::get('/add', [UsersController::class, 'create'])->name('adduser');
            Route::get('/{user}', [UsersController::class, 'viewUser'])->name('viewuser');
    });

    Route::middleware(['currentConstituency'])
        ->prefix('{county}/{constituency}')
        ->where([
            // optional regex constraints (slug‑style: letters, numbers, hyphens)
            'county'       => '[A-Za-z0-9\-]+',
            'constituency' => '[A-Za-z0-9\-]+',
        ])
        ->name('const.')
        ->group(function () {
            Route::get('/dashboard', [ConstituencyController::class, 'index'])->name('dashboard');

            Route::prefix('groups')->name('groups.')->group(function () {
                Route::get('/', [GroupsController::class, 'groupsInfo'])->name('index');
                Route::get('/members', [GroupsController::class, 'groupmembers'])->name('members');
                Route::get('/multi-group-members', [GroupsController::class, 'multiGroupMembers'])->name('multi-group-members');
                Route::get('/{group}', [GroupsController::class, 'groupInfo'])->name('group');
            });
            Route::prefix('loans')->name('loans.')->group(function () {
                Route::get('/phases', [LoansController::class, 'loanphase'])->name('phases');
                Route::get('/members', [LoansController::class, 'members'])->name('loanmembers');
                Route::get('/receipts', [LoansController::class, 'receipts'])->name('loanreceipts');
                Route::get('/application', [LoansController::class, 'application'])->name('application');
                Route::get('/applications', [LoansController::class, 'applicants'])->name('applicants');
                Route::get('/application/{group}', [LoansController::class, 'applicant'])->name('applicant');
                Route::get('/', [LoansController::class, 'groupLoans'])->name('grouploans');
                Route::get('/{group}', [LoansController::class, 'groupLoan'])->name('grouploan');
                Route::get('/members', [LoansController::class, 'members'])->name('loanmembers');
            });
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('loan-sub-form', [ReportsController::class, 'codeList'])->name('codelist');
                Route::get('/disbursement', [ReportsController::class, 'disbursement'])->name('disbursement');
                Route::get('/dynamic-report', [ReportsController::class, 'dynamicReport'])->name('dynamic-report');
                Route::get('/data-upload', [ReportsController::class, 'dataUpload'])->name('data-upload');
                Route::get('/export', [ReportsController::class, 'export'])->name('export');
            });
            Route::prefix('monitoring')->name('monitoring.')->group(function () {
                Route::get('/report', [MeReportingController::class, 'home'])->name('monitor_home');
                Route::get('/dataview', [MeReportingController::class, 'data'])->name('data_view');
            });

            Route::prefix('applications')->name('app.')->group(function () {
                // Dashboard - Shows all applications
                Route::get('/', [LoansController::class, 'app_dashboard'])->name('app_dashboard');

                // Phases Management (Single page for all phase operations)
                Route::get('/phases', [LoansController::class, 'app_phases'])->name('app_phases');

                // Review Session (Only accessible when a phase is in_review)
                Route::get('/review/{phase}', [LoansController::class, 'app_review'])->name('app_review');

                // Phase Details View
                Route::get('/phases/{phase}', [LoansController::class, 'app_phase_details'])->name('app_phase_details');

                // Existing routes for application viewing
                Route::get('/loans', [LoansController::class, 'app_loans'])->name('app_loans');
                Route::get('/{applicationId}', [LoansController::class, 'app_loan'])->name('application-view');
                Route::get('/{applicationId}/file/{action}', [LoansController::class, 'app_loan_files_download'])->name('application.file.download');
            });



        }
    );


    Route::get('/phase-document/{id}/{action}', function ($id, $action) {
        $doc = \App\Models\PhaseApplicationDocument::findOrFail($id);
        $path = storage_path('app/public/' . $doc->path);

        if (!file_exists($path)) {
            abort(404, "File not found on server disk.");
        }

        if ($action === 'view') {
            return response()->file($path, [
                'Content-Disposition' => 'inline; filename="'.$doc->original_filename.'"'
            ]);
        }

        return response()->download($path, $doc->original_filename);
    })->name('phase.doc.serve');
});
