<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\LogActivity;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackController;
use App\Http\Controllers\UserController;

Route::middleware([LogActivity::class])->group(function () {
    Route::get('/your-route', [YourController::class, 'yourMethod']);
    Route::post('/another-route', [AnotherController::class, 'anotherMethod']);

    Route::prefix('dapur')->middleware('auth')->group(function () {
        Route::get('/dashboard', [BackController::class, 'index'])->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('user.index');
        Route::post('/user/add', [UserController::class, 'store'])->name('user.add');
        Route::get('/user/get/{id}', [UserController::class, 'getAdmin'])->name('user.detail');
        Route::put('/user/update/{id}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/user/delete/{id}', [UserController::class, 'softdelete'])->name('user.softdelete');
    });
});

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/act_login', [AuthController::class, 'login'])->name('login.action');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Route::get('/', function () {
//     return view('welcome');
// });
