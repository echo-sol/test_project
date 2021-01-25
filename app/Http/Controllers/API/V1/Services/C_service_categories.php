<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Service Categories REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Services;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Services\M_service_categories as M_default;


class C_service_categories extends Controller{

    private $module = "service_category";
    private $create_required = ['service_id','category_id','status','created_by'];

/************* CREATE  *************/

    // [POST] api/service/category <-- Create new row
        function create($reqs){

            $response = array();

            if($this->validation($this->create_required, $reqs)){

                $cdata = array();
                foreach($reqs->category_id as $category_id){
                    $cdata[] = [
                        "category_id" => $category_id,
                        "service_id" => $reqs->service_id,
                        "status" => $reqs->status,
                        "created_by" => $reqs->created_by
                    ] ; 
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
                $response["debug"] = "Missing create parameters";
            }

            return $response;
        
        }

/************* GET/READ  *************/

    // [GET] api/service/{id}/categories <-- Get all lists
        function get($reqs, $id){  
            $response = array();
            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $filters[] = ["service_id","=",$id];
            $cdata = M_default::where($filters)->first();

            if($cdata){
                $response["status"] = True;
                $response["data"] = $cdata->toArray();
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return $response;
        }




/************* UPDATE  *************/

    // [PUT] api/service/{id}/category <-- Update specific row
        function update($reqs, $id){

            $response = array();

            if($this->validation(array('updated_by'), $reqs)){
                $cdata = new M_default;
                $delete = $cdata->harddelete($id);
                $reqs->service_id = $id;
                $reqs->status = 1;
                $reqs->created_by = $reqs->updated_by;
                $reqs->updated_at = date("Y-m-d H:i:s");

                $response = $this->create($reqs);
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing updated_by parameter";
            }

            return $response;
        }

    // [PUT] api/service/{id}/category/restore <-- Restore deleted specific row
        function restore($id){

            $response = array();
            $cdata = M_default::withTrashed()->where(
                'service_id', '=', $id
            )->restore();
        
            if($cdata){

                $cdata2 = M_default::where(
                    'service_id', '=', $id
                )->update(['deleted_by'=>NULL]);

                if($cdata2){
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

            return $response;
        }



/************* DELETE  *************/

    // [DELETE] api/service/{id}/category <-- SoftDelete specific row
        function delete($reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){
                $cdata = M_default::where(
                    'service_id', '=', $id
                )->update(
                    ['deleted_by' => $reqs->input('deleted_by')]
                );

                $cdelete = M_default::where(
                    'service_id', '=', $id
                )->delete();

                if($cdelete){
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

            return $response;
        }

    // [DELETE] api/service/{id}/category/delete <-- Permanent delete specific row
        function harddelete($req, $id, $override = FALSE){

            $response = array();
            $error = null;
            $apiusername = config('custom.APIHardDelete.APIusername');
            $apipassword = config('custom.APIHardDelete.APIpassword');

            if($override === FALSE){
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
            }

            // Validate ID
            if(!isset($id)){
                $error = "Invalid ID"; 
                $debug = "ID empty";
            }

            if(!$error){
                $cdata = new M_default();
                $dataCheck = M_default::where(
                    'service_id', '=', $id
                );

                $data = $cdata->harddelete($id);

                if($data["status"] ){
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

            return $response;


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
