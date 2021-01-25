<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Orders REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Orders;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// CONTROLLERS
use App\Http\Controllers\API\V1\Shippings\C_shippings as C_shippings;
use App\Http\Controllers\API\V1\Invoices\C_invoices as C_invoices;
use App\Http\Controllers\API\V1\Marketings\Discounts\C_discount_redeems as C_discount_redeems;

// MODELS
use App\Http\Models\API\V1\Orders\M_orders as M_default;
use App\Http\Models\API\V1\Orders\M_order_products as M_order_products;
use App\Http\Models\API\V1\Products\M_product_variances as M_product_variances;
use App\Http\Models\API\V1\Products\M_product_categories as M_product_categories;
use App\Http\Models\API\V1\Marketings\Discounts\M_discount_redeems as M_discount_redeems;
use App\Http\Models\API\V1\Downloadables\M_downloadable_matches as M_downloadable_matches;


class C_orders extends Controller{

    private $module = "order";
    private $create_required = [
        'invoice_id',
        'status',
        'created_by'
    ];
    private $order_chains = ['m_orders','m_shipping','m_discount_redeems','m_order_products'];
    private $create_order_chains = ['m_invoices','m_shippings','m_orders','m_order_products'];

/************* CREATE  *************/

    // [POST] api/order <-- Create new row
        function create(Request $reqs){

            $response = array();

            if($this->validation($this->create_order_chains, $reqs)){

                $invoices_inputs = (object) $reqs->input($this->create_order_chains[0]);
                $shippings_inputs = (object) $reqs->input($this->create_order_chains[1]);
                $orders_inputs = (object) $reqs->input($this->create_order_chains[2]);
                $order_products_inputs = (object) $reqs->input($this->create_order_chains[3]);

                // [1] CREATE INVOICE
                    $invoice = new C_invoices;
                    $invoice_response = $invoice->create(new Request, $invoices_inputs);

                    if($invoice_response['status']){
                        $invoice_id = $invoice_response['data']['invoice_id'];

                        // [2] CREATE SHIPPING
                            if(!empty((array)$shippings_inputs)){
                                $shipping = new C_shippings;
                                $shipping_response = $shipping->create(new Request, $shippings_inputs);
                                if($shipping_response['status']){
                                    $shipping_id = $shipping_response['data']['shipping_id'];
                                }
                            }else{
                                $shipping_response['status'] = TRUE;
                                $shipping_id = NULL;
                            }

                            if($shipping_response['status']){
                                $orders_inputs->shipping_id = $shipping_id;

                                // [3] CREATE ORDER
                                    $orders_inputs->invoice_id = $invoice_id;
                                    if($this->validation($this->create_required, $orders_inputs)){

                                        $order = new M_default;
                                        $order->created_at = date("Y-m-d H:i:s");
                                        foreach($orders_inputs as $colname => $value){
                                            $order->{$colname} = $value;
                                        }
                                
                                        if($order->save()){
                                            $order_id = $order['order_id'];

                                            // [4] CREATE ORDER PRODUCTS
                                                $order_product = new C_order_products;
                                                $order_products_inputs->order_id = $order_id;
                                                $order_product_response = $order_product->create(new Request, $order_products_inputs);

                                                if($order_product_response['status']){

                                                    // [5] CREATE DISCOUNT REDEEMS (IF ANY)
                                                    if($reqs->input('m_discount_redeems') != NULL){
                                                        $discount_redeems_inputs = (object) $reqs->input('m_discount_redeems');
                                                        $discount_redeems_inputs->order_id = $order_id;

                                                        $discount_redeems = new C_discount_redeems;
                                                        $discount_redeems->create(new Request, $discount_redeems_inputs);
                                                    }
                                                    // RETURN ORDER CREATE SUCCESS REGARDLESS
                                                    $response = $this->get(new Request, $order['order_id'], true);

                                                }else{
                                                    $response["status"] = False;
                                                    $response["msg"] = $order_product_response['msg'];
                                                    $response["debug"] = $order_product_response['debug'];
                                                }
                                        }else{
                                            $response["status"] = False;
                                            $response["msg"] = "Problem occured. Please try again";
                                            $response["debug"] = "Cannot update order status from database";
                                        }
                
                                    }else{
                                        $response["status"] = False;
                                        $response["msg"] = "Missing parameters";
                                        $response["debug"] = "Missing parameters";
                                    }

                            }else{
                                $response["status"] = False;
                                $response["msg"] = $shipping_response['msg'];
                                $response["debug"] = $shipping_response['debug'];
                            }

                    }else{
                        $response["status"] = False;
                        $response["msg"] = $invoice_response['msg'];
                        $response["debug"] = $invoice_response['debug'];
                    }
                    

            }else{
                    
                if($reqs->input('apicall') != NULL){
                    if($this->validation(array('updated_by'), $reqs)){
                            
                        $cdata = M_default::find($id);

                        foreach($reqs->input() as $colname => $value){
                            if($colname != 'apicall'){
                                $cdata->{$colname} = $value;
                            }
                        }
                        
                        $cdata->updated_at = date("Y-m-d H:i:s");
                        $cdata->save();
                        $response["status"] = True;
                        $response["data"] = $cdata;

                    }else{
                        $response["status"] = False;
                        $response["msg"] = "Missing parameters";
                        $response["debug"] = "Missing updated_by";
                    }
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing parent parameters";
                }
            }

            return response()->json($response);
        
        }

/************* GET/READ  *************/

