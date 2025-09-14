<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserApiController;
use App\Http\Controllers\Admin\MembersApiController;
use App\Http\Controllers\Admin\ConferencesApiController;

// Livewire
use App\Livewire\Admin\VotingManagers;

// Middleware class (direct use instead of alias)
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

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system/api/members')
    ->name('system.api.members.')
    ->group(function () {
        Route::get('/',        [MembersApiController::class, 'index'])->name('index');   // list
        Route::get('/{member}',[MembersApiController::class, 'show'])->name('show');     // view one
        Route::post('/',       [MembersApiController::class, 'store'])->name('store');   // create
        Route::patch('/{member}',[MembersApiController::class, 'update'])->name('update');// update
        Route::delete('/{member}',[MembersApiController::class, 'destroy'])->name('destroy'); // delete
    });

Route::middleware(['auth', EnsureRole::class . ':SuperAdmin,Admin,VotingManager'])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/members', MembersPage::class)->name('members'); // new page
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

// Mutations (adjust roles as you like)
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
        Route::post('/',  [VoterIdsApiController::class,'assign']); // body: { member_id }
        Route::delete('/',[VoterIdsApiController::class,'unassign']); // body: { member_id }
    });

Route::middleware('web')->group(function () {
    Route::get('/c/{token}', ConferencePage::class)->name('public.conference');
    Route::get('/vote/session/{session}', VoteGate::class)->name('public.vote.gate');
    Route::get('/vote/session/{session}/ballot', VotePage::class)->name('public.vote.page');
});


Route::middleware(['auth', EnsureRole::class.':SuperAdmin,Admin,VotingManager'])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/conferences/{conference}/sessions/{session}/export-docx',
            [SessionExportController::class, 'download']
        )->name('sessions.export.docx');
    });

Route::get('/test-docx', function () {
    \PhpOffice\PhpWord\Settings::setTempDir(storage_path('app/phpword-temp'));
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->addSection()->addText('Hello DOCX');
    \Storage::disk('local')->makeDirectory('exports');
    $path = \Storage::path('exports/hello.docx');
    \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    // Validate ZIP
    $ok = false;
    if (file_exists($path) && filesize($path) > 800) {
        $zip = new ZipArchive();
        $ok = ($zip->open($path) === true);
        $zip->close();
    }
    if (!$ok) abort(500, 'DOCX not valid—check zip extension & temp dir perms.');

    while (ob_get_level()) ob_end_clean();
    return response()->file($path, [
        'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'Content-Disposition' => 'attachment; filename="hello.docx"',
        'Content-Length'      => (string) filesize($path),
        'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma'              => 'no-cache',
    ]);
});


require __DIR__ . '/auth.php';
