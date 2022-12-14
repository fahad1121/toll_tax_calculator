<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\TaxController as TaxController;
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

Route::post('entry_for_vehicle', [TaxController::class, 'saveVehicleInfo']);
Route::post('calculate_toll',[TaxController::class,'calculateTax']);