    // [GET] api/orders <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;;
            $cdata = M_default::where('m_orders.status','<>','0');

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $cdata->where($filters);
            }
            
            $cdata->leftJoin('m_order_statuses','m_orders.status','m_order_statuses.order_status_id')
            
            ->leftJoin('m_order_products','m_orders.order_id','m_order_products.order_id')
            ->leftJoin('m_shippings','m_orders.shipping_id','m_shippings.shipping_id')
            ->leftJoin('m_shipping_methods','m_shippings.shipping_method_id','m_shipping_methods.shipping_method_id')

            ->leftJoin('m_invoices','m_orders.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')
            
            ->leftJoin('m_customers','m_orders.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')
            ->select(

                //Orders
                'm_orders.*',
                'm_order_statuses.status_name',
                'm_order_products.order_product_id',

                //Shippings
                'm_shippings.tracker_id as shipping_tracking_id',
                'm_shippings.address as shipping_address',
                'm_shipping_methods.method_name as shipping_method_name',
                'm_shipping_methods.type as shipping_pricing_type',
                'm_shipping_methods.price as shipping_price',

                //Payment
                'm_invoices.billing_address as billing_address',
                'm_payments.ref_id as payment_ref_id',
                'm_payments.notes as payment_notes',
                'm_payment_methods.method_name as payment_method_name',

                //Customer
                'm_customers.user_id as customer_user_id',
                'm_users.email as customer_email',
                'm_user_details.fullname as customer_fullname',
            );

            if($paginate){
                $cdata = $cdata->paginate(10);
            }else{
                $cdata = $cdata->get();
            }

            if($cdata){
            // INJECT ORDER PRODUCTS DETAILS
                $orders = $cdata->toArray();
                $ordersData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                if(count($ordersData) > 0){
                    foreach($ordersData as $orderKey => $order){
                        $priceTotal = 0;
                        $shippingTotal = ($order['shipping_pricing_type'] == 1) ? $order['shipping_price'] : 0;
                        $weightTotal = 0;

                        $order_id = $order['order_id'];
                        $cdata2 = M_order_products::where([
                            ['m_order_products.order_id','=',$order_id]
                        ])
                        ->leftJoin('m_product_details', 'm_order_products.product_detail_id', 'm_product_details.product_detail_id')
                        ->leftJoin('m_products', 'm_product_details.product_id', 'm_products.product_id')
                        ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
                        ->select(
                            //Products
                            'm_product_details.*',
                            'm_products.name',

                            // Order Products
                            'm_order_products.product_variance_id',
                            'm_order_products.product_variance_id',
                            'm_order_products.price as price_sold',
                            'm_order_products.quantity as product_quantity',

                            //Images
                            'm_images.url as featured_img_url',
                            'm_images.alt as featured_img_alt',
                        )
                        ->get();
                        $order_products = $cdata2->toArray();

                        foreach($order_products as $order_productKey => $order_product){
                            
                            // INJECT DOWNLOADABLE MATCHES
                            $downloadable_file_sets = array();
                                                    
                            if($order_product['downloadable_id'] != NULL){
                                $downloadable_id = $order_product['downloadable_id'];
                                $downlaodables = M_downloadable_matches::where([
                                    ['m_downloadable_matches.downloadable_id','=',$downloadable_id]
                                ])
                                ->leftJoin('m_files','m_downloadable_matches.file_id','=','m_files.file_id')
                                ->select('m_files.*')
                                ->get();

                                // CLEAN DOWNLOADABLE MATCHES IF NULL
                                $files = $downlaodables->toArray();
                                foreach($files as $fileKey => $file){
                                    if($file['file_id'] == NULL){
                                        unset($files[$fileKey]);
                                    }
                                }

                                $order_products[$order_productKey]['downloadable_file_sets'] = $files;
                            }
                            // SET PRODUCT QUANTITY
                            $product_quantity = $order_products[$order_productKey]['product_quantity'];

                            // SET PRODUCT ACTUAL PRICE SOLD
                            if($order_products[$order_productKey]['downsell_price'] != null) {
                                $priceSold = $order_products[$order_productKey]['downsell_price'];
                            }else{
                                $priceSold = ($order_products[$order_productKey]['price_sold'] == NULL) ? $order_products[$order_productKey]['normal_price'] : $order_products[$order_productKey]['price_sold'];
                            }
                            $priceSold = $priceSold * $product_quantity;
                            
                            // IF PRODUCT IS A VARIANCE
                            if($order_product['product_variance_id'] != NULL){

                                // RESET PRICE SOLD
                                $priceSold = $order_products[$order_productKey]['price_sold'];

                                $cdata3 = M_product_variances::where([
                                    ['m_product_variances.product_variance_id','=',$order_product['product_variance_id']]
                                ])
                                ->leftJoin('m_product_details','m_product_variances.product_detail_id','=','m_product_details.product_detail_id')
                                ->leftJoin('m_products', 'm_product_details.product_id', 'm_products.product_id')
                                ->leftJoin('m_images','m_product_variances.featured_img','=','m_images.image_id')
                                ->select(
                                    //Products
                                    'm_product_variances.*',
                                    'm_products.name',
                
                                    //Images
                                    'm_images.url as featured_img_url',
                                    'm_images.alt as featured_img_alt',
                                )
                                ->first();

                                $order_products[$order_productKey] = $cdata3->toArray();

                                // SET PRODUCT ACTUAL PRICE SOLD
                                $priceSold = ($priceSold == NULL) ? $order_products[$order_productKey]['normal_price'] : $priceSold;
                                $priceSold = $priceSold * $product_quantity;

                                // ASSIGN PRODUCT QUANTITY
                                $order_products[$order_productKey]['product_quantity'] = $product_quantity;
                            }

                            // DEFINE PRODUCT PRICE & TOTALS
                            $order_products[$order_productKey]['price_sold'] = $priceSold;

                            // DEFINE PRODUCT PRICE FOR SHIPPING
                            if($order['shipping_pricing_type'] == 2 && $order_product['is_physical'] == 1){ // PRICE PER WEIGHT(kg)
                                $shippingTotal += $order_product['weight'] * $order['shipping_price'] * $product_quantity; 
                            } 
                            $priceTotal += $priceSold;

                            // CUMULATIVE WEIGHT TOTAL
                            $weightTotal += $order_product['weight'] * $product_quantity;
                        }

                    // INJECT DISCOUNTS
                    
                        $discountTotal = 0;
                        if($paginate){
                            $orders['data'][$orderKey]['discount_id'] = NULL;
                        }else{
                            $orders[$orderKey]['discount_id'] = NULL;
                        }
                        $cdata5 = M_discount_redeems::where([
                            ['m_discount_redeems.order_id','=',$order_id]
                        ])
                        ->leftJoin('m_discounts', 'm_discount_redeems.discount_id', 'm_discounts.discount_id')
                        ->select(
                            //Discounts
                            'm_discount_redeems.discount_redeem_id',
                            'm_discounts.discount_id',
                            'm_discounts.name',
                            'm_discounts.type',
                            'm_discounts.value',
                        )
                        ->first();
                        if($cdata5){
                            $discount_redeems = $cdata5->toArray();
                            if($paginate){
                                $orders['data'][$orderKey]['discount_id'] = $discount_redeems['discount_id'];
                            }else{
                                $orders[$orderKey]['discount_id'] = $discount_redeems['discount_id'];
                            }
                            if($discount_redeems['type'] == 2){
                                $discountTotal += $priceTotal * ($discount_redeems['value']/100);
                            }else{
                                $discountTotal += $discount_redeems['value'];
                            }
                        } 

                    // SUMMARY
                        if($paginate){
                            $orders['data'][$orderKey]['total_discount'] = $discountTotal;
                            $orders['data'][$orderKey]['total_weight'] = $weightTotal;
                            $orders['data'][$orderKey]['total_price'] = $priceTotal - $discountTotal;
                            $orders['data'][$orderKey]['total_shipping_price'] = $shippingTotal;
                            $orders['data'][$orderKey]['grand_total_price'] = number_format((float)($priceTotal + $shippingTotal - $discountTotal), 2, '.', '');
                            $orders['data'][$orderKey]['products'] = $order_products;
                        }else{
                            $orders[$orderKey]['total_weight'] = $weightTotal;
                            $orders[$orderKey]['total_price'] = $priceTotal - $discountTotal;
                            $orders[$orderKey]['total_shipping_price'] = $shippingTotal;
                            $orders[$orderKey]['grand_total_price'] = number_format((float)($priceTotal + $shippingTotal - $discountTotal), 2, '.', '');
                            $orders[$orderKey]['products'] = $order_products;
                        }
                    }
                }

            $cdata = $orders;
            }

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }



    // [GET] api/order/{id} <-- Get specific row
        function get(Request $reqs, $id, $direct=false){
            $response = array();

            $cdata = M_default::where($this->module.'_id', '=', $id);

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $withorfilters = ($reqs->input('withorfilters') != NULL) ? json_decode($reqs->input('withorfilters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));

            if($orfilters != NULL){
                $cdata = M_default::where('m_orders.order_id', '=', $id);
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_orders.order_id', '=', $id];
                $cdata = M_default::where($filters);
            }

            $cdata->leftJoin('m_order_statuses','m_orders.status','m_order_statuses.order_status_id')
            
            ->leftJoin('m_order_products','m_orders.order_id','m_order_products.order_id')
            ->leftJoin('m_shippings','m_orders.shipping_id','m_shippings.shipping_id')
            ->leftJoin('m_shipping_methods','m_shippings.shipping_method_id','m_shipping_methods.shipping_method_id')

            ->leftJoin('m_invoices','m_orders.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')
            
            ->leftJoin('m_customers','m_orders.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')
            ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')
            ->select(

                //Orders
                'm_orders.*',
                'm_order_statuses.status_name',
                'm_order_products.order_product_id',

                //Shippings
                'm_shippings.tracker_id as shipping_tracking_id',
                'm_shippings.address as shipping_address',
                'm_shippings.notes as shipping_notes',
                'm_shippings.status as shipping_status',
                'm_shipping_methods.shipping_method_id',
                'm_shipping_methods.method_name as shipping_method_name',
                'm_shipping_methods.type as shipping_pricing_type',
                'm_shipping_methods.price as shipping_price',

                //Payment
                'm_invoices.billing_address as billing_address',
                'm_invoices.status as payment_status',
                'm_invoices.invoice_id as invoice_id',
                'm_invoices.payment_id as invoice_payment_id',
                'm_invoices.email as invoice_email',
                'm_payments.ref_id as payment_ref_id',
                'm_payments.notes as payment_notes',
                'm_payments.total as payment_total',
                'm_payment_methods.payment_method_id as payment_method_id',
                'm_payment_methods.method_name as payment_method_name',

                //Customer
                'm_customers.user_id as customer_user_id',
                'm_users.email as customer_email',
                'm_user_details.fullname as customer_fullname',
                'm_user_details.propic as customer_propic',
                'm_user_details.phone as customer_phone',
                'm_user_details.country_id as customer_country_id',
                'm_user_details.city_id as customer_city_id',
                'm_user_details.state_id as customer_state_id',
                'm_user_details.address1 as customer_address1',
                'm_user_details.address2 as customer_address2',
                'm_user_details.postcode as customer_postcode',
                'm_images.url as customer_propic_url',
                'm_images.alt as customer_propic_alt'

            );

            $cdata = $cdata->first();

            if($cdata){
            // INJECT ORDER PRODUCTS DETAILS
                $order = $cdata->toArray();
                $orderData = $cdata->toArray();

                $priceTotal = 0;
                $shippingTotal = ($order['shipping_pricing_type'] == 1) ? $order['shipping_price'] : 0;
                $weightTotal = 0;

                $order_id = $order['order_id'];
                $cdata2 = M_order_products::where([
                    ['m_order_products.order_id','=',$order_id]
                ])
                ->leftJoin('m_product_details', 'm_order_products.product_detail_id', 'm_product_details.product_detail_id')
                ->leftJoin('m_products', 'm_product_details.product_id', 'm_products.product_id')
                ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
                ->select(
                    //Products
                    'm_product_details.*',
                    'm_products.name',

                    // Order Products
                    'm_order_products.product_variance_id',
                    'm_order_products.product_variance_id',
                    'm_order_products.price as price_sold',
                    'm_order_products.quantity as product_quantity',

                    //Images
                    'm_images.url as featured_img_url',
                    'm_images.alt as featured_img_alt',
                )
                ->get();

                $order_products = $cdata2->toArray();

                foreach($order_products as $order_productKey => $order_product){

                    // INJECT DOWNLOADABLE MATCHES
                        $downloadable_file_sets = array();
                        
                        if($order_product['downloadable_id'] != NULL){
                            $downloadable_id = $order_product['downloadable_id'];
                            $downlaodables = M_downloadable_matches::where([
                                ['m_downloadable_matches.downloadable_id','=',$downloadable_id]
                            ])
                            ->leftJoin('m_files','m_downloadable_matches.file_id','=','m_files.file_id')
                            ->select('m_files.*')
                            ->get();

                            // CLEAN DOWNLOADABLE MATCHES IF NULL
                            $files = $downlaodables->toArray();
                            foreach($files as $fileKey => $file){
                                if($file['file_id'] == NULL){
                                    unset($files[$fileKey]);
                                }
                            }

                            $order_products[$order_productKey]['downloadable_file_sets'] = $files;
                        }
                    
                    // GET FEATURED IMAGE
                        $featured_img_url = $order_products[$order_productKey]['featured_img_url'];
                        $featured_img_alt = $order_products[$order_productKey]['featured_img_alt'];

                    // SET PRODUCT QUANTITY
                        $product_quantity = $order_products[$order_productKey]['product_quantity'];

                    // SET PRODUCT ACTUAL PRICE SOLD
                        if($order_products[$order_productKey]['downsell_price'] != null) {
                            $priceSold = $order_products[$order_productKey]['downsell_price'];
                        }else{
                            $priceSold = ($order_products[$order_productKey]['price_sold'] == NULL) ? $order_products[$order_productKey]['normal_price'] : $order_products[$order_productKey]['price_sold'];
                        }
                        $priceSold = $priceSold * $product_quantity;
                    
                    // IF PRODUCT IS A VARIANCE
                        if($order_product['product_variance_id'] != NULL){

                            // RESET PRICE SOLD
                                $priceSold = $order_products[$order_productKey]['price_sold'];

                                $cdata3 = M_product_variances::where([
                                    ['m_product_variances.product_variance_id','=',$order_product['product_variance_id']]
                                ])
                                ->leftJoin('m_product_details','m_product_variances.product_detail_id','=','m_product_details.product_detail_id')
                                ->leftJoin('m_products', 'm_product_details.product_id', 'm_products.product_id')
                                ->leftJoin('m_images','m_product_variances.featured_img','=','m_images.image_id')
                                ->select(
                                    //Products
                                    'm_product_variances.*',
                                    'm_products.name',
                
                                    //Images
                                    'm_images.url as featured_img_url',
                                    'm_images.alt as featured_img_alt',
                                )
                                ->first();

                                $order_products[$order_productKey] = $cdata3->toArray();

                                // INJECT PRODUCT VARIANCES OPTIONS
                                    $variances = array();
                                    foreach($order_products[$order_productKey] as $var_optionKey => $var_option){
                                        $varopt_array= explode(":",$var_optionKey);
                                        if(isset($varopt_array[0])){
                                            if($varopt_array[0] == "variance" && $var_option != NULL){
                                                $variances[$varopt_array[1]] = $var_option;
                                            }
                                        }

                                        // CHECK IF IMAGE IS NULL
                                            $order_products[$order_productKey]['featured_img_url'] = $featured_img_url;
                                            $order_products[$order_productKey]['featured_img_alt'] = $featured_img_alt;
                                    }

                                $order_products[$order_productKey]['variances'] = $variances;


                            // SET PRODUCT ACTUAL PRICE SOLD
                                $priceSold = ($priceSold == NULL) ? $order_products[$order_productKey]['normal_price'] : $priceSold;
                                $priceSold = $priceSold * $product_quantity;
                                

                            // ASSIGN PRODUCT QUANTITY
                                $order_products[$order_productKey]['product_quantity'] = $product_quantity;
                        }

                    // DEFINE PRODUCT PRICE & TOTALS
                        $order_products[$order_productKey]['price_sold'] = $priceSold;

                    // DEFINE PRODUCT PRICE FOR SHIPPING
                        if($order['shipping_pricing_type'] == 2 && $order_product['is_physical'] == 1){ // PRICE PER WEIGHT(kg)
                            $shippingTotal += $order_product['weight'] * $order['shipping_price'] * $product_quantity; 
                        } 
                        $priceTotal = $priceTotal + $priceSold;
                        
                    // CUMULATIVE WEIGHT TOTAL
                        $weightTotal += $order_product['weight'] * $product_quantity;
                        $weightTotal = number_format((float)$weightTotal, 2, '.', '');


                    // INJECT PRODUCT CATEGORIES
                        $product_id = $order_product['product_id'];
                        $cdata4 = M_product_categories::where([
                            ['m_product_categories.product_id','=',$product_id]
                        ])
                        ->leftJoin('m_categories', 'm_product_categories.category_id', 'm_categories.category_id')
                        ->select(
                            'm_categories.category_id',
                            'm_categories.name',
                            'm_categories.descriptions',
                        )
                        ->get();
                        $product_categories = $cdata4->toArray();
                        $order_products[$order_productKey]['product_categories'] = $product_categories;
                           
                }

                

                $order['products'] = $order_products;

            // INJECT DISCOUNTS
                $discountTotal = 0;
                $order['discount_id'] = NULL;
                $cdata5 = M_discount_redeems::where([
                    ['m_discount_redeems.order_id','=',$id]
                ])
                ->leftJoin('m_discounts', 'm_discount_redeems.discount_id', 'm_discounts.discount_id')
                ->select(
                    //Discounts
                    'm_discount_redeems.discount_redeem_id',
                    'm_discounts.discount_id',
                    'm_discounts.name',
                    'm_discounts.type',
                    'm_discounts.value',
                )
                ->first();
                if($cdata5){
                    $discount_redeems = $cdata5->toArray();
                    $order['discount_id'] = $discount_redeems['discount_id'];
                    $order['discount_details'] = $discount_redeems;  

                    if($discount_redeems['type'] == 2){
                        $discountTotal += $priceTotal * ($discount_redeems['value']/100);
                    }else{
                        $discountTotal += $discount_redeems['value'];
                    }
                } 
            
            

            // SUMMARY
                $order['total_discount'] = $discountTotal;
                $order['total_weight'] = $weightTotal;
                $order['total_price'] = $priceTotal;
                $order['total_shipping_price'] = $shippingTotal;
                $order['grand_total_price'] = $priceTotal + $shippingTotal - $discountTotal;

                $cdata = $order;

                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return ($direct) ? $response : response()->json($response);

        }




/************* UPDATE  *************/

    // [PUT] api/order/{id} <-- Update specific row
        function update(Request $reqs, $id){
            $response = array();

            if($this->validation($this->order_chains, $reqs)){

                $orders_inputs = (object) $reqs->input($this->order_chains[0]);
                $shippings_inputs = (object) $reqs->input($this->order_chains[1]);
                $discount_redeems_inputs = (object) $reqs->input($this->order_chains[2]);
                $order_products_inputs = (object) $reqs->input($this->order_chains[3]);

                if($this->validation(array('updated_by'), $orders_inputs)){

                    // [1] UPDATE BASE ORDER
                        $orders = M_default::find($id);
                        foreach($orders_inputs as $colname => $value){
                            $orders->{$colname} = $value;
                        }
                        $orders->updated_at = date("Y-m-d H:i:s");
                        $orders->save();

                        $ordersData = M_default::where(
                            $this->module.'_id', '=', $id
                        )->first()->toArray();
                        
                        // [2] UPDATE SHIPPING
                            $shippings = new C_shippings;
                            $shippings_response = $shippings->update(new Request, $ordersData['shipping_id'], $shippings_inputs);

                            if($shippings_response['status']){

                            // [3] UPDATE DISCOUNT REDEEMS
                                if(isset($discount_redeems_inputs->discount_id)){
                                    $discount_redeems = new C_discount_redeems;
                                    $discount_redeems_inputs->order_id = $ordersData['order_id'];
                                    $discount_redeems_inputs->customer_id = $ordersData['customer_id'];
                                    $discount_redeems_response = $discount_redeems->update(new Request, $ordersData['order_id'], $discount_redeems_inputs);
                                }else{
                                    $discount_redeems_response = [
                                        "status" => TRUE
                                    ];
                                }
                                if($discount_redeems_response['status']){

                                    // [4] UPDATE ORDER PRODUCTS
                                        $order_products = new C_order_products;
                                        $order_products_inputs->order_id = $ordersData['order_id'];
                                        $order_products_inputs->customer_id = $ordersData['customer_id'];
                                        $order_products_response = $order_products->update(new Request, $ordersData['order_id'], $order_products_inputs);
                                        if($order_products_response['status']){
                                            $response = $this->get(new Request, $ordersData['order_id'], true);
                                        }else{
                                            $response["status"] = False;
                                            $response["msg"] = $order_products_response['msg'];
                                            $response["debug"] = $order_products_response['debug'];
                                        }

                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $discount_redeems_response['msg'];
                                    $response["debug"] = $discount_redeems_response['debug'];
                                }
                            }else{
                                $response["status"] = False;
                                $response["msg"] = $shippings_response['msg'];
                                $response["debug"] = $shippings_response['debug'];
                            }
                            
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing updated_by";
                }

            }else{
                
                if($reqs->input('apicall') != NULL){
                    if($this->validation(array('updated_by'), $reqs)){
                            
                        $cdata = M_default::find($id);

                        foreach($reqs->input() as $colname => $value){
                            if($colname != 'apicall'){
                                $cdata->{$colname} = $value;
                            }
                        }
                        
                        $cdata->updated_at = date("Y-m-d H:i:s");
                        $cdata->save();
                        $response["status"] = True;
                        $response["data"] = $cdata;

                    }else{
                        $response["status"] = False;
                        $response["msg"] = "Missing parameters";
                        $response["debug"] = "Missing updated_by";
                    }
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing parent parameters";
                }
            }

            return response()->json($response);
        }

    // [PUT] api/order/restore/{id} <-- Restore deleted specific row
        function restore($id){
            $response = array();
            $cdata = M_default::withTrashed()->find($id)->restore();
        
            if($cdata){

                $cdata2 = M_default::find($id);
                $cdata2->deleted_by = NULL;
                $cdata2->save();

                $cdata3 = M_default::where(
                    $this->module.'_id', '=', $id
                )->get();

                if($cdata3){
                    $response["status"] = True;
                    $response["data"] = $cdata2;
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Problem occured. Please try again";
                    $response["debug"] = "Cannot find restored item";
                }

            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Fail to restore";
            }

            return response($response);
        }



/************* DELETE  *************/

    // [DELETE] api/order/{id} <-- SoftDelete specific row
    function delete(Request $reqs, $id){
        $response = array();
        
        if($this->validation(array('deleted_by'), $reqs)){
            $cdata = M_default::find($id);
            $cdata->deleted_by = $reqs->input('deleted_by');
            $cdata->save();

            if($cdata->delete()){
                $response['status'] = True;
                $response["msg"] = "Sucessfully Deleted";
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Fail deleting record";
            }
        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing deleted_by";
        }

        return response($response);
    }

    // [DELETE] api/order/delete/{id} <-- Permanent delete specific row
        function harddelete(Request $req,$id){

            $response = array();
            $error = null;
            $apiusername = config('custom.APIHardDelete.APIusername');
            $apipassword = config('custom.APIHardDelete.APIpassword');

            // Validate Username
            if($req->input("username")){
                if($req->input("username") != $apiusername){
                    $error = "Invalid access"; 
                    $debug = "Username not the same";
                }
            }else{
                $error = "Invalid access";
                $debug = "Username empty";

            }
        
            //Validate password
            if($req->input("pass")){
                if($req->input("pass") != $apipassword){
                    $error = "Invalid access";
                    $debug = "Password not the same";
                }
            }else{
                $error = "Invalid access";
                $debug = "Password empty";
            }

            // Validate ID
            if(!isset($id)){
                $error = "Invalid ID"; 
                $debug = "ID empty";
            }

            if(!$error){
                $cdata = new M_default();
                $data = $cdata->harddelete($id);
                if($data["status"]){
                    $response["status"] = TRUE;
                    $response["msg"] = $data["msg"];
                }else{
                    $response["status"] = False;
                    $response["msg"] = $data["msg"];
                    $response["debug"] = $data["debug"];
                }

            }else{
                $response["status"] = False;
                $response["msg"] = $error;
                $response["debug"] = $debug;
            }

            return response($response);


        }



/************* VALIDATION  *************/

    function validation($requirements = NULL, $inputs = NULL){
        $validation = true; 

        if($requirements != NULL){
            foreach($requirements as $required){
                if(!isset($inputs->{$required})){
                    $validation = false;
                }
            }
        }

        return $validation;
    }
}
