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
use App\Http\Models\API\V1\Galleries\M_galleries as M_default;
use App\Http\Models\API\V1\Galleries\M_gallery_matches as M_gallery_matches;

class C_galleries extends Controller{

    private $module = "gallery";
    private $create_required = ['status','created_by'];
    private $gallery_chains = ['m_galleries','m_gallery_matches'];

/************* CREATE  *************/

    // [POST] api/gallery <-- Create new row
        function create(Request $reqs){

            $response = array();

            if($this->validation($this->gallery_chains, $reqs)){

                $galleries_inputs = (object) $reqs->input($this->gallery_chains[0]);
                $gallery_matches_inputs = (object) $reqs->input($this->gallery_chains[1]);

                if($this->validation($this->create_required, $galleries_inputs)){

                    // [1] CREATE GALLERY BASE
                    $gallery = new M_default;
                    foreach($galleries_inputs as $colname => $value){
                        $gallery->{$colname} = $value;
                    }
            
                    $gallery->created_at = date("Y-m-d H:i:s");
                    $gallery->save();

                    // [2] CREATE GALLERY MATCHES
                        $gallery_matches_inputs->gallery_id = $gallery->gallery_id;
                        $gallery_matches = new C_gallery_matches;
                        $gallery_matches_response = $gallery_matches->create($gallery_matches_inputs);
                        if($gallery_matches_response['status']){
                            $response = $this->get($gallery->gallery_id, true);
                        }else{
                            // CLEAR PRODUCT & PRODUCT DETAILS
                            $gallery->harddelete($gallery->gallery_id);
                            $gallery_matches->harddelete($reqs, $gallery->gallery_id, TRUE);
                            $response["status"] = False;
                            $response["msg"] = $gallery_matches_response['msg'];
                            $response["debug"] = $gallery_matches_response['debug'];
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

    // [GET] api/galleries <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $filters = json_decode($reqs->input('filters'));
            $cdata = M_default::where($filters)->get();

            // INJECT GALLERY MATCHES
                $galleries = $cdata->toArray();
                foreach($galleries as $galleryKey => $gallery){
                    $gallery_id = $gallery['gallery_id'];
                    $cdata2 = M_gallery_matches::where([
                        ['m_gallery_matches.gallery_id','=',$gallery_id]
                    ])
                    ->leftJoin('m_images','m_gallery_matches.image_id','=','m_images.image_id')
                    ->select('m_images.*')
                    ->get();
                    
                    // CLEAN GALLERY MATCHES IF NULL
                    $images = $cdata2->toArray();
                    foreach($images as $imageKey => $image){
                        if($image['image_id'] == NULL){
                            unset($images[$imageKey]);
                        }
                    }
                    $galleries[$galleryKey]['image_sets'] = $images;
                }

            $cdata = $galleries;
            
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



    // [GET] api/gallery/{id} <-- Get specific row
        function get($id, $internal = false){
            $response = array();

            $cdata = M_default::where(
                $this->module.'_id', '=', $id
            )->first();

            // INJECT GALLERY MATCHES
            if(isset($cdata['gallery_id'])){
                $gallery_id = $cdata['gallery_id'];
                $cdata2 = M_gallery_matches::where([
                    ['m_gallery_matches.gallery_id','=',$gallery_id]
                ])
                ->leftJoin('m_images','m_gallery_matches.image_id','=','m_images.image_id')
                ->select('m_images.*')
                ->get();
                // CLEAN GALLERY MATCHES IF NULL
                $images = $cdata2->toArray();
                foreach($images as $imageKey => $image){
                    if($image['image_id'] == NULL){
                        unset($images[$imageKey]);
                    }
                }
                $cdata['image_sets'] = $images;
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

    // [PUT] api/gallery/{id} <-- Update specific row
        function update(Request $reqs, $id){
            $response = array();

            if($this->validation($this->gallery_chains, $reqs)){

                $galleries_inputs = (object) $reqs->input($this->gallery_chains[0]);
                $gallery_matches_inputs = (object) $reqs->input($this->gallery_chains[1]);

                if($this->validation(array('updated_by'), $galleries_inputs)){

                    // [1] UPDATE GALLERY BASE
                        $gallery = M_default::find($id);
                        foreach($galleries_inputs as $colname => $value){
                            $gallery->{$colname} = $value;
                        }
                    
                        $gallery->updated_at = date("Y-m-d H:i:s");
                        $gallery->save();

                        // [2] UPDATE GALLERY MATCHES
                        $gallery_matches = new C_gallery_matches;
                        $gallery_matches_response = $gallery_matches->update($gallery_matches_inputs, $gallery->gallery_id);
                        if($gallery_matches_response['status']){
                            $response = $this->get($gallery->gallery_id, true);
                        }else{
                            $response["status"] = False;
                            $response["msg"] = $gallery_matches_response['msg'];
                            $response["debug"] = $gallery_matches_response['debug'];
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

    // [PUT] api/gallery/restore/{id} <-- Restore deleted specific row
        function restore($id){
            $response = array();

            // [1] RESTORE GALLERY        
            if(M_default::withTrashed()->find($id)->restore()){

                $gallery = M_default::find($id);
                $gallery_update = $gallery->update(['deleted_by'=>NULL]);

                if($gallery){

                // [2] RESTORE GALLERY MATCHES
                    $gallery_matches = new C_gallery_matches;
                    $gallery_matches_response = $gallery_matches->restore($gallery->gallery_id);
                    if($gallery_matches_response['status']){
                        $response = $this->get($gallery->gallery_id, true);
                    }else{
                        $response["status"] = False;
                        $response["msg"] = $gallery_matches_response['msg'];
                        $response["debug"] = $gallery_matches_response['debug'];
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

    // [DELETE] api/gallery/{id} <-- SoftDelete specific row
        function delete(Request $reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){
                $gallery = M_default::find($id);
                $gallery->deleted_by = $reqs->input('deleted_by');
                $gallery->save();

                // [1] DELETE GALLERY
                if($gallery->delete()){

                // [2] DELETE GALLERY MATCHES
                    $gallery_matches = new C_gallery_matches;
                    $gallery_matches_response = $gallery_matches->delete($reqs, $id);
                    
                    if($gallery_matches_response['status']){
                        $response['status'] = True;
                        $response["msg"] = "Sucessfully Deleted";
                    }else{
                        $response["status"] = False;
                        $response["msg"] = $gallery_matches_response['msg'];
                        $response["debug"] = $gallery_matches_response['debug'];
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

    // [DELETE] api/gallery/delete/{id} <-- Permanent delete specific row
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

                // [1] DELETE GALLERY
                $gallery = new M_default();
                $gallery_response = $gallery->harddelete($id);
                if($gallery_response["status"]){

                    // [2] DELETE GALLERY MATCHES
                    $gallery_matches = new C_gallery_matches;
                    $gallery_matches_response = $gallery_matches->harddelete($req, $id, TRUE);
                    if($gallery_matches_response['status']){
                        $response["status"] = TRUE;
                        $response["msg"] = $gallery_matches_response["msg"];
                    }else{
                        $response["status"] = False;
                        $response["msg"] = $gallery_matches_response["msg"];
                        $response["debug"] = $gallery_matches_response["debug"];
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
