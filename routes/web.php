<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ApprovalsController;
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

Route::middleware(['auth', 'verified'])->group(function () {
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

require __DIR__ . '/auth.php';
