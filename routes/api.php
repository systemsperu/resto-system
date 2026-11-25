<?php

use App\Http\Controllers\SyncReceiverController;
use Illuminate\Support\Facades\Route;

Route::post('/sync/events', SyncReceiverController::class)->middleware('throttle:60,1');
