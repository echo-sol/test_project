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


$API_VERSION = "API\V1\\";


/******************** CARTS *******************/

    // [CARTS]

        $cont = "cart";
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

    // [CARTHISTORY]

        $cont = "cart";
        $contRoute = "carthistory";
        $contRouteGet = "carthistories";
        $contClass = "cart_histories";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass;

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRouteGet, $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

/*************************************************/

/******************** CATEGORIES *******************/

    // [CATEGORIES]

        $cont = "categories";
        $contRoute = "category";
        $contRouteGet = "categories";
        $contClass = "categories";
        $path = $API_VERSION.ucfirst($cont)."\C_".$contClass;

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRouteGet, $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');


/*************************************************/

/******************** TAGS *******************/

    // [TAGS]

    $cont = "tag";
    $path = $API_VERSION.ucfirst($cont)."s\C_".$cont.'s';

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


/*************************************************/

/******************** CUSTOMERS *******************/

    // [CUSTOMERS]

        $cont = "customer";
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



/*************************************************/

/******************** RESELLERS  *******************/

    // [RESELLERS]

    $cont = "reseller";
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



/*************************************************/

/******************** REVIEWERS  *******************/

    // [REVIEWERS]

    $cont = "reviewer";
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



/*************************************************/

/******************** INVOICES *******************/

    // [INVOICES]

        $cont = "invoice";
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

/*************************************************/

/******************** ORDERS *******************/

    // [PRODUCT ORDERS]

        $cont = "order/product";
        $path = $API_VERSION."Orders\C_order_products";

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


    // [ORDERS]

        $cont = "order";
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

    // [ORDER STATUSES]

        $cont = "order";
        $contRoute = "orderstatus";
        $contRouteGet = "orderstatuses";
        $contClass = "order_statuses";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass;

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRouteGet, $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

/*************************************************/

/******************** BOOKINGS *******************/

    // [SERVICE BOOKING]

        $cont = "booking/service";
        $path = $API_VERSION."Bookings\C_booking_services";

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


    // [BOOKING]

        $cont = "booking";
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

    // [BOOKING STATUSES]

        $cont = "booking";
        $contRoute = "bookingstatus";
        $contRouteGet = "bookingstatuses";
        $contClass = "booking_statuses";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass;

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRouteGet, $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

/*************************************************/
/******************** ANALYTICS *******************/

    // [RESELLER_ANALYTICS]

    $cont = "resellers/analytic";
    $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."s";
    $path = $API_VERSION."Analytics\C_reseller_analytics";

    // READ
    Route::get($cont.'s', $path.'@list')->middleware('client');

    // DELETE
    Route::delete($cont.'/{id}', $path.'@delete')->middleware('client');
    Route::delete($cont.'/delete/{id}', $path.'@harddelete')->middleware('client');

    // [SHOP_ANALYTICS]

    $cont = "store/analytic";
    $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."s";
    $path = $API_VERSION."Analytics\C_store_analytics";

    // READ
    Route::get($cont.'s', $path.'@list')->middleware('client');

    // DELETE
    Route::delete($cont.'/{id}', $path.'@delete')->middleware('client');
    Route::delete($cont.'/delete/{id}', $path.'@harddelete')->middleware('client');

/*************************************************/

/******************** PAYMENTS *******************/


    // [PAYMENT METHODS]

        $cont = "payment";
        $contClass = "payment_method";
        $contRoute = "payment/method";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass."s";

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRoute.'s', $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

    // [PAYMENTS]

        $cont = "payment";
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

/*************************************************/

