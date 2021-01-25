<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Store_Analytics REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Analytics;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Orders\M_orders as M_orders;
use App\Http\Models\API\V1\Orders\M_order_products as M_order_products;
use App\Http\Models\API\V1\Products\M_products as M_products;
use App\Http\Models\API\V1\Payments\M_payments as M_payments;
use App\Http\Models\API\V1\Customers\M_customers as M_customers;
use App\Http\Models\API\V1\Bookings\M_booking_services as M_booking_services;
use App\Http\Models\API\V1\Services\M_product_categories as M_product_categories;
use App\Http\Models\API\V1\Categories\M_categories as M_categories;
use App\Http\Models\API\V1\Marketings\Discounts\M_discount_redeems as M_discount_redeems;
use App\Http\Models\API\V1\Marketings\Discounts\M_discounts as M_discounts;


class C_store_analytics extends Controller{


/************* GET/READ  *************/

    // [GET] api/shop/analytics <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'enable') ? true : false;            
            $limit = ($reqs->input('limit') != NULL) ? $reqs->input('limit') : false;

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $withorfilters = ($reqs->input('withorfilters') != NULL) ? json_decode($reqs->input('withorfilters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata = M_orders::where('m_orders.status','<>','0');
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_orders.status','<>','0'];
                $cdata = M_orders::where($filters);            
            }
            
            $cdata->leftJoin('m_order_statuses','m_orders.status','m_order_statuses.order_status_id')

            ->leftJoin('m_invoices','m_orders.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_order_products','m_orders.order_id','m_order_products.order_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')
            
            ->leftJoin('m_customers','m_orders.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')
            ->select(
                \DB::raw('sum(m_payments.total) as total_sale'),

                \DB::raw('count(m_orders.order_id) as total_orders'),
                \DB::raw('sum(m_payments.total * 0.1) as total_profit'),

            );

            if($reqs->input('orderby') != NULL){
                $orderby = json_decode($reqs->input('orderby'));
                if(count($orderby) > 1){
                    if($orderby[1] == 'ASC' || $orderby[1] == 'DESC'){
                        $cdata->orderBy($orderby[0],$orderby[1]);
                    }
                }
            }

            if($paginate){
                $cdata = $cdata->paginate(10);
            }else{
                if($limit){
                    $cdata = $cdata->skip(0)->take($limit)->first();
                }else{
                    $cdata = $cdata->first();
                }
            }

            
            if($cdata){
                $analytics = $cdata->toArray();

                //TOTAL SALES GRAPH
                    //INJECT DAILY TOTAL SALES  GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata5 = M_payments::where([
                                ['m_payments.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('DATE(m_payments.created_at) as date'),
                                \DB::raw('sum(m_payments.total) as daily_total_sales')

                            )
                            ->groupBy('date')
                            ->orderBy('date', 'ASC')
                            ->get();

                            $daily_total_sales = $cdata5->toArray();
                            if($paginate){
                                $analytics['data']['total_sales']['daily_total_sales'] = $daily_total_sales;
                            }else{
                                $analytics['total_sales']['daily_total_sales'] = $daily_total_sales;
                            }
                        }

                    //INJECT WEEKLY TOTAL SALES  GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata6 = M_payments::where([
                                ['m_payments.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('WEEKOFYEAR(m_payments.created_at) as week'),                          
                                \DB::raw('sum(m_payments.total) as weekly_total_sales')

                            )
                            ->groupBy('week')
                            ->orderBy('m_payments.created_at', 'ASC')
                            ->get();

                            $weekly_total_sales = $cdata6->toArray();
                            if($paginate){
                                $analytics['data']['total_sales']['weekly_total_sales'] = $weekly_total_sales;
                            }else{
                                $analytics['total_sales']['weekly_total_sales'] = $weekly_total_sales;
                            }
                        }

                    //INJECT MONTHLY TOTAL SALES  GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata7 = M_payments::where([
                                ['m_payments.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('MONTHNAME(m_payments.created_at) as month'),                          
                                \DB::raw('sum(m_payments.total) as monthly_total_sales')

                            )
                            ->groupBy('month')
                            ->orderBy('m_payments.created_at', 'ASC')
                            ->get();

