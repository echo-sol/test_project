<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Services REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Services;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

// MODELS
use App\Http\Models\API\V1\Services\M_services as M_default;
use App\Http\Models\API\V1\Services\M_service_details as M_service_details;
use App\Http\Models\API\V1\Reviews\M_reviews as M_reviews;
use App\Http\Models\API\V1\Reviews\M_service_reviews as M_service_reviews;
use App\Http\Models\API\V1\Galleries\M_gallery_matches as M_gallery_matches;
use App\Http\Models\API\V1\Services\M_service_categories as M_service_categories;
use App\Http\Models\API\V1\Downloadables\M_downloadable_matches as M_downloadable_matches;


class C_services extends Controller{

    private $module = "service";
    private $create_required = ['name','status','created_by'];
    private $service_chains = ['m_services','m_service_details','m_service_categories'];


/************* CREATE  *************/

    // [POST] api/service <-- Create new row
    function create(Request $reqs){
        $response = array();
        
        if($this->validation($this->service_chains, $reqs)){

            $services_inputs = (object) $reqs->input($this->service_chains[0]);
            $service_details_inputs = (object) $reqs->input($this->service_chains[1]);
            $service_categories_inputs = (object) $reqs->input($this->service_chains[2]);

            if($this->validation($this->create_required, $services_inputs)){

                // [1] CREATE SERVICE BASE
                    $service = new M_default;
                    foreach($services_inputs as $colname => $value){
                        $service->{$colname} = $value;
                    }
        
                    $service->created_at = date("Y-m-d H:i:s");
                    $service->save();

                // [2] CREATE SERVICE DETAILS
                    $service_details_inputs->service_id = $service->service_id;
                    $service_details = new C_service_details;
                    $service_details_response = $service_details->create($service_details_inputs);
                    if($service_details_response['status']){

                        // [3] CREATE SERVICE CATEGORIES
                            $service_categories_inputs->service_id = $service->service_id;
                            $service_categories = new C_service_categories;
                            $service_categories_response = $service_categories->create($service_categories_inputs);
                            if($service_categories_response['status']){

                                $response = $this->get(new Request, $service->service_id, true);

                            }else{
                                // CLEAR SERVICE & SERVICE DETAILS
                                $service->harddelete($service->service_id);
                                $service_details->harddelete($reqs, $service->service_id, TRUE);
                                $response["status"] = False;
                                $response["msg"] = $service_categories_response['msg'];
                                $response["debug"] = $service_categories_response['debug'];
                            }

                    }else{
                        // CLEAR SERVICE
                        $service->harddelete($service->service_id);
                        $response["status"] = False;
                        $response["msg"] = $service_details_response['msg'];
                        $response["debug"] = $service_details_response['debug'];
                    }
    
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing service parameters";
            }

        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing parent parameters";
        }

        
        return response()->json($response);
        
    
    }

/************* GET/READ  *************/

