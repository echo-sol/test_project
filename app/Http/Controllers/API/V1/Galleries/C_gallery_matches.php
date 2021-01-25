<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Galleries REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Galleries;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Galleries\M_gallery_matches as M_default;


class C_gallery_matches extends Controller{

    private $module = "gallery_match";
    private $create_required = ['gallery_id','image_id','status','created_by'];

/************* CREATE  *************/

    // [POST] api/gallery/match <-- Create new row (modified)
    function create($reqs){

        $response = array();

        if($this->validation($this->create_required, $reqs)){

            $cdata = array();
            foreach($reqs->image_id as $image_id){
                $cdata[] = [
                    "image_id" => $image_id,
                    "gallery_id" => $reqs->gallery_id,
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
            $response["debug"] = "Missing parameters";
        }

        return $response;
    
    }

/************* GET/READ  *************/

    // [GET] api/gallery/matches <-- Get all lists
    /* function list(Request $reqs){  
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
    } */



    // [GET] api/gallery/match/{id} <-- Get specific row
    /* function get($id){
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

        return response()->json($response);
    } */




/************* UPDATE  *************/

    // [PUT] api/gallery/match/{id} <-- Update specific row (modified)
        function update($reqs, $id){
            $response = array();

            if($this->validation(array('updated_by'), $reqs)){
                $cdata = new M_default;
                $delete = $cdata->harddelete($id);
                $reqs->gallery_id = $id;
                $reqs->status = 1;
                $reqs->created_by = $reqs->updated_by;
                $reqs->updated_at = date("Y-m-d H:i:s");

                $response = $this->create($reqs);
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing updated_by";
            }

            return $response;
        }

    // [PUT] api/gallery/match/restore/{id} <-- Restore deleted specific row (modified)
        function restore($id){

            $response = array();
            $cdata = M_default::withTrashed()->where(
                'gallery_id', '=', $id
            )->restore();
        
            if($cdata){

                $cdata2 = M_default::where(
                    'gallery_id', '=', $id
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

    // [DELETE] api/gallery/match/{id} <-- SoftDelete specific row (modified)
        function delete($reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){

                $cdata = M_default::where(
                    'gallery_id', '=', $id
                )->get();

                $cdata = M_default::where(
                    'gallery_id', '=', $id
                )->update(
                    ['deleted_by' => $reqs->input('deleted_by')]
                );

                $cdelete = M_default::where(
                    'gallery_id', '=', $id
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

    // [DELETE] api/gallery/match/delete/{id} <-- Permanent delete specific row (modified)
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
