<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Resellers REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Resellers;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Resellers\M_resellers as M_default;

// CONTROLLERS
use App\Http\Controllers\API\V1\Users\C_users;

class C_resellers extends Controller{

    private $module = "reseller";
    private $create_required = ['user_id','status','created_by'];
    private $reseller_chains = ['m_resellers','m_reseller_details'];


/************* CREATE  *************/

    // [POST] api/reseller <-- Create new row
    function create(Request $reqs){
        $response = array();
        
        if($this->validation($this->reseller_chains, $reqs)){

            $resellers_inputs = (object) $reqs->input($this->reseller_chains[0]);
            $reseller_details_inputs = (object) $reqs->input($this->reseller_chains[1]);

            if($this->validation($this->create_required, $resellers_inputs)){

                // [1] CREATE RESELLER BASE
                    $reseller = new M_default;
                    foreach($resellers_inputs as $colname => $value){
                        $reseller->{$colname} = $value;
                    }
        
                    $reseller->created_at = date("Y-m-d H:i:s");
                    $reseller->save();

                // [2] CREATE RESELLER DETAILS
                    $reseller_details_inputs->reseller_id = $reseller->reseller_id;
                    $reseller_details = new C_reseller_details;
                    $reseller_details_response = $reseller_details->create(new Request, $reseller_details_inputs);
                    if($reseller_details_response['status']){
                        $response = $this->get(new Request, $reseller->reseller_id, true);

                    }else{
                        // CLEAR RESELLER
                        $service->harddelete($reseller->reseller_id);
                        $response["status"] = False;
                        $response["msg"] = $reseller_details_response['msg'];
                        $response["debug"] = $reseller_details_response['debug'];
                    }
    
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing reseller parameters";
            }

        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing parent parameters";
        }

        
        return response()->json($response);
        
    
    }

/************* GET/READ  *************/

    // [GET] api/resellers <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $filters = json_decode($reqs->input('filters'));
            $cdata = M_default::where($filters)
            ->leftJoin("m_users","m_resellers.user_id","m_users.user_id")
            ->leftJoin("m_user_details","m_users.user_id","m_user_details.user_id")
            ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')
            ->select(
                //RESELLERS
                "m_resellers.*",

                //USERS
                "m_users.email",
                "m_users.username",

                //USER DETAILS
                "m_user_details.fullname",
                "m_user_details.phone",

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            )
            ->get();

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }


    // [GET] api/reseller/{id} <-- Get specific row
        function get(Request $reqs, $id){
            $response = array();

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){

                $cdata = M_default::where('m_resellers.reseller_id', '=', $id);
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_resellers.reseller_id', '=', $id];
                $cdata = M_default::where($filters);
            }
            
            // HIDE SUPER
            $cdata->where('m_users.user_role_id','>','0');

            $cdata->leftJoin("m_users","m_resellers.user_id","m_users.user_id")
            ->leftJoin("m_user_details","m_users.user_id","m_user_details.user_id")
            ->leftJoin("m_reviewers","m_users.user_id","m_reviewers.user_id")
            ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')
            ->select(
                //RESELLERS
                "m_resellers.*",
                "m_resellers.status as reseller_status",

                //REVIEWERS
                "m_reviewers.status as reviewer_status",
                "m_reviewers.reviewer_id as reviewer_id",

                //USERS
                "m_users.email",
                "m_users.username",

                //USER DETAILS
                "m_user_details.fullname",
                "m_user_details.phone",

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            );
            
            $cdata = $cdata->first();

            if($cdata){

                $reseller = $cdata->toArray();

                // INJECT USER DETAILS
                $user = new C_users;
                $user_response = $user->get($reseller['user_id'], true);
                $reseller = array_merge($reseller, $user_response['data']->toArray());

                $response["status"] = True;
                $response["data"] = $reseller;
                
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return response()->json($response);
        }




/************* UPDATE  *************/

    // [PUT] api/reseller/{id} <-- Update specific row
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

    // [PUT] api/reseller/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/reseller/{id} <-- SoftDelete specific row
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

    // [DELETE] api/reseller/delete/{id} <-- Permanent delete specific row
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
