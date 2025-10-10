<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserApiController;
use App\Http\Controllers\Admin\MembersApiController;
use App\Http\Controllers\Admin\ConferencesApiController;
use App\Livewire\Admin\VotingManagers;
use App\Http\Middleware\EnsureRole;
use App\Livewire\Admin\MembersPage;
use App\Livewire\Admin\ConferencesIndex;
use App\Livewire\Admin\ConferencesDetail;
use App\Livewire\Admin\SessionShow;
use App\Http\Controllers\Admin\VoterIdsApiController;
use App\Livewire\Public\ConferencePage;
use App\Livewire\Public\VoteGate;
use App\Livewire\Public\VotePage;
use App\Http\Controllers\Admin\SessionExportController;
use App\Http\Controllers\Admin\ConferenceQrController;
use App\Livewire\Public\VoteWizard;

Route::get('/', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin,Admin,VotingManager',
    ])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin',
    ])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
    });

Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin,Admin',
    ])
    ->prefix('system/api/users')
    ->name('system.api.users.')
    ->group(function () {
        Route::get('/',    [UserApiController::class, 'index'])->name('index');
        Route::post('/',   [UserApiController::class, 'store'])->name('store');
        Route::patch('/{user}', [UserApiController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserApiController::class, 'destroy'])->name('destroy'); 
    });

Route::middleware([
        'auth',
        EnsureRole::class . ':SuperAdmin,Admin',
    ])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/voting-managers', VotingManagers::class)->name('voting-managers');
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system/api/members')
    ->name('system.api.members.')
    ->group(function () {
        Route::get('/',        [MembersApiController::class, 'index'])->name('index');
        Route::get('/{member}',[MembersApiController::class, 'show'])->name('show');
        Route::post('/',       [MembersApiController::class, 'store'])->name('store');
        Route::patch('/{member}',[MembersApiController::class, 'update'])->name('update');
        Route::delete('/{member}',[MembersApiController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/members', MembersPage::class)->name('members');
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/conferences/{conference}', ConferencesDetail::class)->name('conferences.show');
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system/api/conferences')
    ->name('system.api.conferences.')
    ->group(function () {
        Route::post('/', [ConferencesApiController::class, 'store'])->name('store');
        Route::patch('/{conference}', [ConferencesApiController::class, 'update'])->name('update');
        Route::delete('/{conference}', [ConferencesApiController::class, 'destroy'])->name('destroy');
        Route::get('/', [ConferencesApiController::class, 'index'])->name('index');
        Route::get('/{conference}', [ConferencesApiController::class, 'show'])->name('show');
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system/api/conferences/{conference}/sessions')
    ->name('system.api.sessions.')
    ->group(function () {
        Route::get('/', [VotingSessionsApiController::class, 'index'])->name('index');
        Route::get('/{session}', [VotingSessionsApiController::class, 'show'])->name('show');
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system/api/conferences/{conference}/sessions')
    ->name('system.api.sessions.')
    ->group(function () {
        Route::post('/', [VotingSessionsApiController::class, 'store'])->name('store');
        Route::patch('/{session}', [VotingSessionsApiController::class, 'update'])->name('update');
        Route::delete('/{session}', [VotingSessionsApiController::class, 'destroy'])->name('destroy');
        Route::patch('/{session}/end', [VotingSessionsApiController::class, 'end'])->name('end');
    });

Route::middleware(['auth', \App\Http\Middleware\EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system')->name('system.')->group(function () {
        Route::get('/conferences/{conference}/sessions/{session}', SessionShow::class)
            ->name('sessions.show');
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system/api/sessions/{session}/voter-ids')->name('system.api.voterids.')->group(function () {
        Route::post('/',  [VoterIdsApiController::class,'assign']);
        Route::delete('/',[VoterIdsApiController::class,'unassign']);
    });

Route::middleware('web')->group(function () {
    Route::get('/c/{token}', VoteWizard::class)->name('public.conference');
});


Route::middleware(['auth', EnsureRole::class.':SuperAdmin,Admin,VotingManager'])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/conferences/{conference}/sessions/{session}/export-docx',
            [SessionExportController::class, 'download']
        )->name('sessions.export.docx');
    });

Route::middleware(['auth']) 
    ->get('/admin/conferences/{conference}/qr', [ConferenceQrController::class, 'download'])
    ->name('admin.conferences.qr.download');


Route::get('/admin/members/template', function () {
    $csv = implode("\n", [
        'title,first_name,last_name,email,bio',
        'Mr.,Jane,Doe,jane@example.com,Short bio here',
        'Ms,Sam,Lee,,',
    ])."\n";

    return response()->streamDownload(
        fn() => print($csv),
        'members_template.csv',
        ['Content-Type' => 'text/csv; charset=UTF-8']
    );
})->middleware(['auth','verified'])->name('admin.members.template');

require __DIR__ . '/auth.php';
