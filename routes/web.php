<?php

use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\PublicProductController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/p/{publicUuid}', PublicProductController::class)
    ->name('products.public');

Route::get('/invitations/accept/{token}', InvitationAcceptController::class)
    ->middleware('throttle:10,1')
    ->name('invitation.accept');
