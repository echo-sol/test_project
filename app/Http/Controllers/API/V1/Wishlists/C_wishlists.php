<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Wishlists REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Wishlists;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Wishlists\M_wishlists as M_default;
use App\Http\Models\API\V1\Products\M_products as M_products;
use App\Http\Models\API\V1\Galleries\M_gallery_matches as M_gallery_matches;


class C_wishlists extends Controller{

    private $module = "wishlist";
    private $create_required = ['customer_id','product_detail_id','status','created_by'];

/************* CREATE  *************/

    // [POST] api/wishlist <-- Create new row
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

    // [GET] api/wishlists <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata = M_default::where('m_wishlists.status','<>','0');
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_wishlists.status','<>','0'];
                $cdata = M_default::where($filters);
            }
            $cdata->leftJoin('m_product_details','m_wishlists.product_detail_id','=','m_product_details.product_detail_id')
            ->leftJoin('m_products','m_product_details.product_id','=','m_products.product_id')
            ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
            ->leftJoin('m_customers','m_wishlists.customer_id','=','m_customers.customer_id')
            ->leftJoin('m_users','m_customers.user_id','=','m_users.user_id')
            ->select(

                //Products
                'm_products.name',

                //Product Details
                'm_product_details.*',

                //Images
                'm_images.url as featured_img_url',

                //Wishlists
                'm_wishlists.wishlist_id',
                'm_wishlists.created_at',
                'm_wishlists.created_by',
                'm_wishlists.customer_id',
            );

            

            if($paginate){
                $cdata = $cdata->paginate(10);
            }else{
                $cdata = $cdata->get();
            }
            
            if(count($cdata->toArray()) > 0){

                // INJECT GALLERY MATCHES
                $wishlists = $cdata->toArray();
                $wishlistsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                
                foreach($wishlistsData as $wishlistKey => $wishlist){
                    $gallery_id = $wishlist['galleries'];
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
                    if($paginate){
                        $wishlists['data'][$wishlistKey]['galleries_image_sets'] = $images;
                    }else{
                        $wishlists[$wishlistKey]['galleries_image_sets'] = $images;
                    }
                }
                $cdata = $wishlists;

            }
            

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }



    // [GET] api/wishlist/{id} <-- Get specific row
        function get(Request $reqs, $id){
            $response = array();

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){

                $cdata = M_default::where('m_wishlists.wishlist_id', '=', $id);
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_wishlists.wishlist_id', '=', $id];
                $cdata = M_default::where($filters);
            }

            $cdata->leftJoin('m_product_details','m_wishlists.product_detail_id','=','m_product_details.product_detail_id')
            ->leftJoin('m_products','m_product_details.product_id','=','m_products.product_id')
            ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
            ->select(
                //Wishlists
                'm_wishlists.wishlist_id',
                'm_wishlists.created_at as wishlist_created_at',
                'm_wishlists.created_by as wishlist_created_by',
                'm_wishlists.customer_id',

                //Products
                'm_products.name',

                //Product Details
                'm_product_details.*',

                //Images
                'm_images.url as featured_img_url'
            );

            $cdata = $cdata->first();

            if($cdata){
            // INJECT GALLERY MATCHES
                $wishlist = $cdata;
                if($wishlist['galleries'] != NULL){
                    $gallery_id = $wishlist['galleries'];
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
                    $wishlist['galleries_image_sets'] = $images;
                }

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

    // [PUT] api/wishlist/{id} <-- Update specific row
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

    // [PUT] api/wishlist/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/wishlist/{id} <-- SoftDelete specific row
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

    // [DELETE] api/wishlist/delete/{id} <-- Permanent delete specific row
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
