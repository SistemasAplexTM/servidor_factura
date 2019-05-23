<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::namespace('Auth')->group(function () {
  Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');

    Route::group(['middleware' => 'auth:api'], function() {
      Route::get('logout', 'AuthController@logout');
      Route::get('user', 'AuthController@user');
    });
  });
});

Route::namespace('Document')->group(function () {
  Route::group(['prefix' => 'document'], function () {
    Route::group(['middleware' => 'auth:api'], function() {
      Route::get('getTypes', 'IndexController@getTypes');
      Route::post('getDocuments/{id}', 'IndexController@getDocuments');
      Route::get('testDetail', 'IndexController@TestDetail');
    });
  });
});

Route::get('algo', 'HomeController@algo');