    // [GET] api/services <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata = M_default::where('m_services.status','<>','-99');
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_services.status','<>','-99'];
                $cdata = M_default::where($filters);
            }

            
            $cdata->leftJoin('m_service_details','m_services.service_id','=','m_service_details.service_id')
            ->leftJoin('m_service_reviews','m_services.service_id','=','m_service_reviews.service_id')
            ->leftJoin('m_reviews','m_service_reviews.review_id','=','m_reviews.review_id')
            ->leftJoin('m_images','m_service_details.featured_img','=','m_images.image_id')
            ->leftJoin('m_reviewers','m_services.created_by','m_reviewers.user_id')
            ->leftJoin('m_users','m_reviewers.user_id','m_users.user_id')
            ->leftJoin('m_user_details','m_users.user_id','m_user_details.user_id')
            ->select(

                //Services Details
                'm_service_details.*',

                //Services
                'm_services.name',
                'm_services.status as service_status',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',

                //Customer
                'm_users.email as reviewer_email',
                'm_user_details.fullname as reviewer_fullname',

                //Ratings
                \DB::raw('AVG(m_reviews.rating) as rating'),
                \DB::raw('count(m_reviews.review_id) as reviews_total')
            )
            ->groupBy('m_services.service_id');

            if($reqs->input('orderby') != NULL){
                $orderby = json_decode($reqs->input('orderby'));
                if(count($orderby) > 1){
                    if($orderby[1] == 'ASC' || $orderby[1] == 'DESC'){
                        $cdata->orderBy($orderby[0],$orderby[1]);
                    }
                }
            }

            if($paginate){
                $cdata = $cdata->paginate(10);
            }else{
                $cdata = $cdata->get();
            }

            if(count($cdata->toArray()) > 0){

            // INJECT SERVICE CATEGORIES
                $services = $cdata->toArray();
                $servicesData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($servicesData as $serviceKey => $service){

                    $service_id = $service['service_id'];
                    $cdata2 = M_service_categories::where([
                        ['m_service_categories.service_id','=',$service_id]
                    ])
                    ->leftJoin('m_categories', 'm_service_categories.category_id', 'm_categories.category_id')
                    ->select(
                        'm_categories.category_id',
                        'm_categories.name',
                        'm_categories.descriptions',
                    )
                    ->get();
                    $service_categories = $cdata2->toArray();

                    if($paginate){
                        $services['data'][$serviceKey]['categories'] = $service_categories;
                    }else{
                        $services[$serviceKey]['categories'] = $service_categories;
                    }
                }

            // INJECT GALLERY MATCHES
                $servicesData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($servicesData as $serviceKey => $service){
                    $gallery_id = $service['galleries'];
                    $cdata3 = M_gallery_matches::where([
                        ['m_gallery_matches.gallery_id','=',$gallery_id]
                    ])
                    ->leftJoin('m_images','m_gallery_matches.image_id','=','m_images.image_id')
                    ->select('m_images.*')
                    ->get();
                    
                    // CLEAN GALLERY MATCHES IF NULL
                    $images = $cdata3->toArray();
                    foreach($images as $imageKey => $image){
                        if($image['image_id'] == NULL){
                            unset($images[$imageKey]);
                        }
                    }
                    
                    if($paginate){
                        $services['data'][$serviceKey]['galleries_image_sets'] = $images;
                    }else{
                        $services[$serviceKey]['galleries_image_sets'] = $images;
                    }
                }

            $cdata = $services;
            }

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }

    
    // [GET] api/service/{id} <-- Get specific row
        function get(Request $reqs, $id, $direct = false){
            $response = array();

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){

                $cdata = M_default::where('m_services.service_id', '=', $id);
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_services.service_id', '=', $id];
                $cdata = M_default::where($filters);
            }

            $cdata->leftJoin('m_service_details','m_services.service_id','=','m_service_details.service_id')
            ->leftJoin('m_images','m_service_details.featured_img','=','m_images.image_id')
            ->select(

                //Services Details
                'm_service_details.*',

                //Services
                'm_services.name',
                'm_services.status',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            )
            ->groupBy('m_services.service_id')
            ->orderBy('m_services.service_id');

            $cdata = $cdata->first();

            if($cdata){

            // INJECT SERVICE CATEGORIES
                $service = $cdata;
                $service_id = $service['service_id'];
                $cdata2 = M_service_categories::where([
                    ['m_service_categories.service_id','=',$service_id]
                ])
                ->leftJoin('m_categories', 'm_service_categories.category_id', 'm_categories.category_id')
                ->select(
                    'm_categories.category_id',
                    'm_categories.name',
                    'm_categories.descriptions',
                )
                ->get();
                $service_categories = $cdata2->toArray();

                $service['categories'] = $service_categories;

            // INJECT SERVICE REVIEWS
                $service_reviews = M_service_reviews::where([
                    ['m_service_reviews.service_id','=',$service_id],
                    ['m_service_reviews.status','<>',0],
                    ['m_reviews.status','<>',0]
                ])
                ->leftJoin('m_reviews', 'm_service_reviews.review_id', 'm_reviews.review_id')
                ->leftJoin('m_users', 'm_reviews.user_id', 'm_users.user_id')
                ->leftJoin('m_user_details', 'm_users.user_id', 'm_user_details.user_id')
                ->leftJoin('m_images', 'm_user_details.propic', 'm_images.image_id')
                ->select(
                    'm_reviews.*',

                    'm_users.email',
                    'm_user_details.fullname',
                    'm_user_details.propic',
                    'm_images.url as propic_url',
                    'm_images.alt as propic_alt'
                )
                ->get();

                $service['service_reviews'] = $service_reviews->toArray();

            // INJECT GALLERY MATCHES
                $service = $cdata;
                $service['galleries_image_sets'] = array();
                if($service['galleries'] != NULL){
                    $gallery_id = $service['galleries'];
                    $cdata4 = M_gallery_matches::where([
                        ['m_gallery_matches.gallery_id','=',$gallery_id]
                    ])
                    ->leftJoin('m_images','m_gallery_matches.image_id','=','m_images.image_id')
                    ->select('m_images.*')
                    ->get();

                    // CLEAN GALLERY MATCHES IF NULL
                    $images = $cdata4->toArray();
                    foreach($images as $imageKey => $image){
                        if($image['image_id'] == NULL){
                            unset($images[$imageKey]);
                        }
                    }
                    $service['galleries_image_sets'] = $images;
                }
            
            // INJECT DOWNLOADABLE MATCHES
                $service = $cdata;
                $service['downloadable_file_sets'] = array();
                if($service['downloadable_id'] != NULL){
                    $downloadable_id = $service['downloadable_id'];
                    $cdata4 = M_downloadable_matches::where([
                        ['m_downloadable_matches.downloadable_id','=',$downloadable_id]
                    ])
                    ->leftJoin('m_files','m_downloadable_matches.file_id','=','m_files.file_id')
                    ->select('m_files.*')
                    ->get();

                    // CLEAN DOWNLOADABLE MATCHES IF NULL
                    $files = $cdata4->toArray();
                    foreach($files as $fileKey => $file){
                        if($file['file_id'] == NULL){
                            unset($files[$fileKey]);
                        }
                    }
                    $service['downloadable_file_sets'] = $files;
                }

                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return ($direct) ? $response : response($response);
        }



    // [GET] api/ajax/services <-- Get all lists
        function ajax_list(Request $reqs){  
            $response = array();
            $filters = json_decode($reqs->input('filters'));
            $cdata = M_default::where($filters)/* 
            ->leftJoin('m_categories','m_services.category_id','=','m_categories.category_id') */
            ->leftJoin('m_service_details','m_services.service_id','=','m_service_details.service_id')
            ->leftJoin('m_reviews','m_services.service_id','=','m_reviews.service_id')
            ->leftJoin('m_images','m_service_details.featured_img','=','m_images.image_id')
            ->select(
                //Services
                'm_services.name',

                //Services Details
                'm_service_details.*',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',

                //Ratings
                \DB::raw('AVG(m_reviews.rating) as rating'),
                \DB::raw('count(m_reviews.review_id) as reviews_total')
            )
            ->groupBy('m_services.service_id')
            ->orderBy('m_services.service_id')
            ->get();

            // INJECT SERVICE CATEGORIES
                $servicesData = $cdata->toArray();
                foreach($servicesData as $serviceKey => $service){

                    $service_id = $service['service_id'];
                    $cdata2 = M_service_categories::where([
                        ['m_service_categories.service_id','=',$service_id]
                    ])
                    ->leftJoin('m_categories', 'm_service_categories.category_id', 'm_categories.category_id')
                    ->select(
                        'm_categories.category_id',
                        'm_categories.name',
                        'm_categories.descriptions',
                    )
                    ->get();
                    $service_categories = $cdata2->toArray();
                    $services[$serviceKey]['categories'] = $service_categories;
                }

            // INJECT GALLERY MATCHES
                $servicesData = $cdata->toArray();
                foreach($servicesData as $serviceKey => $service){
                    $gallery_id = $service['galleries'];
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
                    $services[$serviceKey]['galleries_image_sets'] = $images;
                }

            $cdata = $services;

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

    // [PUT] api/service/{id} <-- Update specific row
        function update(Request $reqs, $id){
            $response = array();

            if($this->validation($this->service_chains, $reqs)){

                $services_inputs = (object) $reqs->input($this->service_chains[0]);
                $service_details_inputs = (object) $reqs->input($this->service_chains[1]);
                $service_categories_inputs = (object) $reqs->input($this->service_chains[2]);

                if($this->validation(array('updated_by'), $services_inputs)){

                    // [1] UPDATE service BASE
                        $service = M_default::find($id);
                        foreach($services_inputs as $colname => $value){
                            $service->{$colname} = $value;
                        }
                        $service->updated_at = date("Y-m-d H:i:s");
                        $service->save();

                    // [2] UPDATE SERVICE DETAILS
                        $service_details = new C_service_details;
                        $service_detail_id = $service_details->get($reqs, $id)['data']['service_detail_id'];
                        $service_details_response = $service_details->update($service_details_inputs, $service->service_id);
                        if($service_details_response['status']){

                            // [3] UPDATE SERVICE CATEGORIES
                                $service_categories = new C_service_categories;
                                $service_categories_response = $service_categories->update($service_categories_inputs, $service->service_id);
                                if($service_categories_response['status']){
                                    $response = $this->get(new Request, $service->service_id, true);
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $service_categories_response['msg'];
                                    $response["debug"] = $service_categories_response['debug'];
                                }

                        }else{
                            $response["status"] = False;
                            $response["msg"] = $service_details_response['msg'];
                            $response["debug"] = $service_details_response['debug'];
                        }

                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing updated_by";
                }

            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parent parameters";
            }

            return response()->json($response);
        }

    // [PUT] api/service/restore/{id} <-- Restore deleted specific row
        function restore($id){
            $response = array();

            // [1] RESTORE SERVICE        
                if(M_default::withTrashed()->find($id)->restore()){

                    $service = M_default::find($id);
                    $service_udpate = $service->update(['deleted_by'=>NULL]);

                    if($service){

                        // [2] RESTORE SERVICE DETAILS
                            $service_details = new C_service_details;
                            $service_details_response = $service_details->restore($service->service_id);
                            if($service_details_response['status']){
                                
                                // [3] RESTORE SERVICE CATEGORIES
                                    $service_categories = new C_service_categories;
                                    $service_categories_response = $service_categories->restore($service->service_id);
                                    if($service_categories_response['status']){
                                        $response = $this->get(new Request, $service->service_id, true);
                                    }else{
                                        $response["status"] = False;
                                        $response["msg"] = $service_categories_response['msg'];
                                        $response["debug"] = $service_categories_response['debug'];
                                    }

                            }else{
                                $response["status"] = False;
                                $response["msg"] = $service_details_response['msg'];
                                $response["debug"] = $service_details_response['debug'];
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

    // [DELETE] api/service/{id} <-- SoftDelete specific row
        function delete(Request $reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){
                $service = M_default::find($id);
                $service->deleted_by = $reqs->input('deleted_by');
                $service->save();

                // [1] DELETE SERVICE
                if($service->delete()){

                    // [2] DELETE SERVICE DETAILS
                        $service_details = new C_service_details;
                        $service_detail_id = $service_details->get($reqs, $id)['data']['service_detail_id'];
                        $service_details_response = $service_details->delete($reqs, $id);

                        if($service_details_response['status']){
                            // [3] DELETE SERVICE CATEGORIES
                                $service_detail_categories = new C_service_categories;
                                $service_detail_categories_reponse = $service_detail_categories->delete($reqs, $id);
                                if($service_detail_categories_reponse['status']){
                                    // [4] DELETE SERVICE VARIANCES
                                    $service_variances = new C_service_variances;
                                    $service_variances_response = $service_variances->delete($reqs, $service_detail_id);
                                    if($service_variances_response['status']){
                                        $response['status'] = True;
                                        $response["msg"] = "Sucessfully Deleted";
                                    }else{
                                        $response["status"] = False;
                                        $response["msg"] = $service_variances_response['msg'];
                                        $response["debug"] = $service_variances_response['debug'];
                                    }
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $service_detail_categories_reponse['msg'];
                                    $response["debug"] = $service_detail_categories_reponse['debug'];
                                }
                        }else{
                            $response["status"] = False;
                            $response["msg"] = $service_details_response['msg'];
                            $response["debug"] = $service_details_response['debug'];
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

    // [DELETE] api/service/delete/{id} <-- Permanent delete specific row
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

                // RESTORE FIRST BEFORE DELETE
                $this->restore($id);

                // [1] DELETE SERVICES
                $service = new M_default();
                $service_response = $service->harddelete($id);
                if($service_response["status"]){

                    // [2] DELETE SERVICE DETAILS
                        $service_details = new C_service_details;
                        $service_detail_id = $service_details->get($req, $id)['data']['service_detail_id'];
                        $service_details_response = $service_details->harddelete($req, $id, TRUE);
                        if($service_details_response['status']){
                            
                            // [3] DELETE SERVICE CATEGORIES
                                $service_categories = new C_service_categories;
                                $service_categories_response = $service_categories->harddelete($req, $id, TRUE);
                                if($service_categories_response['status']){

                                    // [4] DELETE SERVICE VARIANCES
                                        $service_variances = new C_service_variances;
                                        $service_variances_response = $service_variances->harddelete($req, $service_detail_id, TRUE);
                                        if($service_variances_response['status']){
                                            $response["status"] = TRUE;
                                            $response["msg"] = $service_variances_response["msg"];
                                        }else{
                                            $response["status"] = False;
                                            $response["msg"] = $service_variances_response["msg"];
                                            $response["debug"] = $service_variances_response["debug"];
                                        }
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $service_categories_response["msg"];
                                    $response["debug"] = $service_categories_response["debug"];
                                }
                        }else{
                            $response["status"] = False;
                            $response["msg"] = $service_details_response["msg"];
                            $response["debug"] = $service_details_response["debug"];
                        }

                }else{
                    $response["status"] = False;
                    $response["msg"] = $service_response["msg"];
                    $response["debug"] = $service_response["debug"];
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
