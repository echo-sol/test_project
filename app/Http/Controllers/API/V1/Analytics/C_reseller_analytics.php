<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Reseller_Analytics REST-API (CRUD)
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
use App\Http\Models\API\V1\Resellers\M_resellers as M_resellers;
use App\Http\Models\API\V1\Bookings\M_booking_services as M_booking_services;
use App\Http\Models\API\V1\Services\M_product_categories as M_product_categories;
use App\Http\Models\API\V1\Categories\M_categories as M_categories;
use App\Http\Models\API\V1\Marketings\Discounts\M_discount_redeems as M_discount_redeems;
use App\Http\Models\API\V1\Marketings\Discounts\M_discounts as M_discounts;

class C_reseller_analytics extends Controller{

    private $module = "reseller_analytics";

/************* GET/READ  *************/

    // [GET] api/reseller/analytics <-- Get all lists
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
            ->leftJoin('m_product_details','m_order_products.product_detail_id','m_product_details.product_detail_id')
            ->select(
                \DB::raw('sum(m_payments.total) as total_sales'),
                \DB::raw('sum(m_payments.total * 0.9) as total_commission'),

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
                // INJECT TOTAL RESELLER ANALYTICS
                    $analytics = $cdata->toArray();
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata1 = M_resellers::where([
                            ['m_resellers.status','<>','0']
                        ])
                        ->select(
                            // CUSTOMER
                            \DB::raw('count(m_resellers.reseller_id) as total_resellers'),
                        )
                        ->first();

                        $resellers = $cdata1->toArray();
                        if($paginate){
                            $analytics['data']['resellers'] = $resellers;
                        }else{
                            $analytics['resellers'] = $resellers;
                        }
                    }

                // INJECT RESELLER SALES ANALYTICS
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata2 = M_resellers::where([
                            ['m_resellers.status','<>','0']
                        ])
                        ->leftJoin('m_users','m_resellers.user_id','m_users.user_id')
                        ->leftJoin('m_user_details','m_users.user_id','m_user_details.user_id')
                        ->leftJoin('m_products','m_resellers.user_id','m_products.created_by')
                        ->leftJoin('m_product_details','m_products.product_id','m_product_details.product_id')
                        ->leftJoin('m_order_products','m_product_details.product_detail_id','m_order_products.product_detail_id')
                        ->leftJoin('m_orders','m_order_products.order_id','m_orders.order_id')
                        ->leftJoin('m_invoices','m_orders.invoice_id','m_invoices.invoice_id')
                        ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
                        ->select(
                            'm_resellers.reseller_id as reseller_id',
                            'm_user_details.fullname as seller_name',
                            \DB::raw('sum(m_payments.total) as total_sale'),

                        )
                        ->groupBy('m_resellers.reseller_id')
                        ->orderBy('m_payments.total', 'DESC')
                        ->take(5)
                        ->get();

