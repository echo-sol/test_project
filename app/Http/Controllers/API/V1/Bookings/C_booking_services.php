<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Booking Services REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Bookings;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Bookings\M_booking_services as M_default;


class C_booking_services extends Controller{

    private $module = "booking_service";
    private $create_required = ['booking_id','services','status','created_by'];

/************* CREATE  *************/

    // [POST] api/booking/service <-- Create new row
        function create(Request $reqs, $direct = false){

            $response = array();
            $request = ($direct) ? $direct : $reqs;
            $request_input = ($direct) ? $direct->services : $reqs->input('services');

            if($this->validation($this->create_required, $request)){

                $cdata = array();

                foreach($request_input as $service){

                    if($this->validation(array('service_detail_id'), (object) $service)){

                        $cdata_service = [
                            "booking_id" => ($direct) ? $request->booking_id : $reqs->input('booking_id'),
                            "status" => ($direct) ? $request->status : $reqs->input('status'),
                            "created_by" => ($direct) ? $request->created_by : $reqs->input('created_by')
                        ];

                        $cdata_service = array_merge($service, $cdata_service);
                        $cdata[] = $cdata_service; 
                        
                    }else{
                        $response["status"] = False;
                        $response["msg"] = "Missing parameters";
                        $response["debug"] = "Missing booking service parameters";

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

    // [GET] api/booking/services <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            
            $paginate = ($reqs->input('paginate') == 'enable') ? true : false;
            $cdata = M_default::where('m_booking_services.status','<>','0')->whereNull('m_bookings.deleted_by');

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

            $cdata->leftJoin('m_bookings','m_booking_services.booking_id','m_bookings.booking_id')
            ->leftJoin('m_booking_statuses','m_bookings.status','m_booking_statuses.booking_status_id')
            
            ->leftJoin('m_invoices','m_bookings.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')

            ->leftJoin('m_customers','m_bookings.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')

            ->leftJoin('m_service_details','m_booking_services.service_detail_id','m_service_details.service_detail_id')
            ->leftJoin('m_services','m_service_details.service_id','m_services.service_id')
            ->leftJoin('m_images','m_service_details.featured_img','=','m_images.image_id')
            ->select(

                //Bookings
                'm_booking_services.booking_service_id',
                'm_bookings.*',
                'm_booking_statuses.status_name',

                //Payment
                'm_invoices.billing_address as billing_address',
                'm_payments.ref_id as payment_ref_id',
                'm_payments.notes as payment_notes',
                'm_payment_methods.method_name as payment_method_name',


                //Service Details
                'm_services.service_id',
                'm_services.name as service_name',
                'm_service_details.normal_price',
                'm_service_details.service_detail_id',

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



    // [GET] api/booking/{id}/service <-- Get specific row
        function get($id, $direct = false){
            $response = array();

            $cdata = M_default::where($this->module.'_id', '=', $id);

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

            $cdata->leftJoin('m_bookings','m_booking_services.booking_id','m_bookings.booking_id')
            ->leftJoin('m_booking_statuses','m_bookings.status','m_booking_statuses.booking_status_id')
            
            ->leftJoin('m_invoices','m_bookings.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')

            ->leftJoin('m_customers','m_bookings.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')

            ->leftJoin('m_service_details','m_booking_services.service_detail_id','m_service_details.service_detail_id')
            ->leftJoin('m_services','m_service_details.service_id','m_services.service_id')
            ->leftJoin('m_images','m_service_details.featured_img','=','m_images.image_id')
            ->select(

                //Bookings
                'm_booking_services.booking_service_id',
                'm_bookings.*',
                'm_booking_statuses.status_name',

                //Payment
                'm_invoices.billing_address as billing_address',
                'm_payments.ref_id as payment_ref_id',
                'm_payments.notes as payment_notes',
                'm_payment_methods.method_name as payment_method_name',


                //Service Details
                'm_services.service_id',
                'm_services.name as service_name',
                'm_service_details.normal_price',
                'm_service_details.service_detail_id',

                //Customer
                'm_customers.user_id as customer_user_id',
                'm_users.email as customer_email',
                'm_user_details.fullname as customer_fullname',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            )
            ->distinct();
            
            $cdata = $cdata->first();

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

    // [PUT] api/booking/{id}/service <-- Update specific row
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
                $response["debug"] = "Missing booking services updated_by";
            }

            return ($direct) ? $response : response()->json($response);
        }

    // [PUT] api/booking/{id}/service <-- Restore deleted specific row
        function restore($id){
            $response = array();
        
            $booking_services = M_default::withTrashed()->where(
                'booking_id', '=', $id
            )->restore();

            if($booking_services){
                $booking_services = M_default::where(
                    'booking_id', '=', $id
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

    // [DELETE] api/booking/{id}/service <-- SoftDelete specific row
        function delete(Request $reqs, $id, $direct = false){
            $response = array();
            $request = ($direct) ? $direct : $reqs;
            $request_input = ($direct) ? $direct : $reqs->input();
            $request_deleted_by = ($direct) ? $direct->deleted_by : $reqs->input('deleted_by');
            
            if($this->validation(array('deleted_by'), $request)){

                $booking_services = M_default::where(
                    'booking_id', '=', $id
                )->update(
                    ['deleted_by' => $request_deleted_by]
                );

                $booking_services_delete = M_default::where(
                    'booking_id', '=', $id
                )->delete();

                if($booking_services_delete){
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

    // [DELETE] api/booking/{id}/service <-- Permanent delete specific row
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
