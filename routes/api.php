<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Model Product_Details REST-API (CRUD)
**/

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

/* Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
}); */


$API_VERSION = "API\\";


/******************** USERS *******************/

    // [USERS]
    
    $cont = 'user';

        $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."s";

        // CREATE
        Route::post($cont, $path.'@create')->middleware('client');

        // READ
        Route::get($cont.'s', $path.'@list')->middleware('client');
        Route::get($cont.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($cont.'/{id}', $path.'@update')->middleware('client');
        Route::put($cont.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($cont.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($cont.'/delete/{id}', $path.'@harddelete')->middleware('client');
        
    /******************** sandbox *******************/

    // CREATE
    Route::get('sandbox',$path.'@reply')->middleware('client');