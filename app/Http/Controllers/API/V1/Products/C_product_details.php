<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Product_Details REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Products;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Products\M_product_details as M_default;


class C_product_details extends Controller{

    private $module = "product_detail";
    private $create_required = ['product_id','normal_price','status','created_by'];

/************* CREATE  *************/

    // [POST] api/product/detail <-- Create new row
        function create($reqs){

            $response = array();

            if($this->validation($this->create_required, $reqs)){

                $cdata = new M_default;
                foreach($reqs as $colname => $value){
                    $cdata->{$colname} = $value;
                }
        
                $cdata->created_at = date("Y-m-d H:i:s");
                $cdata->save();

                $response["status"] = True;
                $response["data"] = $cdata->toArray();
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parameters";
            }

            return $response;
        
        }

/************* GET/READ  *************/

    // [GET] api/product/{id}/details <-- Get all lists
        function get($reqs, $id){  
            $response = array();
            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $filters[] = ["product_id","=",$id];
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

    // [PUT] api/product/{id}/detail <-- Update specific row
        function update($reqs, $id){
            $response = array();

            if($this->validation(array('updated_by'), $reqs)){
                $cdata = M_default::where(
                    'product_id', '=', $id
                )->first();

                foreach($reqs as $colname => $value){
                    $cdata->{$colname} = $value;
                }
                
                $cdata->updated_at = date("Y-m-d H:i:s");
                $cdata->save();
                $response["status"] = True;
                $response["data"] = $cdata->toArray();
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing updated_by";
            }

            return $response;
        }

    // [PUT] api/product/{id}/detail/restore <-- Restore deleted specific row
        function restore($id){

            $response = array();
            $cdata = M_default::withTrashed()->where(
                'product_id', '=', $id
            )->first();
            $cdata->restore();
        
            if($cdata){

                $cdata2 = M_default::where(
                    'product_id', '=', $id
                )->first();
                $cdata2->deleted_by = NULL;
                $cdata2->save();

                if($cdata2){
                    $response["status"] = True;
                    $response["data"] = $cdata2->toArray();
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

    // [DELETE] api/product/{id}/detail <-- SoftDelete specific row
        function delete($reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){
                $cdata = M_default::where(
                    'product_id', '=', $id
                )->first();
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

            return $response;
        }

    // [DELETE] api/product/{id}/detail/delete <-- Permanent delete specific row
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
