<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Receipts REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Blogs;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Blogs\M_blogs as M_default;
use App\Http\Models\API\V1\Blogs\M_blog_categories as M_blog_categories;


class C_blogs extends Controller{

    private $module = "blog";
    private $create_required = ['title','contents','status','created_by'];
    private $blog_chains = ['m_blogs','m_blog_categories'];

/************* CREATE  *************/

    // [POST] api/blog <-- Create new row
    function create(Request $reqs){

        $response = array();

        if($this->validation($this->blog_chains, $reqs)){

            $blogs_inputs = (object) $reqs->input($this->blog_chains[0]);
            $blog_categories_inputs = (object) $reqs->input($this->blog_chains[1]);

            if($this->validation($this->create_required, $blogs_inputs)){

                // [1] CREATE BLOG BASE
                $blog = new M_default;
                foreach($blogs_inputs as $colname => $value){
                    $blog->{$colname} = $value;
                }
    
                $blog->created_at = date("Y-m-d H:i:s");
                $blog->save();

                // [2] CREATE BLOG CATEGORIES
                    $blog_categories_inputs->blog_id = $blog->blog_id;
                    $blog_categories = new C_blog_categories;
                    $blog_categories_response = $blog_categories->create($blog_categories_inputs);
                    if($blog_categories_response['status']){
                        $response = $this->get(new Request, $blog->blog_id, true);
                    }else{
                        $response["msg"] = $blog_categories_response['msg'];
                        $response["debug"] = $blog_categories_response['debug'];
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

    // [GET] api/blogs <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;
            $cdata = M_default::where('m_blogs.status','<>','0');

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

            $cdata->leftJoin('m_images','m_blogs.featured_img','=','m_images.image_id')
            ->select(
                //Blogs
                'm_blogs.*',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            );

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
                $blogs = $cdata->toArray();
                $blogsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($blogsData as $blogKey => $blog){

                // INJECT BLOG CATEGORIES
                    $blog_id = $blog['blog_id'];
                    $cdata2 = M_blog_categories::where([
                        ['m_blog_categories.blog_id','=',$blog_id]
                    ])
                    ->leftJoin('m_categories', 'm_blog_categories.category_id', 'm_categories.category_id')
                    ->select(
                        'm_categories.category_id',
                        'm_categories.name',
                        'm_categories.descriptions',
                    )
                    ->get();
                    $blog_categories = $cdata2->toArray();
                    
                    if($paginate){
                        $blogs['data'][$blogKey]['categories'] = $blog_categories;
                    }else{
                        $blogs[$blogKey]['categories'] = $blog_categories;
                    }
                }

                $cdata = $blogs;
            }

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }



    // [GET] api/blog/{id} <-- Get specific row
        function get(Request $reqs, $id, $direct=false){
            $response = array();

            $cdata = M_default::where('m_blogs.blog_id', '=', $id);

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

            $cdata->leftJoin('m_images','m_blogs.featured_img','=','m_images.image_id')
            ->select(
                //Blogs
                'm_blogs.*',

                //Images
                'm_images.url as featured_img_url',
                'm_images.alt as featured_img_alt',
            );

            $cdata = $cdata->first();

            if($cdata){

                // INJECT BLOG CATEGORIES
                    $blog = $cdata;
                    $blog_id = $blog['blog_id'];
                    $cdata2 = M_blog_categories::where([
                        ['m_blog_categories.blog_id','=',$blog_id]
                    ])
                    ->leftJoin('m_categories', 'm_blog_categories.category_id', 'm_categories.category_id')
                    ->select(
                        'm_categories.category_id',
                        'm_categories.name',
                        'm_categories.descriptions',
                    )
                    ->get();
                    $blog_categories = $cdata2->toArray();

                    $cdata['categories'] = $blog_categories;


                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return ($direct) ? $response : response()->json($response);
        }




/************* UPDATE  *************/

    // [PUT] api/blog/{id} <-- Update specific row
    function update(Request $reqs, $id){
        $response = array();

        if($this->validation($this->blog_chains, $reqs)){

            $blogs_inputs = (object) $reqs->input($this->blog_chains[0]);
            $blog_categories_inputs = (object) $reqs->input($this->blog_chains[1]);

            if($this->validation(array('updated_by'), $blogs_inputs)){
                $blog = M_default::find($id);

                foreach($blogs_inputs as $colname => $value){
                    $blog->{$colname} = $value;
                }
                
                $blog->updated_at = date("Y-m-d H:i:s");
                $blog->save();

                // [2] UPDATE BLOG CATEGORIES
                    $blog_categories = new C_blog_categories;
                    $blog_categories_response = $blog_categories->update($blog_categories_inputs, $blog->blog_id);
                    if($blog_categories_response['status']){
                        $response = $this->get(new Request, $blog->blog_id, true);
                    }else{
                        $response["msg"] = $blog_categories_response['msg'];
                        $response["debug"] = $blog_categories_response['debug'];
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

    // [PUT] api/blog/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/receipt/{id} <-- SoftDelete specific row
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

    // [DELETE] api/receipt/delete/{id} <-- Permanent delete specific row
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
