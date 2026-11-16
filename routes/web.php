<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\InterestController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app');
Route::get('/api/csrf', fn () => response()->json(['token' => csrf_token()]))->middleware('throttle:60,1');
Route::post('/api/interests', [InterestController::class, 'store'])->middleware('throttle:interests');
Route::get('/admin/login', [AdminController::class, 'loginForm'])->name('login');
Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:admin-login')->name('admin.login');
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [AdminMessageController::class, 'create'])->name('messages.create');
    Route::get('/messages/audience', [AdminMessageController::class, 'audience'])->name('messages.audience');
    Route::post('/messages', [AdminMessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}', [AdminMessageController::class, 'show'])->whereNumber('message')->name('messages.show');
    Route::get('/messages/{message}/edit', [AdminMessageController::class, 'edit'])->whereNumber('message')->name('messages.edit');
    Route::put('/messages/{message}', [AdminMessageController::class, 'update'])->whereNumber('message')->name('messages.update');
    Route::post('/messages/{message}/send', [AdminMessageController::class, 'send'])->whereNumber('message')->middleware('throttle:10,1')->name('messages.send');
    Route::patch('/interests/{interest}', [AdminController::class, 'update'])->whereNumber('interest')->name('interests.update');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});
