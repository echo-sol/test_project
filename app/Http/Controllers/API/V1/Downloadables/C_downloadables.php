<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Downloadables REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Downloadables;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Downloadables\M_downloadables as M_default;
use App\Http\Models\API\V1\Downloadables\M_downloadable_matches as M_downloadable_matches;

class C_downloadables extends Controller{

    private $module = "downloadable";
    private $create_required = ['status','created_by'];
    private $downloadables_chains = ['m_downloadables','m_downloadable_matches'];

/************* CREATE  *************/

    // [POST] api/downloadable <-- Create new row
        function create(Request $reqs){

            $response = array();

            if($this->validation($this->downloadables_chains, $reqs)){

                $downloadables_inputs = (object) $reqs->input($this->downloadables_chains[0]);
                $downloadable_matches_inputs = (object) $reqs->input($this->downloadables_chains[1]);

                if($this->validation($this->create_required, $downloadables_inputs)){

                    // [1] CREATE DOWNLOADABLES BASE
                    $downloadable = new M_default;
                    foreach($downloadables_inputs as $colname => $value){
                        $downloadable->{$colname} = $value;
                    }
            
                    $downloadable->created_at = date("Y-m-d H:i:s");
                    $downloadable->save();

                    // [2] CREATE DOWNLOADABLES MATCHES
                        $downloadable_matches_inputs->downloadable_id = $downloadable->downloadable_id;
                        $downloadable_matches = new C_downloadable_matches;
                        $downloadable_matches_response = $downloadable_matches->create($downloadable_matches_inputs);
                        if($downloadable_matches_response['status']){
                            $response = $this->get($downloadable->downloadable_id, true);
                        }else{
                            // CLEAR PRODUCT & PRODUCT DETAILS
                            $downloadable->harddelete($downloadable->downloadable_id);
                            $downloadable_matches->harddelete($reqs, $downloadable->downloadable_id, TRUE);
                            $response["status"] = False;
                            $response["msg"] = $downloadable_matches_response['msg'];
                            $response["debug"] = $downloadable_matches_response['debug'];
                        }
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing parameters";
                }

            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parent parameters";
            }

            return response()->json($response);
        
        }

/************* GET/READ  *************/

    // [GET] api/downloadables <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $filters = json_decode($reqs->input('filters'));
            $cdata = M_default::where($filters)->get();

            if($cdata){
            // INJECT DOWNLOADABLES MATCHES
                $downloadables = $cdata->toArray();
                foreach($downloadables as $downloadableKey => $downloadable){
                    $downloadable_id = $downloadable['downloadable_id'];
                    $cdata2 = M_downloadable_matches::where([
                        ['m_downloadable_matches.downloadable_id','=',$downloadable_id]
                    ])
                    ->leftJoin('m_files','m_downloadable_matches.file_id','=','m_files.file_id')
                    ->select('m_files.*')
                    ->get();
                    
                    // CLEAN DOWNLOADABLES MATCHES IF NULL
                    $files = $cdata2->toArray();
                    foreach($files as $fileKey => $file){
                        if($file['file_id'] == NULL){
                            unset($files[$fileKey]);
                        }
                    }
                    $downloadables[$downloadableKey]['file_sets'] = $files;
                }

                $cdata = $downloadables;
            
                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return response()->json($response);
        }



