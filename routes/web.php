<?php

use App\Http\Controllers\GoogleDriveAuthorizationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::get('/admin/google-drive/connect', [GoogleDriveAuthorizationController::class, 'redirect'])
    ->name('google-drive.connect');
Route::get('/google-drive/callback', [GoogleDriveAuthorizationController::class, 'callback'])
    ->name('google-drive.callback');
