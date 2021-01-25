<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Order Products REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Orders;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Orders\M_order_products as M_default;


class C_order_products extends Controller{

    private $module = "order_product";
    private $create_required = ['order_id','products','status','created_by'];

/************* CREATE  *************/

    // [POST] api/order/product <-- Create new row
        function create(Request $reqs, $direct = false){

            $response = array();
            $request = ($direct) ? $direct : $reqs;
            $request_input = ($direct) ? $direct->products : $reqs->input('products');

            if($this->validation($this->create_required, $request)){

                $cdata = array();

                foreach($request_input as $product){

                    if($this->validation(array('product_detail_id'), (object) $product)){

                        $cdata_product = [
                            "order_id" => ($direct) ? $request->order_id : $reqs->input('order_id'),
                            "status" => ($direct) ? $request->status : $reqs->input('status'),
                            "created_by" => ($direct) ? $request->created_by : $reqs->input('created_by')
                        ];

                        // CHECK PRODUCT VARIANCE AVAIALBILITY
                        if($direct){
                            if(!isset($request->product_variance_id)){
                                $cdata_product['product_variance_id'] = NULL;
                            }
                        }else{
                            if($reqs->input('product_variance_id') == NULL){
                                $cdata_product['product_variance_id'] = NULL;
                            }
                        }

                        $cdata_product = array_merge($product, $cdata_product);
                        $cdata[] = $cdata_product; 
                        
                    }else{
                        $response["status"] = False;
                        $response["msg"] = "Missing parameters";
                        $response["debug"] = "Missing product parameters";

                        return $response;
                        break;
                    }
                }

                if(M_default::insert($cdata)){
                    $response["status"] = True;
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Error, please try again";
                    $response["debug"] = "Error DB";
                }

            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parameters";
            }

            return $response;
        
        }

/************* GET/READ  *************/

    // [GET] api/order/products <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            
            $paginate = ($reqs->input('paginate') == 'enable') ? true : false;
            $cdata = M_default::where('m_order_products.status','<>','0');

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

            $cdata->leftJoin('m_orders','m_order_products.order_id','m_orders.order_id')
            ->leftJoin('m_order_statuses','m_orders.status','m_order_statuses.order_status_id')
            
            ->leftJoin('m_shippings','m_orders.shipping_id','m_shippings.shipping_id')
            ->leftJoin('m_shipping_methods','m_shippings.shipping_method_id','m_shipping_methods.shipping_method_id')

            ->leftJoin('m_invoices','m_orders.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')

            ->leftJoin('m_customers','m_orders.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')

            ->leftJoin('m_product_details','m_order_products.product_detail_id','m_product_details.product_detail_id')
            ->leftJoin('m_products','m_product_details.product_id','m_products.product_id')
            ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
            ->select(

                //Orders
                'm_order_products.order_product_id',
                'm_orders.*',
                'm_order_statuses.status_name',

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


                //Product Details
                'm_products.product_id',
                'm_products.name as product_name',
                'm_product_details.normal_price',
                'm_product_details.product_detail_id',

                //Customer
                'm_customers.user_id as customer_user_id',
                'm_users.email as customer_email',
                'm_user_details.fullname as customer_fullname',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            )
            ->distinct();

            if($paginate){
                $cdata = $cdata->paginate(10);
            }else{
                $cdata = $cdata->get();
            }

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }



    // [GET] api/order/{id}/product <-- Get specific row
        function get($id, $direct = false){
            $response = array();

            $cdata = M_default::where(
                'order_id', '=', $id
            )->first();

            if($cdata){
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

    // [PUT] api/order/{id}/product <-- Update specific row
        function update(Request $reqs, $id, $direct = false){
            $response = array();
            $request = ($direct) ? $direct : $reqs;
            $request_input = ($direct) ? $direct : $reqs->input();

            if($this->validation(array('updated_by'), $request)){
                $cdata = new M_default;
                $delete = $cdata->harddelete($id);
                $request->status = 1;
                $request->created_by = ($direct) ? $request->updated_by : $reqs->input('updated_by');
                $request->updated_at = date("Y-m-d H:i:s");

                $response = $this->create(new Request, $request);

            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing order products updated_by";
            }

            return ($direct) ? $response : response()->json($response);
        }

    // [PUT] api/order/{id}/product <-- Restore deleted specific row
        function restore($id){
            $response = array();
        
            $order_products = M_default::withTrashed()->where(
                'order_id', '=', $id
            )->restore();

            if($order_products){
                $order_products = M_default::where(
                    'order_id', '=', $id
                )->update(['deleted_by'=>NULL]);
                // Return Data Get
                return $this->get($id);
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot find restored item";
            }

            return response($response);
        }



/************* DELETE  *************/

    // [DELETE] api/order/{id}/product <-- SoftDelete specific row
        function delete(Request $reqs, $id, $direct = false){
            $response = array();
            $request = ($direct) ? $direct : $reqs;
            $request_input = ($direct) ? $direct : $reqs->input();
            $request_deleted_by = ($direct) ? $direct->deleted_by : $reqs->input('deleted_by');
            
            if($this->validation(array('deleted_by'), $request)){

                $order_products = M_default::where(
                    'order_id', '=', $id
                )->update(
                    ['deleted_by' => $request_deleted_by]
                );

                $order_products_delete = M_default::where(
                    'order_id', '=', $id
                )->delete();

                if($order_products_delete){
                    $response['status'] = True;
                    $response["msg"] = "Sucessfully Deleted";
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Problem occured. Please try again";
                    $response["debug"] = "Fail deleting redeemed record";
                }

            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing deleted_by";
            }

            return ($direct) ? $response : response($response);
        }

    // [DELETE] api/order/{id}/product <-- Permanent delete specific row
        function harddelete(Request $req, $id, $direct = false){

            $response = array();
            $request = ($direct) ? $direct : $reqs;
            $request_input = ($direct) ? $direct : $reqs->input();
            $error = null;
            
            if(!$direct){
                $apiusername = config('custom.APIHardDelete.APIusername');
                $apipassword = config('custom.APIHardDelete.APIpassword');

                // Validate Username
                if($reqs->input('username')){
                    if($reqs->input('username') != $apiusername){
                        $error = "Invalid access"; 
                        $debug = "Username not the same";
                    }
                }else{
                    $error = "Invalid access";
                    $debug = "Username empty";

                }
            
                //Validate password
                if($reqs->input('pass')){
                    if($reqs->input('pass') != $apipassword){
                        $error = "Invalid access";
                        $debug = "Password not the same";
                    }
                }else{
                    $error = "Invalid access";
                    $debug = "Password empty";
                }
            }

            // Validate ID
            if(!isset($id)){
                $error = "Invalid ID"; 
                $debug = "ID empty";
            }

            if(!$error){
                $cdata = new M_default();
                $cdata_response = $cdata->harddelete($id);
                if($cdata_response["status"]){
                    $response["status"] = TRUE;
                    $response["msg"] = $cdata_response["msg"];
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

            return ($direct) ? $response : response($response);


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
