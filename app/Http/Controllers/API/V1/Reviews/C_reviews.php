<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Shipping REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Reviews;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// CONTROLLERS
use App\Http\Controllers\API\V1\Reviews\C_product_reviews as C_product_reviews;
use App\Http\Controllers\API\V1\Reviews\C_service_reviews as C_service_reviews;

// MODELS
use App\Http\Models\API\V1\Reviews\M_reviews as M_default;


class C_reviews extends Controller{

    private $module = "review";
    private $create_required = ['title','rating','status','created_by'];
    private $review_chains = ['m_reviews'];

/************* CREATE  *************/

    // [POST] api/review <-- Create new row
    function create(Request $reqs){

        $response = array();
        
        if($this->validation($this->review_chains, $reqs)){

            $reviews_inputs = (object) $reqs->input($this->review_chains[0]);

            if($this->validation($this->create_required, $reviews_inputs)){

                // [1] CREATE REVIEW
                $review = new M_default;
                foreach($reviews_inputs as $colname => $value){
                    $review->{$colname} = $value;
                }
        
                $review->created_at = date("Y-m-d H:i:s");
                if($review->save()){

                // [2] BIND REVIEW

                    // PRODUCT REVIEWS
                        if($reqs->input('m_product_reviews') != NULL){
                            $product_review_inputs = (object) $reqs->input('m_product_reviews');
                            $product_review_inputs->review_id = $review->review_id;

                            $product_review = new C_product_reviews;
                            $product_review_response = $product_review->create(new Request, $product_review_inputs);
                            if($product_review_response['status']){
                                $response["status"] = True;
                                $response["data"] = $review;
                            }else{
                                $response["msg"] = $product_review_response['msg'];
                                $response["debug"] = $product_review_response['debug'];
                            }

                        }else{
                            $response["status"] = True;
                            $response["data"] = $review;
                        }

                    // SERVICE REVIEWS
                        if($reqs->input('m_service_reviews') != NULL){
                            $service_review_inputs = (object) $reqs->input('m_service_reviews');
                            $service_review_inputs->review_id = $review->review_id;

                            $service_review = new C_service_reviews;
                            $service_review_response = $service_review->create(new Request, $service_review_inputs);
                            if($service_review_response['status']){
                                $response["status"] = True;
                                $response["data"] = $review;
                            }else{
                                $response["msg"] = $service_review_response['msg'];
                                $response["debug"] = $service_review_response['debug'];
                            }
                        }

                }else{
                    $response["msg"] = "Problem occured. Please try again";
                    $response["debug"] = "Cannot create from database";
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

    // [GET] api/reviews <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;
            $cdata = M_default::where('m_reviews.status','<>','0');

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
            
            $cdata = $cdata->get();
            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }



    // [GET] api/review/{id} <-- Get specific row
        function get(Request $reqs, $id){
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
            $cdata->select(
                'm_reviews.*'
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

    // [PUT] api/review/{id} <-- Update specific row
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
            $response["msg"] = $cdata;
        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing updated_by";
        }

        return response()->json($response);
    }

    // [PUT] api/review/restore/{id} <-- Restore deleted specific row
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
                $response["msg"] = $cdata2;
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

    // [DELETE] api/review/{id} <-- SoftDelete specific row
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

    // [DELETE] api/review/delete/{id} <-- Permanent delete specific row
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
