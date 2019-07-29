<?php

use Illuminate\Http\Request;
use App\User;
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

Route::group(['prefix' => 'global'], function () {
 Route::group(['middleware' => 'auth:api'], function() {
 Route::get('validateCashRegister/{branch}', 'GlobalController@validateCashRegister');
 Route::post('saveCashRegister', 'GlobalController@saveCashRegister');
 });
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
     Route::get('getTypes/{rol}/{branch}', 'IndexController@getTypes');
     Route::get('validateCashRegister', 'IndexController@validateCashRegister');
     Route::post('save', 'BillController@save');
     Route::post('savePaymentMethod', 'BillController@savePaymentMethod');
     Route::get('getCupon/{data}', 'BillController@getCupon');
     Route::post('getDocuments/{id}', 'IndexController@getDocuments');
     Route::get('documentById/{id}', 'BillController@documentById');
     Route::put('update/{id}', 'BillController@update');
     Route::post('getInventory/{branch}', 'BillController@getInventory');
   });
  });
});


Route::namespace('People')->group(function () {
  Route::group(['prefix' => 'people'], function () {
    Route::group(['middleware' => 'auth:api'], function() {
      Route::post('save', 'IndexController@save');
      Route::put('update', 'IndexController@update');
      Route::delete('delete', 'IndexController@delete');
      Route::get('getById/{id}', 'IndexController@getById');
      Route::get('search/{data}/{type}', 'IndexController@search');
    });
  });
});

Route::namespace('Report')->group(function () {
  Route::group(['prefix' => 'report'], function () {
    Route::group(['middleware' => 'auth:api'], function() {
     Route::get('testDetail', 'IndexController@TestDetail');
    });
  });
});

Route::namespace('Product')->group(function () {
  Route::group(['prefix' => 'product'], function () {
    Route::group(['middleware' => 'auth:api'], function() {
      Route::post('getByCode', 'IndexController@getByCode');
    });
  });
});

Route::prefix('setup')->as('setup.')->group(function(){
	Route::get('get', 'SetupController@get');
});

Route::get('algo', 'HomeController@algo');
Route::get('usuario', function(){
 return response()->json(Auth::user()->with('branch')->find(3));
});
