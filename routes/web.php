<?php

use App\Http\Controllers\HelpController;
use App\Http\Controllers\UserController;
use App\Models\Handbook;
use Illuminate\Support\Facades\Route;

// ------------------- Home route ------------------
Route::get('/', function () {
    return redirect()->route('handbooks.index');
})->name('home');

// ============= Handbook routes =================
Route::prefix('handbooks')->name('handbooks.')->group(function () {
    Route::middleware(['auth', 'can:viewAny,'.Handbook::class])->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/', 'pages::admin.handbooks.index')->name('index');
        Route::livewire('create', 'pages::admin.handbooks.create')
            ->middleware('can:create,'.Handbook::class)
            ->name('create');
        Route::livewire('{handbook}/edit', 'pages::admin.handbooks.edit')->name('edit');
    });

    Route::livewire('/', 'pages::handbooks.index')->name('index');
    Route::livewire('{handbook:slug}/{pageSlug?}', 'pages::handbooks.show')
        ->name('show');
});

// =======Authenticated app routes=======
Route::middleware('auth')->group(function () {
    Route::view('/about', 'about')->name('about');
    Route::view('/contact', 'contact')->name('contact');
    Route::get('/help', [HelpController::class, 'index'])->name('help');
});
// =======User management routes=======
Route::prefix('/users')->name('users.')->middleware(['auth'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/edit/{id}', [UserController::class, 'edit'])->name('edit');
    Route::post('/edit/{id}', [UserController::class, 'update'])->name('update');

    Route::get('/mirror/stop', [UserController::class, 'stop'])->name('mirror.stop');
    Route::get('/mirror/{id}', [UserController::class, 'start'])->name('mirror.start');
});

// =======Guest app routes=======
Route::middleware('guest')->group(function () {});

require __DIR__.'/auth.php';
