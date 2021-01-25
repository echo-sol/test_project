<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Tags REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Tags;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Tags\M_tags as M_default;


class C_tags extends Controller{

    private $module = "tag";
    private $create_required = ['name','status','created_by'];

/************* CREATE  *************/

    // [POST] api/tag <-- Create new row
        function create(Request $reqs){

            $response = array();

            if($this->validation($this->create_required, $reqs)){

                $cdata = new M_default;
                foreach($reqs->input() as $colname => $value){
                    $cdata->{$colname} = $value;
                }
        
                $cdata->created_at = date("Y-m-d H:i:s");
                $cdata->save();

                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parameters";
            }

            return response()->json($response);
        
        }

/************* GET/READ  *************/

    // [GET] api/tags <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;
            $cdata = M_default::where('m_tags.status','<>','0');

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
            $cdata->select(
                'm_tags.*'
            )
            ->orderBy('m_tags.tag_id','DESC');
            $cdata = $cdata->get();

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }


    // [GET] api/tag/{id} <-- Get specific row
        function get(Request $reqs, $id){
            $response = array();

            $cdata = M_default::where('m_tags.tag_id', '=', $id);

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
            $cdata->select(
                'm_tags.*'
            );

            $cdata = $cdata->first();

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




/************* UPDATE  *************/

    // [PUT] api/tag/{id} <-- Update specific row
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

    // [PUT] api/tag/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/tag/{id} <-- SoftDelete specific row
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

    // [DELETE] api/tag/delete/{id} <-- Permanent delete specific row
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
