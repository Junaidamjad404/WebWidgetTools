<?php

use App\Http\Controllers\widgetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('get_widget',[widgetController::class, 'getWidget'])->name('getWidget');
Route::post('set_metafield',[widgetController::class,'setMetafield'])->name('setMetafield');
Route::post('delete_metafield', [widgetController::class, 'deleteMetafield'])->name('deleteMetafield');
Route::post('custom_setting', [widgetController::class, 'customSetting'])->name('customSetting');
Route::post('update_module_status', [widgetController::class, 'updateModuleStatus'])->name('updateModuleStatus');

