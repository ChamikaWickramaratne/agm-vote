<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserApiController;

// Livewire
use App\Livewire\Admin\VotingManagers;

// Middleware class (direct use instead of alias)
use App\Http\Middleware\EnsureRole;

/**
 * Landing/dashboard
 */
Route::get('/', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/**
 * Profile (authenticated users)
 */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * Admin area (SuperAdmin, Admin, VotingManager)
 * NOTE: using middleware class directly with parameters
 */
Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin,Admin,VotingManager',
    ])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        // Candidate/Session/Report routes here...
    });

/**
 * System area (SuperAdmin only)
 */
Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin',
    ])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        // user management, role assignment, system-level settings...
    });

/**
 * Users API (SuperAdmin, Admin) - create/manage Voting Managers
 */
Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin,Admin',
    ])
    ->prefix('system/api/users')
    ->name('system.api.users.')
    ->group(function () {
        Route::get('/',    [UserApiController::class, 'index'])->name('index');     // list users (optional)
        Route::post('/',   [UserApiController::class, 'store'])->name('store');     // create VotingManager
        Route::patch('/{user}', [UserApiController::class, 'update'])->name('update');  // optional
        Route::delete('/{user}', [UserApiController::class, 'destroy'])->name('destroy'); // optional
    });

/**
 * Voting Managers UI (SuperAdmin, Admin)
 */
Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin,Admin',
    ])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/voting-managers', VotingManagers::class)->name('voting-managers');
    });

require __DIR__ . '/auth.php';
