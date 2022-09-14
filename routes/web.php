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
// https://server.2factura.com/informe_inventario/2022-08-26
// https://pos.2factura.com/api/store/upfReport/2021-05-12
Route::get('/informe_inventario/{date?}/{hour?}', 'Document\IndexController@informeInventario')->name('informeInventario');
Route::get('/informe_inventario_json/{date?}/{hour?}', 'Document\IndexController@informeInventarioJson')->name('informeInventarioJson');
Route::get('/moreSales/{date_ini}/{date_fin}/{branch_id?}/{category?}/{group}', 'Document\IndexController@moreSales')->name('moreSales');
// referencias negativas
Route::get('/informe_inventario_negativo/{date?}', 'Document\IndexController@informeInventarioNegativo')->name('informeInventarioNegativo');
Route::get('/detalle_negativo/{date?}', 'Document\IndexController@detailedNegativeInventory')->name('detailedNegativeInventory');
// informe de categorias
Route::get('/informe_inventario_categorias', 'Document\IndexController@informeCategorias')->name('informeCategorias');

// For urls reports
Route::get('/warranty', 'Document\IndexController@reportWarranty');
Route::get('/reportCuadre/{date}', 'Document\IndexController@reportCuadre');

// informe registro de productos
Route::get('/informe_registros_productos/{date}', 'Product\IndexController@informeReistroProductos');
