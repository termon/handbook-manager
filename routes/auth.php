<?php

use App\Http\Controllers\Auth\{
    ConfirmationController, 
    LoginController, 
    NewPasswordController, 
    PasswordResetLinkController, 
    RegistrationController, 
    VerificationController
};
use App\Http\Controllers\Settings\{
    AppearanceController, 
    PasswordController, 
    ProfileController
};
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegistrationController::class, 'create'])->name('register');
    Route::post('register', [RegistrationController::class, 'store']);

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::post('verify-email', [VerificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.store');
    Route::get('verify-email/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::get('confirm-password', [ConfirmationController::class, 'create'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmationController::class, 'store'])->middleware('throttle:6,1')->name('confirmation.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('settings', [ProfileController::class, 'show'])->name('settings.profile.show');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::put('settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('settings.profile.destroy');
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('settings.password.update');
    Route::get('settings/appearance', [AppearanceController::class, 'edit'])->name('settings.appearance.edit');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});