    // [GET] api/downloadable/{id} <-- Get specific row
        function get($id, $internal = false){
            $response = array();

            $cdata = M_default::where(
                $this->module.'_id', '=', $id
            )->first();

            // INJECT downloadable MATCHES
            if(isset($cdata['downloadable_id'])){
                $downloadable_id = $cdata['downloadable_id'];
                $cdata2 = M_downloadable_matches::where([
                    ['m_downloadable_matches.downloadable_id','=',$downloadable_id]
                ])
                ->leftJoin('m_files','m_downloadable_matches.file_id','=','m_files.file_id')
                ->select('m_files.*')
                ->get();
                // CLEAN downloadable MATCHES IF NULL
                $files = $cdata2->toArray();
                foreach($files as $fileKey => $file){
                    if($file['file_id'] == NULL){
                        unset($files[$fileKey]);
                    }
                }
                $cdata['file_sets'] = $files;
            }
            

            if($cdata){
                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            if($internal){
                return $response;
            }else{
                return response($response);
            }
        }




/************* UPDATE  *************/

    // [PUT] api/downloadable/{id} <-- Update specific row
        function update(Request $reqs, $id){
            $response = array();

            if($this->validation($this->downloadables_chains, $reqs)){

                $downloadables_inputs = (object) $reqs->input($this->downloadables_chains[0]);
                $downloadable_matches_inputs = (object) $reqs->input($this->downloadables_chains[1]);

                if($this->validation(array('updated_by'), $downloadables_inputs)){

                    // [1] UPDATE DOWNLOADABLES BASE
                        $downloadable = M_default::find($id);
                        foreach($downloadables_inputs as $colname => $value){
                            $downloadable->{$colname} = $value;
                        }
                    
                        $downloadable->updated_at = date("Y-m-d H:i:s");
                        $downloadable->save();

                        // [2] UPDATE DOWNLOADABLES MATCHES
                        $downloadable_matches = new C_downloadable_matches;
                        $downloadable_matches_response = $downloadable_matches->update($downloadable_matches_inputs, $downloadable->downloadable_id);
                        if($downloadable_matches_response['status']){
                            $response = $this->get($downloadable->downloadable_id, true);
                        }else{
                            $response["status"] = False;
                            $response["msg"] = $downloadable_matches_response['msg'];
                            $response["debug"] = $downloadable_matches_response['debug'];
                        }
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing updated_by";
                }
                
            }else{
                
            }

            return response()->json($response);
        }

    // [PUT] api/downloadable/restore/{id} <-- Restore deleted specific row
        function restore($id){
            $response = array();

            // [1] RESTORE DOWNLOADABLES        
            if(M_default::withTrashed()->find($id)->restore()){

                $downloadable = M_default::find($id);
                $downloadable_update = $downloadable->update(['deleted_by'=>NULL]);

                if($downloadable){

                // [2] RESTORE DOWNLOADABLES MATCHES
                    $downloadable_matches = new C_downloadable_matches;
                    $downloadable_matches_response = $downloadable_matches->restore($downloadable->downloadable_id);
                    if($downloadable_matches_response['status']){
                        $response = $this->get($downloadable->downloadable_id, true);
                    }else{
                        $response["status"] = False;
                        $response["msg"] = $downloadable_matches_response['msg'];
                        $response["debug"] = $downloadable_matches_response['debug'];
                    }

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

    // [DELETE] api/downloadable/{id} <-- SoftDelete specific row
        function delete(Request $reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){
                $downloadable = M_default::find($id);
                $downloadable->deleted_by = $reqs->input('deleted_by');
                $downloadable->save();

                // [1] DELETE DOWNLOADABLES
                if($downloadable->delete()){

                // [2] DELETE DOWNLOADABLES MATCHES
                    $downloadable_matches = new C_downloadable_matches;
                    $downloadable_matches_response = $downloadable_matches->delete($reqs, $id);
                    
                    if($downloadable_matches_response['status']){
                        $response['status'] = True;
                        $response["msg"] = "Sucessfully Deleted";
                    }else{
                        $response["status"] = False;
                        $response["msg"] = $downloadable_matches_response['msg'];
                        $response["debug"] = $downloadable_matches_response['debug'];
                    }
            
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

    // [DELETE] api/downloadable/delete/{id} <-- Permanent delete specific row
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

                // [1] DELETE DOWNLOADABLES
                $downloadable = new M_default();
                $downloadable_response = $downloadable->harddelete($id);
                if($downloadable_response["status"]){

                    // [2] DELETE DOWNLOADABLES MATCHES
                    $downloadable_matches = new C_downloadable_matches;
                    $downloadable_matches_response = $downloadable_matches->harddelete($req, $id, TRUE);
                    if($downloadable_matches_response['status']){
                        $response["status"] = TRUE;
                        $response["msg"] = $downloadable_matches_response["msg"];
                    }else{
                        $response["status"] = False;
                        $response["msg"] = $downloadable_matches_response["msg"];
                        $response["debug"] = $downloadable_matches_response["debug"];
                        }
                }else{
                    $response["status"] = False;
                    $response["msg"] = $product_response["msg"];
                    $response["debug"] = $product_response["debug"];
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
