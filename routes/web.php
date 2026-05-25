<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\HrReportsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TravelRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'sw'])) {
        session(['locale' => $locale]);
    }
    return back();
})->name('locale.switch');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\PreventBackHistory::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::patch('/dashboard/supervisor', [DashboardController::class, 'updateSupervisor'])->name('dashboard.supervisor.update');
    Route::get('/approvals', ApprovalsController::class)->name('approvals.index');

    Route::get('/travel-requests',                  [TravelRequestController::class, 'index'])->name('travel-requests.index');
    Route::get('/travel-requests/create',           [TravelRequestController::class, 'create'])->name('travel-requests.create');
    Route::post('/travel-requests',                 [TravelRequestController::class, 'store'])->name('travel-requests.store');
    Route::get('/travel-requests/{travelRequest}',  [TravelRequestController::class, 'show'])->name('travel-requests.show');
    Route::get('/travel-requests/{travelRequest}/edit',  [TravelRequestController::class, 'edit'])->name('travel-requests.edit');
    Route::patch('/travel-requests/{travelRequest}', [TravelRequestController::class, 'update'])->name('travel-requests.update');

    Route::post('/travel-requests/{travelRequest}/approve', [ApprovalController::class, 'store'])->name('travel-requests.approve');
    Route::get('/travel-requests/{travelRequest}/print',   [TravelRequestController::class, 'print'])->name('travel-requests.print');
    Route::get('/travel-requests/{travelRequest}/pdf',     [TravelRequestController::class, 'pdf'])->name('travel-requests.pdf');

    Route::get('/hr/reports',        [HrReportsController::class, 'index'])->name('hr.reports.index');
    Route::get('/hr/reports/export', [HrReportsController::class, 'export'])->name('hr.reports.export');

    Route::middleware(\App\Http\Middleware\EnsureIsAdmin::class)->group(function () {
        Route::get('/users',              [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create',       [UserController::class, 'create'])->name('users.create');
        Route::post('/users',             [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit',  [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}',     [UserController::class, 'update'])->name('users.update');
    });

    Route::delete('/travel-requests/{travelRequest}/cancel', [TravelRequestController::class, 'cancel'])->name('travel-requests.cancel');
    Route::get('/travel-requests/{travelRequest}/download',  [TravelRequestController::class, 'download'])->name('travel-requests.download');

    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Read-only debug endpoint — hardcoded token, remove after debugging
Route::get('/debug/data', function () {
    if (request('token') !== 'nimr-debug-2026') {
        abort(403, 'Invalid or missing token.');
    }

    $units = \App\Models\Unit::orderBy('type')->orderBy('name')->get()
        ->map(fn($u) => [
            'id'        => $u->id,
            'name'      => $u->name,
            'type'      => $u->type,
            'parent_id' => $u->parent_id,
            'is_active' => $u->is_active,
        ]);

    $users = \App\Models\User::with('unit')->orderBy('name')->get()
        ->map(fn($u) => [
            'id'            => $u->id,
            'name'          => $u->name,
            'email'         => $u->email,
            'role'          => $u->role,
            'is_active'     => $u->is_active,
            'unit_id'       => $u->unit_id,
            'unit_name'     => $u->unit?->name,
            'unit_type'     => $u->unit?->type,
            'supervisor_id' => $u->supervisor_id,
        ]);

    return response()->json([
        'units' => $units,
        'users' => $users,
    ], 200, [], JSON_PRETTY_PRINT);
})->name('debug.data');

require __DIR__ . '/auth.php';
