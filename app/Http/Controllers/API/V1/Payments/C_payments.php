<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Payments REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Payments;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// CONTROLLERS
use App\Http\Controllers\API\V1\Invoices\C_invoices as C_invoices;

// MODELS
use App\Http\Models\API\V1\Payments\M_payments as M_default;
use App\Http\Models\API\V1\Orders\M_orders as M_orders;
use App\Http\Models\API\V1\Bookings\M_bookings as M_bookings;


class C_payments extends Controller{

    private $module = "payment";
    private $create_required = ['payment_method_id','total','status','created_by'];
    private $payment_chains = ['m_payments','m_invoices'];

/************* CREATE  *************/

    // [POST] api/payment <-- Create new row
    function create(Request $reqs){

        $response = array();

        if($this->validation($this->payment_chains, $reqs)){

            $payment_inputs = (object) $reqs->input($this->payment_chains[0]);
            $invoice_inputs = (object) $reqs->input($this->payment_chains[1]);
            
            if($this->validation($this->create_required, $payment_inputs)){

                // [1] CREATE PAYMENT
                $payment = new M_default;
                foreach($payment_inputs as $colname => $value){
                    $payment->{$colname} = $value;
                }
        
                $payment->created_at = date("Y-m-d H:i:s");
                $payment->save();


                if($this->validation(array('updated_by','invoice_id'), $invoice_inputs)){
                    
                    // [2] UPDATE INVOICE PAYMENT
                        $invoice_inputs->payment_id = $payment->payment_id;
                        $invoice_inputs->status = 2;
                        $invoice = new C_invoices;
                        $invoice_response = $invoice->update(new Request, $invoice_inputs->invoice_id, $invoice_inputs);
                        if($invoice_response['status']){

                            if($reqs->input('m_orders') != NULL){

                                // [3.1] UPDATE ORDER STATUS
                                $order_inputs = (object) $reqs->input('m_orders');
                                if($this->validation(array('order_id','updated_by'), $order_inputs)){

                                    $order = M_orders::find($order_inputs->order_id);
                                    $order->status = 2;
                                    $order->updated_at = date("Y-m-d H:i:s");
                                    $order->save();
                                }
                                
                            }elseif($reqs->input('m_bookings') != NULL){

                                // [3.2] UPDATE BOOKING STATUS
                                $booking_inputs = (object) $reqs->input('m_bookings');
                                if($this->validation(array('booking_id','updated_by'), $booking_inputs)){

                                    $booking = M_bookings::find($booking_inputs->booking_id);
                                    $booking->status = 2;
                                    $booking->updated_at = date("Y-m-d H:i:s");
                                    $booking->save();
                                }

                            }

                            // RETURN TRUE REGARDLESS
                            $response = $this->get($payment->payment_id, true);

                        }else{
                            //$payment->harddelete($payment->payment_id);

                            $response["status"] = False;
                            $response["msg"] = $invoice_response['msg'];
                            $response["debug"] = $invoice_response['debug'];
                        }

                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing invoice parameters";
                }

            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing payment parameters";
            }

        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing parent parameters";
        }

        return response()->json($response);
    
    }

/************* GET/READ  *************/

    // [GET] api/payments <-- Get all lists
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



    // [GET] api/payment/{id} <-- Get specific row
    function get($id, $direct = false){
        $response = array();

        $cdata = M_default::where(
            $this->module.'_id', '=', $id
        )->get();

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

    // [PUT] api/payment/{id} <-- Update specific row
    function update(Request $reqs, $id){
        $response = array();

        if($this->validation(array('updated_by'), $reqs)){
            $cdata = M_default::find($id);

            foreach($reqs->input() as $colname => $value){
                $cdata->{$colname} = $value;
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

        return response()->json($response);
    }

    // [PUT] api/payment/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/payment/{id} <-- SoftDelete specific row
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

    // [DELETE] api/payment/delete/{id} <-- Permanent delete specific row
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
