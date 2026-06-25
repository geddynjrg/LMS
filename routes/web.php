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
use App\Http\Controllers\Hq\HqConstituencyAllocationController;
use App\Http\Controllers\Hq\HqReportController;
use App\Http\Controllers\Secretariat\SecretariatController;
use App\Http\Controllers\Accountant\AccountantController;
use App\Http\Controllers\PhaseDocumentController;
use App\Http\Controllers\HelpController;

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
use Illuminate\Support\Facades\Auth;

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

    // Fallback for direct GET /logout (Fortify only registers POST)
    Route::get('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // --- HQ / National Administration Routes ---
    Route::middleware(['role:super-admin|hq_officer'])
    ->prefix('hq')
    ->name('hq.')
    ->group(function () {

        // Submissions Management
        Route::prefix('applications')->name('submissions.')->group(function () {
            // Static routes first
            Route::get('/', [HqApplications::class, 'index'])->name('apps.index');
            Route::get('/approval-tasks', [HqApplications::class, 'tasks'])->name('tasks.phases');
            Route::get('/phases/{phase}/processed-code-list', [\App\Http\Controllers\ProcessedCodeListController::class, 'download'])->name('phase.processed_code_list');
            Route::get('/phases/{phase}/processed-code-list/preview', [\App\Http\Controllers\ProcessedCodeListController::class, 'preview'])->name('phase.processed_code_list.preview');
            Route::get('/phases/{phase}/approved-code-list', [\App\Http\Controllers\ProcessedCodeListController::class, 'downloadSigned'])->name('phase.approved_code_list');

            // Wildcard routes last
            Route::get('/{applicationId}', [HqApplications::class, 'appView'])->name('app.view');
            Route::get('/{phase}/review', [HqApplications::class, 'review'])->name('phase.review');
            Route::get('/{applicationId}/file/{action}', [HqApplications::class, 'app_files_download'])->name('app.file.download');
            Route::get('/{applicationId}/file/member/{action}', [HqApplications::class, 'app_member_files_download'])->name('app.file.member.download');

        });

        Route::prefix('groups')->name('groups.')->group(function () {
            Route::get('/dashboard', [HqGroupController::class, 'dashboard'])->name('dashboard');
            Route::get('/list', [HqGroupController::class, 'index'])->name('index');
            Route::get('/groups/{slug}', [HqGroupController::class, 'group'])->name('group');
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
            Route::get('/group-loans', [HqLoanController::class, 'groupLoans'])->name('group_loans');
        });

        // Reports
        Route::get('/reports', [HqReportController::class, 'index'])->name('reports.index');

        Route::prefix('allocations')->name('allocations.')->group(function () {
            Route::get('/', [HqConstituencyAllocationController::class, 'index'])->name('index');
            Route::get('/{constituency}/template', [HqConstituencyAllocationController::class, 'downloadTemplate'])->name('template');
        });


        // Global Reports (National level)
        Route::get('/hq-reports', [ReportsController::class, 'nationalIndex'])->name('reports');
    });

    Route::middleware(['role:super-admin|head_of_secretariat'])
        ->prefix('secretariat')
        ->name('secretariat.')
        ->group(function () {
            Route::get('/phases', [SecretariatController::class, 'index'])->name('phases.index');
            Route::get('/phases/{phase}/review', [SecretariatController::class, 'review'])->name('phases.review');
            Route::get('/phases/{phase}/code-list', [\App\Http\Controllers\ProcessedCodeListController::class, 'download'])->name('phases.code-list');
            Route::get('/phases/{phase}/processed-code-list/preview', [\App\Http\Controllers\ProcessedCodeListController::class, 'preview'])->name('phases.processed_code_list.preview');
            Route::get('/phases/{phase}/approved-code-list', [\App\Http\Controllers\ProcessedCodeListController::class, 'downloadSigned'])->name('phases.approved_code_list');
        });

    Route::middleware(['role:super-admin|accountant'])
        ->prefix('accountant')
        ->name('accountant.')
        ->group(function () {
            Route::get('/phases', [AccountantController::class, 'index'])->name('phases.index');
            Route::get('/phases/{phase}/review', [AccountantController::class, 'review'])->name('phases.review');
            Route::get('/phases/{phase}/processed-code-list', [\App\Http\Controllers\ProcessedCodeListController::class, 'download'])->name('phases.processed_code_list');
            Route::get('/phases/{phase}/processed-code-list/preview', [\App\Http\Controllers\ProcessedCodeListController::class, 'preview'])->name('phases.processed_code_list.preview');
        });

    Route::middleware(['auth', 'role:super-admin|constituency_officer|head_of_secretariat'])
        ->get('/training-certificates/{phaseApplication}', [SecretariatController::class, 'downloadTrainingCertificate'])
        ->name('training-certificate.download');

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
                Route::get('/phases/{phase}/signed-code-list', [LoansController::class, 'downloadSignedCodeList'])->name('app_phase_signed_code_list');
                Route::get('/phases/{phase}/processed-code-list', [LoansController::class, 'downloadProcessedCodeList'])->name('app_phase_processed_code_list');
                Route::get('/phases/{phase}/processed-code-list/preview', [\App\Http\Controllers\ProcessedCodeListController::class, 'preview'])->name('app_phase_processed_code_list.preview');
                Route::get('/phases/{phase}/letter-of-authority', [LoansController::class, 'downloadLetterOfAuthority'])->name('app_phase_letter_of_authority');

                // Existing routes for application viewing
                Route::get('/loans', [LoansController::class, 'app_loans'])->name('app_loans');
                Route::get('/{applicationId}', [LoansController::class, 'app_loan'])->name('application-view');
                Route::get('/{applicationId}/file/{action}', [LoansController::class, 'app_loan_files_download'])->name('application.file.download');
                Route::get('/{applicationId}/file/member/{action}', [LoansController::class, 'app_member_files_download'])->name('application.file.member.download');
            });



        }
    );

    Route::get('/phase-document/{id}/preview', [PhaseDocumentController::class, 'preview'])->name('phase.doc.preview');
    Route::get('/phase-document/{id}/{action}', [PhaseDocumentController::class, 'serve'])->name('phase.doc.serve');
});

