<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConstituencyController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\LoansController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Artisan;

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
    /*Route::get('/dashboard/users/export/', [TestController::class, 'export']);
    Route::middleware('role:super-admin|admin')->get('/test-role', function () {
        return 'You are admin';
    });*/


    Route::middleware('role:super-admin|admin')->prefix('{county}/users')            // dynamic county + constituency
        ->where([
            // optional regex constraints (slug‑style: letters, numbers, hyphens) users.viewuser
            'county'       => '[A-Za-z0-9\-]+',
        ])->name('users.')->group(function () {
            Route::get('/', [UsersController::class, 'index'])->name('viewusers');
            Route::get('/add', [UsersController::class, 'create'])->name('adduser');
            Route::get('/{user}', [UsersController::class, 'viewUser'])->name('viewuser');
    });



    Route::middleware(['currentConstituency'])
        ->prefix('{county}/{constituency}')            // dynamic county + constituency
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
                Route::get('codelist', [ReportsController::class, 'codeList'])->name('codelist');
                Route::get('/disbursement', [ReportsController::class, 'disbursement'])->name('disbursement');
                Route::get('/dynamic-report', [ReportsController::class, 'dynamicReport'])->name('dynamic-report');
                Route::get('/data-upload', [ReportsController::class, 'dataUpload'])->name('data-upload');
                Route::get('/export', [ReportsController::class, 'export'])->name('export');
            });



        }
    );

    //Route::middleware(['currentConstituency'])->group(function ()
    //{



















    //});
});
