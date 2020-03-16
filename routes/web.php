<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
// http://serverfactura.4factura.com/informeInventario/2020-03-16
Route::get('/informe_inventario/{date?}/{hour?}', 'Document\IndexController@informeInventario')->name('informeInventario');
Route::get('/informe_inventario_json/{date?}/{hour?}', 'Document\IndexController@informeInventarioJson')->name('informeInventarioJson');
Route::get('/moreSales/{date_ini}/{date_fin}/{branch_id?}/{category?}/{group}', 'Document\IndexController@moreSales')->name('moreSales');