                            $monthly_total_sales = $cdata7->toArray();
                            if($paginate){
                                $analytics['data']['total_sales']['monthly_total_sales'] = $monthly_total_sales;
                            }else{
                                $analytics['total_sales']['monthly_total_sales'] = $monthly_total_sales;
                            }
                        }

                //TOTAL CUSTOMER REGISTERED GRAPH
                    //INJECT DAILY CUSTOMER REGISTERED  GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata8 = M_customers::where([
                                ['m_customers.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('DATE(m_customers.created_at) as date'),
                                \DB::raw('count(m_customers.customer_id) as daily_total_customer_registered')

                            )
                            ->groupBy('date')
                            ->orderBy('date', 'ASC')
                            ->take(7)
                            ->get();

                            $daily_total_customer_registered = $cdata8->toArray();
                            if($paginate){
                                $analytics['data']['total_customer_registered']['daily_total_customer_registered'] = $daily_total_customer_registered;
                            }else{
                                $analytics['total_customer_registered']['daily_total_customer_registered'] = $daily_total_customer_registered;
                            }
                        }
                    //INJECT WEEKLY TOTAL SALES  GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata9 = M_customers::where([
                                ['m_customers.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('WEEKOFYEAR(m_customers.created_at) as week'),                          
                                \DB::raw('count(m_customers.customer_id) as weekly_total_customer_registered')

                            )
                            ->groupBy('week')
                            ->orderBy('m_customers.created_at', 'ASC')
                            ->get();

                            $weekly_total_customer_registered = $cdata9->toArray();
                            if($paginate){
                                $analytics['data']['total_customer_registered']['weekly_total_customer_registered'] = $weekly_total_customer_registered;
                            }else{
                                $analytics['total_customer_registered']['weekly_total_customer_registered'] = $weekly_total_customer_registered;
                            }
                        }

                    //INJECT MONTHLY TOTAL SALES  GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata10 = M_customers::where([
                                ['m_customers.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('MONTHNAME(m_customers.created_at) as month'),                          
                                \DB::raw('count(m_customers.customer_id) as monthly_total_customer_registered')

                            )
                            ->groupBy('month')
                            ->orderBy('m_customers.created_at', 'ASC')
                            ->get();

                            $monthly_total_customer_registered = $cdata10->toArray();
                            if($paginate){
                                $analytics['data']['total_customer_registered']['monthly_total_customer_registered'] = $monthly_total_customer_registered;
                            }else{
                                $analytics['total_customer_registered']['monthly_total_customer_registered'] = $monthly_total_customer_registered;
                            }
                        }

                // INJECT CUSTOMER ANALYTICS
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata1 = M_customers::where([
                            ['m_customers.status','<>','0']
                        ])
                        ->select(
                            \DB::raw('count(m_customers.customer_id) as total_customers'),
                        )
                        ->first();

                        $customer = $cdata1->toArray();
                        if($paginate){
                            $analytics['data']['customer'] = $customer;
                        }else{
                            $analytics['customer'] = $customer;
                        }
                    }

                // INJECT TOTAL SERVICES ANALYTICS
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata11 = M_products::where([
                            ['m_products.status','<>','0']
                        ])
                        ->select(
                            \DB::raw('count(m_products.product_id) - count(m_products.deleted_by) as total_products'),
                        )
                        ->first();

                        $total_products = $cdata11->toArray();
                        if($paginate){
                            $analytics['data']['total_products'] = $total_products;
                        }else{
                            $analytics['total_products'] = $total_products;
                        }
                    }

                // INJECT BEST SELLING SERVICE ANALYTICS
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata4 = M_order_products::where([
                            ['m_order_products.status','<>','0']
                        ])
                        ->leftJoin('m_product_details','m_order_products.product_detail_id','m_product_details.product_detail_id')                        
                        ->leftJoin('m_products','m_product_details.product_id','m_products.product_id')                        
                        ->select(
                            'm_products.product_id as product_id',
                            'm_products.name as product_name',
                            \DB::raw('count(m_products.product_id) as total_order_products'),
                        )
                        ->groupBy('m_products.product_id')
                        ->orderByRaw('count(m_products.product_id) DESC')
                        ->take(5)
                        ->get();

                        $best_selling_product = $cdata4->toArray();
                        if($paginate){
                            $analytics['data']['best_selling_product'] = $best_selling_product;
                        }else{
                            $analytics['best_selling_product'] = $best_selling_product;
                        }
                    }

                // INJECT DISCOUNT ANALYTICS
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata2 = M_discounts::where([
                            ['m_discounts.status','<>','0']
                        ])
                        ->leftJoin('m_discount_redeems','m_discounts.discount_id','m_discount_redeems.discount_id')
                        ->selectRaw('
                            m_discounts.*,
                            count(m_discount_redeems.discount_id) as redeem_totals

                        ')
                        ->groupBy('m_discounts.discount_id')
                        ->orderByRaw('count(m_discount_redeems.discount_id) DESC')
                        ->take(5)
                        ->get();

                        $discounts = $cdata2->toArray();
                        if($paginate){
                            $analytics['data']['discounts'] = $discounts;
                        }else{
                            $analytics['discounts'] = $discounts;
                        }
                    }

                    $cdata = $analytics;

                }
                
            

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }

/************* DELETE  *************/

    // [DELETE] api/booking/{id} <-- SoftDelete specific row
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

    // [DELETE] api/booking/delete/{id} <-- Permanent delete specific row
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