/******************** PRODUCTS *******************/

    // [PRODUCT CATEGORIES]

        $path = $API_VERSION."Products\C_product_categories";

        // CREATE
        Route::post('product/category', $path.'@create')->middleware('client');

        // READ
        Route::get('product/categories', $path.'@list')->middleware('client');
        Route::get('product/category/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put('product/category/{id}', $path.'@update')->middleware('client');
        Route::put('product/category/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete('product/category/{id}', $path.'@delete')->middleware('client');
        Route::delete('product/category/delete/{id}', $path.'@harddelete')->middleware('client');


    // [RELATED PRODUCTS]
        $cont = "product";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."s";

        // CREATE
        Route::post($cont, $path.'@create')->middleware('client');

    // [PRODUCTS]

        $cont = "product";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."s";

        // CREATE
        Route::post($cont, $path.'@create')->middleware('client');

        // READ
            // BRANDS
                Route::get($cont.'/brands', $path.'@getBrands')->middleware('client');

            Route::get($cont.'s', $path.'@list')->middleware('client');
            Route::get($cont.'/{id}', $path.'@get')->middleware('client');

            // AJAX
                Route::get('ajax/'.$cont.'s', $path.'@ajax_list')->middleware('client');

        // UPDATE
        Route::put($cont.'/{id}', $path.'@update')->middleware('client');
        Route::put($cont.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($cont.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($cont.'/delete/{id}', $path.'@harddelete')->middleware('client');

    // [PRODUCT DETAIL]

        /* $cont = "product/{id}/detail";
        $path = $API_VERSION."Products\C_product_details";

        // CREATE
        Route::post('product/detail', $path.'@create')->middleware('client');

        // READ
            Route::get($cont.'s', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($cont, $path.'@update')->middleware('client');
        Route::put($cont.'/restore', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($cont, $path.'@delete')->middleware('client');
        Route::delete($cont.'/delete', $path.'@harddelete')->middleware('client'); */

    /* // [PRODUCT DETAIL]

        $cont = "product";
        $contClass = "product_detail";
        $contRoute = "productdetail";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass."s";

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRoute.'s', $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

    // [PRODUCT VARIANCE]

        $cont = "product";
        $contClass = "product_variance";
        $contRoute = "productvariance";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass."s";

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRoute.'s', $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

    // [PRODUCT ATTRIBUTES]

        $cont = "product";
        $contClass = "product_attribute";
        $contRoute = "productattribute";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass."s";

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRoute.'s', $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client'); */

/*************************************************/

/******************** SERVICES *******************/

    // [SERVICE CATEGORIES]

    $path = $API_VERSION."Services\C_service_categories";

    // CREATE
    Route::post('service/category', $path.'@create')->middleware('client');

    // READ
    Route::get('service/categories', $path.'@list')->middleware('client');
    Route::get('service/category/{id}', $path.'@get')->middleware('client');

    // UPDATE
    Route::put('service/category/{id}', $path.'@update')->middleware('client');
    Route::put('service/category/restore/{id}', $path.'@restore')->middleware('client');

    // DELETE
    Route::delete('service/category/{id}', $path.'@delete')->middleware('client');
    Route::delete('service/category/delete/{id}', $path.'@harddelete')->middleware('client');


// [SERVICES]

    $cont = "service";
    $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."s";

    // CREATE
    Route::post($cont, $path.'@create')->middleware('client');

    // READ
        Route::get($cont.'s', $path.'@list')->middleware('client');
        Route::get($cont.'/{id}', $path.'@get')->middleware('client');

        // AJAX
            Route::get('ajax/'.$cont.'s', $path.'@ajax_list')->middleware('client');

    // UPDATE
    Route::put($cont.'/{id}', $path.'@update')->middleware('client');
    Route::put($cont.'/restore/{id}', $path.'@restore')->middleware('client');

    // DELETE
    Route::delete($cont.'/{id}', $path.'@delete')->middleware('client');
    Route::delete($cont.'/delete/{id}', $path.'@harddelete')->middleware('client');


/*************************************************/

/******************** REVIEWS *******************/

    // [PRODUCT REVIEWS]

        $cont = "review/product";
        $path = $API_VERSION."Reviews\C_product_reviews";

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

    // [SERVICE REVIEWS]

        $cont = "review/service";
        $path = $API_VERSION."Reviews\C_service_reviews";

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

    // [REVIEWS]

        $cont = "review";
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

/*************************************************/

/******************** RECEIPTS *******************/

    // [RECEIPTS]

        $cont = "receipt";
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

/*************************************************/

/******************** SHIPPINGS *******************/

    // [SHIPPING METHOD COVERAGES]

        $cont = "shipping";
        $contClass = "shipping_method_coverage";
        $contRoute = "shipping/method/coverage";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass."s";

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRoute.'s', $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

    // [SHIPPING METHODS]

        $cont = "shipping";
        $contClass = "shipping_method";
        $contRoute = "shipping/method";
        $path = $API_VERSION.ucfirst($cont)."s\C_".$contClass."s";

        // CREATE
        Route::post($contRoute, $path.'@create')->middleware('client');

        // READ
        Route::get($contRoute.'s', $path.'@list')->middleware('client');
        Route::get($contRoute.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($contRoute.'/{id}', $path.'@update')->middleware('client');
        Route::put($contRoute.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($contRoute.'/{id}', $path.'@delete')->middleware('client');
        Route::delete($contRoute.'/delete/{id}', $path.'@harddelete')->middleware('client');

    // [SHIPPINGS]

        $cont = "shipping";
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

/*************************************************/

/******************** TAXATIONS *******************/

    // [TAXATIONS]

        $cont = "taxation";
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

/*************************************************/

/******************** USERS *******************/
        
    $cont = "user";

    // [USER ROLE ACCESS]

        $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."_role_access";

        // CREATE
        Route::post($cont."/role/access", $path.'@create')->middleware('client');

        // READ
        Route::get($cont.'/role/accesses', $path.'@list')->middleware('client');
        Route::get($cont.'/role/access/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($cont.'/role/access/{id}', $path.'@update')->middleware('client');
        Route::put($cont.'/role/access/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($cont.'/role/access/{id}', $path.'@delete')->middleware('client');
        Route::delete($cont.'/role/access/delete/{id}', $path.'@harddelete')->middleware('client');

    // [USER ROLES]

        $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."_roles";

        // CREATE
        Route::post($cont."/role", $path.'@create')->middleware('client');

        // READ
        Route::get($cont.'/roles', $path.'@list')->middleware('client');
        Route::get($cont.'/role/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($cont.'/role/{id}', $path.'@update')->middleware('client');
        Route::put($cont.'/role/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($cont.'/role/{id}', $path.'@delete')->middleware('client');
        Route::delete($cont.'/role/delete/{id}', $path.'@harddelete')->middleware('client');
    
    // [USER DETAILS]

        $path = $API_VERSION.ucfirst($cont)."s\C_".$cont."_details";

        // CREATE
        Route::post($cont."/detail", $path.'@create')->middleware('client');

        // READ
        Route::get($cont.'/details', $path.'@list')->middleware('client');
        Route::get($cont.'/detail/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($cont.'/detail/{id}', $path.'@update')->middleware('client');
        Route::put($cont.'/detail/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($cont.'/detail/{id}', $path.'@delete')->middleware('client');
        Route::delete($cont.'/detail/delete/{id}', $path.'@harddelete')->middleware('client');

    // [USERS]

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
        
    // [LOGIN]

        $cont = "login";
        $path = $API_VERSION."Access\C_".$cont;

        // LOGIN
        Route::post($cont, $path.'@attempt')->middleware('client');

/*************************************************/

/******************** WISHLISTS *******************/

    // [WISHLISTS]

    $cont = "wishlist";
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

/*************************************************/

/******************** IMAGES *******************/

    // [IMAGES]

    $cont = "image";
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

/*************************************************/

/******************** FILES *******************/

    // [FILES]

    $cont = "file";
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

/*************************************************/

/******************** DOWNLOADABLES *******************/

    // [DOWNLOADABLES]

    $cont = "downloadable";
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

/*************************************************/

/******************** GALLERIES *******************/

    // [GALLERY MATCHES]

        /* $cont = "gallery/match";
        $path = $API_VERSION."Galleries\C_gallery_matches";

        // CREATE
            Route::post($cont, $path.'@create')->middleware('client');

        // READ
            Route::get($cont.'es', $path.'@list')->middleware('client');
            Route::get($cont.'/{id}', $path.'@get')->middleware('client');

        // UPDATE
            Route::put($cont.'/{id}', $path.'@update')->middleware('client');
            Route::put($cont.'/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
            Route::delete($cont.'/{id}', $path.'@delete')->middleware('client');
            Route::delete($cont.'/delete/{id}', $path.'@harddelete')->middleware('client'); */

    // [GALLERIES]

        $cont = "galler";
        $path = $API_VERSION.ucfirst($cont)."ies\C_".$cont."ies";

        // CREATE
            Route::post($cont.'y', $path.'@create')->middleware('client');

        // READ
            Route::get($cont.'ies', $path.'@list')->middleware('client');
            Route::get($cont.'y/{id}', $path.'@get')->middleware('client');

        // UPDATE
            Route::put($cont.'y/{id}', $path.'@update')->middleware('client');
            Route::put($cont.'y/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
            Route::delete($cont.'y/{id}', $path.'@delete')->middleware('client');
            Route::delete($cont.'y/delete/{id}', $path.'@harddelete')->middleware('client');

/*************************************************/

/******************** SOCIALS *******************/

    // [SOCIALS]

    $cont = "social";
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

/*************************************************/

/******************** FAQS *******************/

    // [FAQS]

    $cont = "faq";
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

/*************************************************/

/******************** CONVERSATIONS **********************/

    // [CONVERSATIONS]

    $cont = "conversation";
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

/*************************************************/

/******************** MESSAGES **********************/

    // [MESSAGES]

    $cont = "message";
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

/*************************************************/

/******************** PRIVACY POLICIES ***********/

    // [PRIVACY POLICIES]

    $cont = "privacy_polic";
    $path = $API_VERSION.ucfirst($cont)."ies\C_".$cont."ies";

    // CREATE
    Route::post($cont.'y', $path.'@create')->middleware('client');

    // READ
    Route::get($cont.'ies', $path.'@list')->middleware('client');
    Route::get($cont.'y/{id}', $path.'@get')->middleware('client');

    // UPDATE
    Route::put($cont.'y/{id}', $path.'@update')->middleware('client');
    Route::put($cont.'y/restore/{id}', $path.'@restore')->middleware('client');

    // DELETE
    Route::delete($cont.'y/{id}', $path.'@delete')->middleware('client');
    Route::delete($cont.'y/delete/{id}', $path.'@harddelete')->middleware('client');

/*************************************************/

/******************** BLOGS **********************/

    // [BLOGS]

    $cont = "blog";
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

/*************************************************/
/******************** MARKETINGS *******************/

    // [DISCOUNTS]

        $cont = "discount";
        $path = $API_VERSION."Marketings\\".ucfirst($cont)."s\C_".$cont."s";

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

    // [REFERRAL]

        $cont = "referral";
        $path = $API_VERSION."Marketings\\".ucfirst($cont)."s\C_".$cont."s";

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

/*************************************************/

/******************** HIGHSCORES *******************/

    // [HIGHSCORES]

    $cont = "highscore";
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

/*************************************************/

/******************** WORLD *******************/

    // [CONTINENTS]

        $cont = "continent";
        $path = $API_VERSION."Worlds\C_".$cont."s";

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

    
    // [COUNTRIES]

        $cont = "countr";
        $path = $API_VERSION."Worlds\C_".$cont."ies";
    
        // CREATE
        Route::post($cont.'y', $path.'@create')->middleware('client');
    
        // READ
        Route::get($cont.'ies', $path.'@list')->middleware('client');
        Route::get($cont.'y/{id}', $path.'@get')->middleware('client');
    
        // UPDATE
        Route::put($cont.'y/{id}', $path.'@update')->middleware('client');
        Route::put($cont.'y/restore/{id}', $path.'@restore')->middleware('client');
    
        // DELETE
        Route::delete($cont.'y/{id}', $path.'@delete')->middleware('client');
        Route::delete($cont.'y/delete/{id}', $path.'@harddelete')->middleware('client');


    // [STATES]

        $cont = "state";
        $path = $API_VERSION."Worlds\C_".$cont."s";

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


    // [CITIES]

        $cont = "cit";
        $path = $API_VERSION."Worlds\C_".$cont."ies";

        // CREATE
        Route::post($cont.'y', $path.'@create')->middleware('client');

        // READ
        Route::get($cont.'ies', $path.'@list')->middleware('client');
        Route::get($cont.'y/{id}', $path.'@get')->middleware('client');

        // UPDATE
        Route::put($cont.'y/{id}', $path.'@update')->middleware('client');
        Route::put($cont.'y/restore/{id}', $path.'@restore')->middleware('client');

        // DELETE
        Route::delete($cont.'y/{id}', $path.'@delete')->middleware('client');
        Route::delete($cont.'y/delete/{id}', $path.'@harddelete')->middleware('client');
/*************************************************/
