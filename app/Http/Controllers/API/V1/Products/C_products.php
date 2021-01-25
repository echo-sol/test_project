<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Products REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Products;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

// MODELS
use App\Http\Models\API\V1\Products\M_products as M_default;
use App\Http\Models\API\V1\Products\M_product_variances as M_product_variances;
use App\Http\Models\API\V1\Products\M_product_details as M_product_details;
use App\Http\Models\API\V1\Reviews\M_reviews as M_reviews;
use App\Http\Models\API\V1\Reviews\M_product_reviews as M_product_reviews;
use App\Http\Models\API\V1\Galleries\M_gallery_matches as M_gallery_matches;
use App\Http\Models\API\V1\Products\M_product_categories as M_product_categories;
use App\Http\Models\API\V1\Downloadables\M_downloadable_matches as M_downloadable_matches;


class C_products extends Controller{

    private $module = "product";
    private $create_required = ['name','status','created_by'];
    private $product_chains = ['m_products','m_product_details','m_product_categories','m_product_variances'];


/************* CREATE  *************/

    // [POST] api/product <-- Create new row
    function create(Request $reqs){
        $response = array();
        
        if($this->validation($this->product_chains, $reqs)){

            $products_inputs = (object) $reqs->input($this->product_chains[0]);
            $product_details_inputs = (object) $reqs->input($this->product_chains[1]);
            $product_categories_inputs = (object) $reqs->input($this->product_chains[2]);
            $product_variances_inputs = (object) $reqs->input($this->product_chains[3]);

            if($this->validation($this->create_required, $products_inputs)){

                // [1] CREATE PRODUCT BASE
                    $product = new M_default;
                    foreach($products_inputs as $colname => $value){
                        $product->{$colname} = $value;
                    }
        
                    $product->created_at = date("Y-m-d H:i:s");
                    $product->save();

                // [2] CREATE PRODUCT DETAILS
                    $product_details_inputs->product_id = $product->product_id;
                    $product_details = new C_product_details;
                    $product_details_response = $product_details->create($product_details_inputs);
                    if($product_details_response['status']){

                        // [3] CREATE PRODUCT CATEGORIES
                            $product_categories_inputs->product_id = $product->product_id;
                            $product_categories = new C_product_categories;
                            $product_categories_response = $product_categories->create($product_categories_inputs);
                            if($product_categories_response['status']){

                                // [4] CREATE PRODUCT VARIANCES
                                    $product_variances_inputs->product_detail_id = $product_details_response['data']['product_detail_id'];
                                    $product_variances = new C_product_variances;
                                    $product_variances_response = $product_variances->create($product_variances_inputs);
                                    if($product_variances_response['status']){
                                        $response = $this->get(new Request, $product->product_id, true);
                                    }else{
                                        // CLEAR PRODUCT & PRODUCT DETAILS & PRODUCT CATEGORIES
                                        $product->harddelete($product->product_id);
                                        $product_details->harddelete($reqs, $product->product_id, TRUE);
                                        $product_categories->harddelete($reqs, $product->product_id, TRUE);

                                        $response["status"] = False;
                                        $response["msg"] = $product_variances_response['msg'];
                                        $response["debug"] = $product_variances_response['debug'];
                                    }
                            }else{
                                // CLEAR PRODUCT & PRODUCT DETAILS
                                $product->harddelete($product->product_id);
                                $product_details->harddelete($reqs, $product->product_id, TRUE);
                                $response["status"] = False;
                                $response["msg"] = $product_categories_response['msg'];
                                $response["debug"] = $product_categories_response['debug'];
                            }

                    }else{
                        // CLEAR PRODUCT
                        $product->harddelete($product->product_id);
                        $response["status"] = False;
                        $response["msg"] = $product_details_response['msg'];
                        $response["debug"] = $product_details_response['debug'];
                    }
    
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing product parameters";
            }

        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing parent parameters";
        }

        
        return response()->json($response);
        
    
    }

/************* GET/READ  *************/

