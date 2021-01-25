<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Reviewers REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Reviewers;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Reviewers\M_reviewers as M_default;
use App\Http\Models\API\V1\Reviews\M_service_reviews as M_service_reviews;

// CONTROLLERS
use App\Http\Controllers\API\V1\Users\C_users;

class C_reviewers extends Controller{

    private $module = "reviewer";
    private $create_required = ['user_id','status','created_by'];

/************* CREATE  *************/

    // [POST] api/reviewer <-- Create new row
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

    // [GET] api/reviewers <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata = M_default::where('m_reviewers.status','<>','-99');
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_reviewers.status','<>','-99'];
                $cdata = M_default::where($filters);
            }

            $cdata->leftJoin("m_users","m_reviewers.user_id","m_users.user_id")
            ->leftJoin("m_user_details","m_users.user_id","m_user_details.user_id")
            ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')
            ->select(
                //REVIEWER
                "m_reviewers.*",
                "m_reviewers.status as reviewer_status",

                //USERS
                "m_users.email",
                "m_users.username",

                //USER DETAILS
                "m_user_details.fullname",
                "m_user_details.phone",

                //Images
                'm_images.url as propic_url',
                'm_images.alt as propic_alt',
            );

            if($paginate){
                $cdata = $cdata->paginate(10);
            }else{
                $cdata = $cdata->get();
            }

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }


    // [GET] api/reviewer/{id} <-- Get specific row
        function get(Request $reqs, $id){
            $response = array();

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){

                $cdata = M_default::where('m_reviewers.reviewer_id', '=', $id);
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_reviewers.reviewer_id', '=', $id];
                $cdata = M_default::where($filters);
            }
            
            // HIDE SUPER
            $cdata->where('m_users.user_role_id','>','0');

            $cdata->leftJoin("m_users","m_reviewers.user_id","m_users.user_id")
            ->leftJoin("m_user_details","m_users.user_id","m_user_details.user_id")

            ->leftJoin("m_services","m_users.user_id","m_services.created_by")
            ->leftJoin("m_service_details","m_services.service_id","m_service_details.service_id")
            ->leftJoin("m_service_reviews","m_service_details.service_id","m_service_reviews.service_id")
            ->leftJoin("m_reviews","m_service_reviews.review_id","m_reviews.review_id")
            
            ->leftjoin('m_cities', 'm_user_details.city_id', '=', 'm_cities.city_id')
            ->leftjoin('m_states', 'm_user_details.state_id', '=', 'm_states.state_id')
            ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')
            ->select(
                //REVIEWER
                "m_reviewers.*",
                "m_reviewers.status as reviewer_status",

                //USERS
                "m_users.email",
                "m_users.username",

                //USER DETAILS
                'm_user_details.*',
                'm_cities.name as city_name',
                'm_states.name as state_name',

                //SERVICES DETAILS
                "m_services.service_id",
                "m_service_details.service_detail_id",
                "m_service_details.normal_price",
                "m_service_details.description as service_description",

                //REVIEWS
                "m_reviews.review_id",
                "m_reviews.title as review_title",
                "m_reviews.rating as review_rating",
                "m_reviews.message as review_message",

                //Images
                'm_images.url as user_propic_url',
                'm_images.alt as user_propic_alt',
            );
            
            $cdata = $cdata->first();

            if($cdata){

                $reviewer = $cdata->toArray();
                $service_id = $reviewer['service_id'];

                // INJECT SERVICE REVIEWS
                    $service_reviews = M_service_reviews::where([
                        ['m_service_reviews.service_id','=',$service_id],
                        ['m_service_reviews.status','<>',-99],
                        ['m_reviews.status','<>',-99]
                    ])
                    ->leftJoin('m_reviews', 'm_service_reviews.review_id', 'm_reviews.review_id')
                    ->leftJoin('m_users', 'm_reviews.created_by', 'm_users.user_id')
                    ->leftJoin('m_user_details', 'm_users.user_id', 'm_user_details.user_id')
                    ->leftJoin('m_images', 'm_user_details.propic', 'm_images.image_id')
                    ->select(
                        'm_reviews.*',

                        'm_service_reviews.service_review_id',

                        'm_users.email',
                        'm_user_details.fullname',
                        'm_user_details.propic',
                        'm_images.url as propic_url',
                        'm_images.alt as propic_alt'
                    )
                    ->get();

                    $reviewer['service_reviews'] = $service_reviews->toArray();

                $response["status"] = True;
                $response["data"] = $reviewer;
                
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return response()->json($response);
        }




/************* UPDATE  *************/

    // [PUT] api/reviewer/{id} <-- Update specific row
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

    // [PUT] api/reviewer/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/reviewer/{id} <-- SoftDelete specific row
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

    // [DELETE] api/reviewer/delete/{id} <-- Permanent delete specific row
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