                        $reseller_sale = $cdata2->toArray();
                        if($paginate){
                            $analytics['data']['reseller_sale'] = $reseller_sale;
                        }else{
                            $analytics['reseller_sale'] = $reseller_sale;
                        }
                    }
                //RESELLER ONBOARDING GRAPH
                    //INJECT RESELLER DAILY ONBOARDING GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata5 = M_resellers::where([
                                ['m_resellers.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('DATE(m_resellers.created_at) as date'),
                                \DB::raw('count(m_resellers.reseller_id) as daily_reseller_onboarding')

                            )
                            ->groupBy('date')
                            ->orderBy('date', 'ASC')
                            ->get();

                            $daily_reseller_onboarding = $cdata5->toArray();
                            if($paginate){
                                $analytics['data']['reseller_onboarding']['daily_reseller_onboarding'] = $daily_reseller_onboarding;
                            }else{
                                $analytics['reseller_onboarding']['daily_reseller_onboarding'] = $daily_reseller_onboarding;
                            }
                        }

                    //INJECT RESELLER WEEKLY ONBOARDING GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata6 = M_resellers::where([
                                ['m_resellers.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('WEEKOFYEAR(m_resellers.created_at) as week'),                          
                                \DB::raw('count(m_resellers.reseller_id) as weekly_reseller_onboarding')

                            )
                            ->groupBy('week')
                            ->orderBy('m_resellers.created_at', 'ASC')
                            ->get();

                            $weekly_reseller_onboarding = $cdata6->toArray();
                            if($paginate){
                                $analytics['data']['reseller_onboarding']['weekly_reseller_onboarding'] = $weekly_reseller_onboarding;
                            }else{
                                $analytics['reseller_onboarding']['weekly_reseller_onboarding'] = $weekly_reseller_onboarding;
                            }
                        }

                    //INJECT RESELLER MONTHLY ONBOARDING GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata6 = M_resellers::where([
                                ['m_resellers.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('MONTHNAME(m_resellers.created_at) as month'),                          
                                \DB::raw('count(m_resellers.reseller_id) as monthly_reseller_onboarding')

                            )
                            ->groupBy('month')
                            ->orderBy('m_resellers.created_at', 'ASC')
                            ->get();

                            $monthly_reseller_onboarding = $cdata6->toArray();
                            if($paginate){
                                $analytics['data']['reseller_onboarding']['monthly_reseller_onboarding'] = $monthly_reseller_onboarding;
                            }else{
                                $analytics['reseller_onboarding']['monthly_reseller_onboarding'] = $monthly_reseller_onboarding;
                            }
                        }

                    //INJECT RESELLER YEARLY ONBOARDING GRAPH
                        $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                        if(count($analyticsData) > 0){
                            $cdata6 = M_resellers::where([
                                ['m_resellers.status','<>','0']
                            ])
                            ->select(

                                \DB::raw('YEAR(m_resellers.created_at) as year'),                          
                                \DB::raw('count(m_resellers.reseller_id) as yearly_reseller_onboarding')

                            )
                            ->groupBy('year')
                            ->orderBy('m_resellers.created_at', 'ASC')
                            ->get();

                            $yearly_reseller_onboarding = $cdata6->toArray();
                            if($paginate){
                                $analytics['data']['reseller_onboarding']['yearly_reseller_onboarding'] = $yearly_reseller_onboarding;
                            }else{
                                $analytics['reseller_onboarding']['yearly_reseller_onboarding'] = $yearly_reseller_onboarding;
                            }
                        }

                // INJECT RESELLER COMMISSION ANALYTICS
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata3 = M_resellers::where([
                            ['m_resellers.status','<>','0']
                        ])
                        ->leftJoin('m_users','m_resellers.user_id','m_users.user_id')
                        ->leftJoin('m_user_details','m_users.user_id','m_user_details.user_id')
                        ->leftJoin('m_products','m_resellers.user_id','m_products.created_by')
                        ->leftJoin('m_product_details','m_products.product_id','m_product_details.product_id')
                        ->leftJoin('m_order_products','m_product_details.product_detail_id','m_order_products.product_detail_id')
                        ->leftJoin('m_orders','m_order_products.order_id','m_orders.order_id')
                        ->leftJoin('m_invoices','m_orders.invoice_id','m_invoices.invoice_id')
                        ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
                        ->select(

                            'm_resellers.reseller_id as reseller_id',
                            'm_user_details.fullname as seller_name',
                            \DB::raw('sum(m_payments.total * 0.9) as total_commission'),

                        )
                        ->groupBy('m_resellers.reseller_id')
                        ->orderByRaw('sum(m_payments.total * 0.9) DESC')
                        ->take(5)
                        ->get();

                        $reseller_commission = $cdata3->toArray();
                        if($paginate){
                            $analytics['data']['reseller_commission'] = $reseller_commission;
                        }else{
                            $analytics['reseller_commission'] = $reseller_commission;
                        }
                    }

                // INJECT RESELLER PRODUCTS ANALYTICS
                    $analyticsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    if(count($analyticsData) > 0){
                        $cdata4 = M_products::where([
                            ['m_products.status','<>','0']
                        ])
                        ->leftJoin('m_resellers','m_products.created_by','m_resellers.user_id')
                        ->leftJoin('m_users','m_resellers.user_id','m_users.user_id')
                        ->leftJoin('m_user_details','m_users.user_id','m_user_details.user_id')
                        ->select(
                            'm_resellers.reseller_id as reseller_id',
                            'm_user_details.fullname as seller_name',
                            \DB::raw('count(m_products.product_id) as total_products'),

                        )
                        ->groupBy('m_resellers.reseller_id')
                        ->orderByRaw('count(m_products.product_id) DESC')
                        ->take(5)
                        ->get();

                        $reseller_products = $cdata4->toArray();
                        if($paginate){
                            $analytics['data']['reseller_products'] = $reseller_products;
                        }else{
                            $analytics['reseller_products'] = $reseller_products;
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
