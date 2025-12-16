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

Route::group([
    'prefix' => 'teste',
], function ($router) {
    Route::post('/iniciar', '\App\Http\Controllers\Jobs\JobsController@iniciar');
    Route::get('/liberar', '\App\Http\Controllers\Jobs\JobsController@liberar');
    Route::get('/parar', '\App\Http\Controllers\Jobs\JobsController@parar');
});

Route::middleware(
    'auth:api'
)->group(function () {
    Route::group([
        'prefix' => 'project',
    ], function ($router) {
        Route::post('create', '\App\Http\Controllers\Projects\ProjectsController@create');
        Route::get('list', '\App\Http\Controllers\Projects\ProjectsController@listAllProjectByUser');
        Route::get('getProject', '\App\Http\Controllers\Projects\ProjectsController@getProject');
        Route::get('teste', '\App\Http\Controllers\Projects\ProjectsController@runQuantumCircuit');
    });
});

//Route::get('/users', action: [UsersController::class, 'index']);