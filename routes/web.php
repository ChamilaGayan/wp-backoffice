<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\WebhookController;

Route::get('/auth/redirect', [AuthController::class,'redirectToProvider']);
Route::get('/auth/callback', [AuthController::class,'handleProviderCallback']);

// SPA entry
Route::get('/app', function(){ return view('app'); })->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/api/posts', [PostController::class,'index']);
    Route::post('/api/posts', [PostController::class,'store']);
    Route::put('/api/posts/{post}', [PostController::class,'update']);
    Route::delete('/api/posts/{post}', [PostController::class,'destroy']);
    Route::post('/api/posts/{post}/priority', [PostController::class,'setPriority']);
});

// webhook endpoint (no auth - but implement signature verification)
Route::post('/webhook/wp', [WebhookController::class,'receive']);
