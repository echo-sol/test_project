<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Shipping REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Shippings;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Shippings\M_shippings as M_default;
use App\Http\Models\API\V1\Orders\M_orders as M_orders;


class C_shippings extends Controller{

    private $module = "shipping";
    private $create_required = ['shipping_method_id','address','status','created_by'];
    private $shipping_chains = ['m_shippings','m_orders'];

/************* CREATE  *************/

    // [POST] api/shipping <-- Create new row
    function create(Request $reqs, $direct = false){

        $response = array();
        $request = ($direct) ? $direct : $reqs;
        $request_input = ($direct) ? $direct : $reqs->input();

        if($this->validation($this->shipping_chains, $reqs)){

            $shipping_inputs = (object) $reqs->input($this->shipping_chains[0]);
            $order_inputs = (object) $reqs->input($this->shipping_chains[1]);
            
            if($this->validation($this->create_required, $shipping_inputs)){

                // [1] CREATE SHIPPING
                $shipping = new M_default;
                foreach($shipping_inputs as $colname => $value){
                    $shipping->{$colname} = $value;
                }
        
                $shipping->created_at = date("Y-m-d H:i:s");
                $shipping->save();

                
                if($this->validation(array('order_id','updated_by'), $order_inputs)){

                // [2] UPDATE ORDER STATUS
                    $order = M_orders::find($order_inputs->order_id);
                    $order->shipping_id = $shipping->shipping_id;
                    $order->status = 3;
                    $order->updated_at = date("Y-m-d H:i:s");
                    if($order->save()){
                        $response = $this->get($shipping->shipping_id, true);
                    }else{
                        $response["status"] = False;
                        $response["msg"] = "Problem occured. Please try again";
                        $response["debug"] = "Cannot update order status from database";
                    }
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing orders parameters";
                }
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing shipping parameters";
            }

        }else{

            if($direct){
                
                if($this->validation($this->create_required, $request)){
                    
                    $shipping = new M_default;
                    foreach($request_input as $colname => $value){
                        $shipping->{$colname} = $value;
                    }
            
                    $shipping->created_at = date("Y-m-d H:i:s");
                    if($shipping->save()){
                        $response = $this->get($shipping->shipping_id, true);
                    }else{
                        $response["status"] = False;
                        $response["msg"] = "Problem occured. Please try again";
                        $response["debug"] = "Cannot update order status from database";
                    }
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing shipping parameters";
                }

            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parent parameters";
            }
        }

        return ($direct) ? $response : response()->json($response);
    
    }

/************* GET/READ  *************/

    // [GET] api/shippings <-- Get all lists
    function list(Request $reqs){  
        $response = array();
        $filters = json_decode($reqs->input('filters'));
        $cdata = M_default::where($filters)->get();

        if($cdata){
            $response["status"] = True;
            $response["data"] = $cdata;
        }else{
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = "Cannot retrieve from database";
        }

        return response()->json($response);
    }



    // [GET] api/shipping/{id} <-- Get specific row
    function get($id, $direct = false){
        $response = array();

        $cdata = M_default::where(
            $this->module.'_id', '=', $id
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

    // [PUT] api/shipping/{id} <-- Update specific row
    function update(Request $reqs, $id, $direct = false){
        $response = array();
        $request = ($direct) ? $direct : $reqs;
        $request_input = ($direct) ? $direct : $reqs->input();

        if($this->validation(array('updated_by'), $request)){
            $cdata = M_default::find($id);

            foreach($request_input as $colname => $value){
                $cdata->{$colname} = $value;
            }
            
            $cdata->updated_at = date("Y-m-d H:i:s");
            $cdata->save();
            $response["status"] = True;
            $response["data"] = ($direct) ? $cdata->toArray() : $cdata;
        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing updated_by";
        }

        return ($direct) ? $response : response()->json($response);
    }

    // [PUT] api/shipping/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/shipping/{id} <-- SoftDelete specific row
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

    // [DELETE] api/shipping/delete/{id} <-- Permanent delete specific row
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
