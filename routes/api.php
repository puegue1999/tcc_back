<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::get('/user', function (Request $request) {
//     Route::get('/', '\App\Http\Controllers\Users\UsersController@index');
// });

// Route::get('/login', '\App\Http\Controllers\Users\UsersController@store')->name('login');

// Route::middleware(
//     'auth:api'
// )->group(function () {
// });
Route::group([
    'prefix' => 'users',
], function ($router) {
    Route::get('/loginUser', '\App\Http\Controllers\Users\UsersController@login');
    Route::post('/createUser', '\App\Http\Controllers\Users\UsersController@create');
});


//Route::get('/users', action: [UsersController::class, 'index']);