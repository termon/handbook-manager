<?php

use App\Http\Controllers\HelpController;
use App\Http\Controllers\UserController;
use App\Models\Handbook;
use Illuminate\Support\Facades\Route;

// ============= Non-authenticated routes =================
Route::livewire('/handbooks', 'pages::handbooks.index')->name('handbooks.index');
Route::livewire('handbooks/{handbook:slug}/{pageSlug?}', 'pages::handbooks.show')
    ->name('handbooks.show');

// =======Authenticated app routes=======

Route::middleware('auth')->group(function () {
    Route::view('/', 'welcome')->name('home');
    Route::view('/about', 'about')->name('about');
    Route::view('/contact', 'contact')->name('contact');
    Route::get('/help', [HelpController::class, 'index'])->name('help');
});
Route::middleware(['auth', 'can:viewAny,'.Handbook::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('handbooks', 'pages::admin.handbooks.index')->name('handbooks.index');
    Route::livewire('handbooks/create', 'pages::admin.handbooks.create')
        ->middleware('can:create,'.Handbook::class)
        ->name('handbooks.create');
    Route::livewire('handbooks/{handbook}/edit', 'pages::admin.handbooks.edit')->name('handbooks.edit');
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
