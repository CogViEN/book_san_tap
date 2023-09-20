<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;


Route::get('/', function () {
    return view('layout_admin.master');
})->name('welcome');

Route::group([
    'as' => 'users.',
    'prefix' => 'users',
], function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/api', [UserController::class, 'api'])->name('api');
    Route::get('/edit{user}', [UserController::class, 'edit'])->name('edit');
    Route::delete('course/{user}', [UserController::class, 'destroy'])->name('destroy');
});