    // [GET] api/products <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;
            $limit = ($reqs->input('limit') != NULL) ? $reqs->input('limit') : false;

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $withorfilters = ($reqs->input('withorfilters') != NULL) ? json_decode($reqs->input('withorfilters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){

                $cdata = M_default::where('m_products.status','<>','-99');
                $cdata->where(function ($q) use ($withorfilters, $orfilters){
                    $q->where($withorfilters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });

                if(count($withorfilters) > 0){
                    $cdata->where($filters);
                }

            }else{
                $filters[] = ['m_products.status','<>','-99'];
                $cdata = M_default::where($filters);
            }

            
            $cdata->leftJoin('m_product_details','m_products.product_id','=','m_product_details.product_id')
            ->leftJoin('m_product_reviews','m_products.product_id','=','m_product_reviews.product_id')
            ->leftJoin('m_product_categories','m_products.product_id','=','m_product_categories.product_id')
            ->leftJoin('m_categories','m_product_categories.category_id','=','m_categories.category_id')
            ->leftJoin('m_reviews','m_product_reviews.review_id','=','m_reviews.review_id')
            ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
            ->select(
                //Products
                'm_products.name',

                //Product Details
                'm_product_details.*',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',

                //Ratings
                \DB::raw('AVG(m_reviews.rating) as rating'),
                \DB::raw('count(m_reviews.review_id) as reviews_total')
            )
            ->groupBy('m_products.product_id');

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
                if($limit){
                    $cdata = $cdata->skip(0)->take($limit)->get();
                }else{
                    $cdata = $cdata->get();
                }
            }


            if(count($cdata->toArray()) > 0){
            // INJECT PRODUCT CATEGORIES
                $products = $cdata->toArray();
                $productsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($productsData as $productKey => $product){

                    $product_id = $product['product_id'];
                    $cdata2 = M_product_categories::where([
                        ['m_product_categories.product_id','=',$product_id]
                    ])
                    ->leftJoin('m_categories', 'm_product_categories.category_id', 'm_categories.category_id')
                    ->select(
                        'm_categories.category_id',
                        'm_categories.name',
                        'm_categories.descriptions',
                    )
                    ->get();
                    $product_categories = $cdata2->toArray();

                    if($paginate){
                        $products['data'][$productKey]['categories'] = $product_categories;
                    }else{
                        $products[$productKey]['categories'] = $product_categories;
                    }
                }

            // INJECT GALLERY MATCHES
                $productsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($productsData as $productKey => $product){
                    $gallery_id = $product['galleries'];
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
                        $products['data'][$productKey]['galleries_image_sets'] = $images;
                    }else{
                        $products[$productKey]['galleries_image_sets'] = $images;
                    }
                }

            $cdata = $products;
            }

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }

    // [GET] api/product/brands <-- Get all brands
        function getBrands(){
            $response = array();

            $cdata = M_default::distinct('brands')->select('brands')->get();

            if($cdata){
                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return response($response);
        }

    // [GET] api/product/{id} <-- Get specific row
        function get(Request $reqs, $id, $direct = false){
            $response = array();

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){

                $cdata = M_default::where('m_products.product_id', '=', $id);
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_products.product_id', '=', $id];
                $cdata = M_default::where($filters);
            }

            $cdata->leftJoin('m_product_details','m_products.product_id','=','m_product_details.product_id')
            ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
            ->select(
                //Products
                'm_products.name',

                //Product Details
                'm_product_details.*',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            )
            ->groupBy('m_products.product_id')
            ->orderBy('m_products.product_id');

            $cdata = $cdata->first();

            if($cdata){

            // INJECT PRODUCT CATEGORIES
                $product = $cdata;
                $product_id = $product['product_id'];
                $cdata2 = M_product_categories::where([
                    ['m_product_categories.product_id','=',$product_id]
                ])
                ->leftJoin('m_categories', 'm_product_categories.category_id', 'm_categories.category_id')
                ->select(
                    'm_categories.category_id',
                    'm_categories.name',
                    'm_categories.descriptions',
                )
                ->get();
                $product_categories = $cdata2->toArray();

                $product['categories'] = $product_categories;

            // INJECT PRODUCT REVIEWS
                $product_reviews = M_product_reviews::where([
                    ['m_product_reviews.product_id','=',$product_id],
                    ['m_product_reviews.status','<>',-99],
                    ['m_reviews.status','<>',-99]
                ])
                ->leftJoin('m_reviews', 'm_product_reviews.review_id', 'm_reviews.review_id')
                ->leftJoin('m_users', 'm_reviews.created_by', 'm_users.user_id')
                ->leftJoin('m_user_details', 'm_users.user_id', 'm_user_details.user_id')
                ->leftJoin('m_images', 'm_user_details.propic', 'm_images.image_id')
                ->select(
                    'm_reviews.*',

                    'm_product_reviews.product_review_id',

                    'm_users.email',
                    'm_user_details.fullname',
                    'm_user_details.propic',
                    'm_images.url as propic_url',
                    'm_images.alt as propic_alt'
                )
                ->get();

                $product['product_reviews'] = $product_reviews->toArray();
            // INJECT PRODUCT VARIANCES
                $cdata3 = M_product_variances::where([
                    ['m_product_variances.product_detail_id','=',$product['product_detail_id']]
                ])
                ->leftJoin('m_images','m_product_variances.featured_img','m_images.image_id')
                ->select(
                    'm_product_variances.*',
                    'm_images.url as featured_img_url'
                )
                ->get();

                // INJECT PRODUCT VARIANCES OPTIONS
                    $variances = $cdata3->toArray();
                    $variance_options = array();
                    foreach($variances as $varianceKey => $variance){
                        foreach($variance as $var_optionKey => $var_option){
                            $varopt_array= explode(":",$var_optionKey);
                            if(isset($varopt_array[0])){
                                if($varopt_array[0] == "variance"){
                                    if(!isset($variance_options[$varopt_array[1]])){
                                        $variance_options[$varopt_array[1]] = array();
                                    }
                                    if(!in_array($var_option, $variance_options[$varopt_array[1]])){
                                        //$variance_options[$varopt_array[1]][$var_option] = true;
                                        array_push($variance_options[$varopt_array[1]], $var_option);
                                    }
                                }
                            }
                        }
                        /* $variances[$varianceKey]['variance_options'] = json_decode($variance_options); */
                    }

                $product['variances_options'] = $variance_options;
                $product['variances'] = $variances;


            // INJECT GALLERY MATCHES
                $product = $cdata;
                $product['galleries_image_sets'] = array();
                if($product['galleries'] != NULL){
                    $gallery_id = $product['galleries'];
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
                    $product['galleries_image_sets'] = $images;
                }
            
            // INJECT DOWNLOADABLE MATCHES
                $product = $cdata;
                $product['downloadable_file_sets'] = array();
                if($product['downloadable_id'] != NULL){
                    $downloadable_id = $product['downloadable_id'];
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
                    $product['downloadable_file_sets'] = $files;
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



    // [GET] api/ajax/products <-- Get all lists
        function ajax_list(Request $reqs){  
            $response = array();
            $filters = json_decode($reqs->input('filters'));
            $cdata = M_default::where($filters)/* 
            ->leftJoin('m_categories','m_products.category_id','=','m_categories.category_id') */
            ->leftJoin('m_product_details','m_products.product_id','=','m_product_details.product_id')
            ->leftJoin('m_product_variances','m_product_details.product_detail_id','=','m_product_variances.product_detail_id')
            ->leftJoin('m_product_reviews','m_products.product_id','=','m_product_reviews.product_id')
            ->leftJoin('m_reviews','m_product_reviews.review_id','=','m_reviews.review_id')
            ->leftJoin('m_images','m_product_details.featured_img','=','m_images.image_id')
            ->select(
                //Products
                'm_products.name',

                //Product Details
                'm_product_details.*',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',

                //Ratings
                \DB::raw('AVG(m_reviews.rating) as rating'),
                \DB::raw('count(m_reviews.review_id) as reviews_total')
            )
            ->groupBy('m_products.product_id')
            ->orderBy('m_products.product_id')
            ->get();


            // INJECT PRODUCT VARIANCES
                $products = $cdata->toArray();
                
                $cdata2 = new M_product_variances;
                foreach($products as $product){
                    $cdata2->orWhere("m_product_variances.product_detail_id", "=", $product['product_detail_id']);
                }
                $cdata2 = $cdata2->get();

            // INJECT PRODUCT VARIANCES OPTIONS
                $variances = $cdata2->toArray();
                foreach($products as $productKey => $product){
                    $variance_options = array();
                    $variance_collections = array();
                    $products[$productKey]['variances'] = array();
                    foreach($variances as $varianceKey => $variance){
                        if($product['product_detail_id'] == $variance['product_detail_id']){
                            array_push($products[$productKey]['variances'], $variance);
                        }
                        // INJECT PRODUCT VARIANCES OPTIONS
                            foreach($variance as $var_optionKey => $var_option){
                                $varopt_array= explode(":",$var_optionKey);
                                if(isset($varopt_array[0])){
                                    if($varopt_array[0] == "variance"){

                                        // DEFINE VARIANCE OPTIONS KEY AS ARRAY
                                            if(!isset($variance_options[$varopt_array[1]])){
                                                $variance_options[$varopt_array[1]] = array();
                                            }

                                        // ASSING VARIANCE VALUE IF UNAVAILABLE
                                            if(!in_array($var_option, $variance_options[$varopt_array[1]])){
                                                array_push($variance_options[$varopt_array[1]], $var_option);
                                            }

                                        // INJECT PRODUCT VARIANCE BADGE
                                            if($product['product_detail_id'] == $variance['product_detail_id'] && $var_option != NULL){
                                                if(!isset($variance_collections[$varopt_array[1]])){
                                                    $variance_collections[$varopt_array[1]] = array();
                                                }
                                                if(!in_array($var_option, $variance_collections[$varopt_array[1]])){
                                                    $variance_collections[$varopt_array[1]][] = $var_option;
                                                }
                                            }
                                    }
                                }
                            }
                            $products[$productKey]['variance_collections'] = $variance_collections;

                    }
                    $products[$productKey]['variances_options'] = $variance_options;
                 /* $variances[$varianceKey]['variance_options'] = json_decode($variance_options); */
                }

            // INJECT PRODUCT CATEGORIES
                $productsData = $cdata->toArray();
                foreach($productsData as $productKey => $product){

                    $product_id = $product['product_id'];
                    $cdata2 = M_product_categories::where([
                        ['m_product_categories.product_id','=',$product_id]
                    ])
                    ->leftJoin('m_categories', 'm_product_categories.category_id', 'm_categories.category_id')
                    ->select(
                        'm_categories.category_id',
                        'm_categories.name',
                        'm_categories.descriptions',
                    )
                    ->get();
                    $product_categories = $cdata2->toArray();
                    $products[$productKey]['categories'] = $product_categories;
                }

            // INJECT GALLERY MATCHES
                $productsData = $cdata->toArray();
                foreach($productsData as $productKey => $product){
                    $gallery_id = $product['galleries'];
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
                    $products[$productKey]['galleries_image_sets'] = $images;
                }

            $cdata = $products;

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

    // [PUT] api/product/{id} <-- Update specific row
        function update(Request $reqs, $id){
            $response = array();

            if($this->validation($this->product_chains, $reqs)){

                $products_inputs = (object) $reqs->input($this->product_chains[0]);
                $product_details_inputs = (object) $reqs->input($this->product_chains[1]);
                $product_categories_inputs = (object) $reqs->input($this->product_chains[2]);
                $product_variances_inputs = (object) $reqs->input($this->product_chains[3]);

                if($this->validation(array('updated_by'), $products_inputs)){

                    // [1] UPDATE PRODUCT BASE
                        $product = M_default::find($id);
                        foreach($products_inputs as $colname => $value){
                            $product->{$colname} = $value;
                        }
                        $product->updated_at = date("Y-m-d H:i:s");
                        $product->save();

                    // [2] UPDATE PRODUCT DETAILS
                        $product_details = new C_product_details;
                        $product_detail_id = $product_details->get($reqs, $id)['data']['product_detail_id'];
                        $product_details_response = $product_details->update($product_details_inputs, $product->product_id);
                        if($product_details_response['status']){

                            // [3] UPDATE PRODUCT CATEGORIES
                                $product_categories = new C_product_categories;
                                $product_categories_response = $product_categories->update($product_categories_inputs, $product->product_id);
                                if($product_categories_response['status']){
                                    
                                    // [4] UPDATE PRODUCT VARIANCES
                                        $product_variances = new C_product_variances;
                                        $product_variances_response = $product_variances->update($product_variances_inputs, $product_detail_id);
                                        if($product_variances_response['status']){
                                            $response = $this->get(new Request, $product->product_id, true);
                                        }else{
                                            $response["status"] = False;
                                            $response["msg"] = $product_variances_response['msg'];
                                            $response["debug"] = $product_variances_response['debug'];
                                        }
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $product_categories_response['msg'];
                                    $response["debug"] = $product_categories_response['debug'];
                                }

                        }else{
                            $response["status"] = False;
                            $response["msg"] = $product_details_response['msg'];
                            $response["debug"] = $product_details_response['debug'];
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

    // [PUT] api/product/restore/{id} <-- Restore deleted specific row
        function restore($id){
            $response = array();

            // [1] RESTORE PRODUCT        
                if(M_default::withTrashed()->find($id)->restore()){

                    $product = M_default::find($id);
                    $product_udpate = $product->update(['deleted_by'=>NULL]);

                    if($product){

                        // [2] RESTORE PRODUCT DETAILS
                            $product_details = new C_product_details;
                            $product_details_response = $product_details->restore($product->product_id);
                            if($product_details_response['status']){
                                
                                // [3] RESTORE PRODUCT CATEGORIES
                                    $product_categories = new C_product_categories;
                                    $product_categories_response = $product_categories->restore($product->product_id);
                                    if($product_categories_response['status']){
                                        $response = $this->get(new Request, $product->product_id, true);
                                    }else{
                                        $response["status"] = False;
                                        $response["msg"] = $product_categories_response['msg'];
                                        $response["debug"] = $product_categories_response['debug'];
                                    }

                            }else{
                                $response["status"] = False;
                                $response["msg"] = $product_details_response['msg'];
                                $response["debug"] = $product_details_response['debug'];
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

    // [DELETE] api/product/{id} <-- SoftDelete specific row
        function delete(Request $reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){
                $product = M_default::find($id);
                $product->deleted_by = $reqs->input('deleted_by');
                $product->save();

                // [1] DELETE PRODUCTS
                if($product->delete()){

                    // [2] DELETE PRODUCT DETAILS
                        $product_details = new C_product_details;
                        $product_detail_id = $product_details->get($reqs, $id)['data']['product_detail_id'];
                        $product_details_response = $product_details->delete($reqs, $id);

                        if($product_details_response['status']){
                            // [3] DELETE PRODUCT CATEGORIES
                                $product_detail_categories = new C_product_categories;
                                $product_detail_categories_reponse = $product_detail_categories->delete($reqs, $id);
                                if($product_detail_categories_reponse['status']){
                                    // [4] DELETE PRODUCT VARIANCES
                                    $product_variances = new C_product_variances;
                                    $product_variances_response = $product_variances->delete($reqs, $product_detail_id);
                                    if($product_variances_response['status']){
                                        $response['status'] = True;
                                        $response["msg"] = "Sucessfully Deleted";
                                    }else{
                                        $response["status"] = False;
                                        $response["msg"] = $product_variances_response['msg'];
                                        $response["debug"] = $product_variances_response['debug'];
                                    }
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $product_detail_categories_reponse['msg'];
                                    $response["debug"] = $product_detail_categories_reponse['debug'];
                                }
                        }else{
                            $response["status"] = False;
                            $response["msg"] = $product_details_response['msg'];
                            $response["debug"] = $product_details_response['debug'];
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

    // [DELETE] api/product/delete/{id} <-- Permanent delete specific row
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

                // [1] DELETE PRODUCTS
                $product = new M_default();
                $product_response = $product->harddelete($id);
                if($product_response["status"]){

                    // [2] DELETE PRODUCT DETAILS
                        $product_details = new C_product_details;
                        $product_detail_id = $product_details->get($req, $id)['data']['product_detail_id'];
                        $product_details_response = $product_details->harddelete($req, $id, TRUE);
                        if($product_details_response['status']){
                            
                            // [3] DELETE PRODUCT CATEGORIES
                                $product_categories = new C_product_categories;
                                $product_categories_response = $product_categories->harddelete($req, $id, TRUE);
                                if($product_categories_response['status']){

                                    // [4] DELETE PRODUCT VARIANCES
                                        $product_variances = new C_product_variances;
                                        $product_variances_response = $product_variances->harddelete($req, $product_detail_id, TRUE);
                                        if($product_variances_response['status']){
                                            $response["status"] = TRUE;
                                            $response["msg"] = $product_variances_response["msg"];
                                        }else{
                                            $response["status"] = False;
                                            $response["msg"] = $product_variances_response["msg"];
                                            $response["debug"] = $product_variances_response["debug"];
                                        }
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $product_categories_response["msg"];
                                    $response["debug"] = $product_categories_response["debug"];
                                }
                        }else{
                            $response["status"] = False;
                            $response["msg"] = $product_details_response["msg"];
                            $response["debug"] = $product_details_response["debug"];
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
