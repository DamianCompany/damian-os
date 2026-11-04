<?php

use App\Http\Controllers\GoogleDriveAuthorizationController;
use App\Http\Controllers\CotizacionServicioTecnicoController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/workshop-files/{type}/{id}/{index?}', \App\Http\Controllers\WorkshopAttachmentController::class)
    ->middleware('auth')->whereNumber('id')->whereNumber('index')->name('workshop.file');

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::get('/admin/google-drive/connect', [GoogleDriveAuthorizationController::class, 'redirect'])
    ->name('google-drive.connect');
Route::get('/google-drive/callback', [GoogleDriveAuthorizationController::class, 'callback'])
    ->name('google-drive.callback');

Route::get('/admin/servicio-tecnico/ordenes/{orden}/cotizacion.pdf', CotizacionServicioTecnicoController::class)
    ->middleware('auth')
    ->name('servicio-tecnico.cotizacion.pdf');
