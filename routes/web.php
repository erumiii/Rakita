<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Employee\BookController;
use App\Http\Controllers\Employee\BorrowController;
use App\Http\Controllers\Employee\HistoryController;
use App\Http\Controllers\Employee\ReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', function () {
    return auth()->user()->role === 'employee' ? redirect('/employee/books') : redirect('/member/books');
})->middleware('auth');

Route::middleware(['auth', 'dbsession', 'role:employee'])->prefix('employee')->name('employee.')->group(function () {
    Route::resource('books', BookController::class)->except(['show']);

    Route::get('/borrowings/create', [BorrowController::class, 'create'])->name('borrowings.create');
    Route::post('/borrowings', [BorrowController::class, 'store'])->name('borrowings.store');
    Route::get('/borrowings/history', [HistoryController::class, 'index'])->name('borrowings.history');
    Route::get('/returns/create', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');
});

Route::middleware(['auth', 'dbsession', 'role:member'])->prefix('member')->name('member.')->group(function () {
    // ponytail: placeholder replaced in Task 8
    Route::get('/books', fn () => 'TODO-member-books');
});
