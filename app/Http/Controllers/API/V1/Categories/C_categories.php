<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Categories REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Categories;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Categories\M_categories as M_default;


class C_categories extends Controller{

    private $module = "category";
    private $create_required = ['name','status','created_by'];

/************* CREATE  *************/

    // [POST] api/category <-- Create new row
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

    // [GET] api/categories <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;;
            $cdata = M_default::where('m_categories.status','<>','0');

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
            
            $cdata->leftJoin('m_product_categories','m_categories.category_id','m_product_categories.category_id')
            ->leftJoin('m_images','m_categories.image_id','m_images.image_id')
            ->selectRaw('
                m_categories.*,

                m_images.url as category_image_url,
                m_images.alt as category_image_alt,

                count(m_product_categories.category_id) as product_totals
            ')
            ->groupBy('m_categories.category_id')
            ->orderBy('m_categories.category_id','DESC');
            $cdata = $cdata->get();

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }


    // [GET] api/category/{id} <-- Get specific row
        function get(Request $reqs, $id){
            $response = array();

            $cdata = M_default::where('m_categories.category_id', '=', $id);

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

            $cdata->leftJoin('m_product_categories','m_categories.category_id','m_product_categories.category_id')
            ->leftJoin('m_images','m_categories.image_id','m_images.image_id')
            ->selectRaw(
                'm_categories.*,

                m_images.url as category_image_url,
                m_images.alt as category_image_alt,

                count(m_product_categories.category_id) as product_totals

            ')
            ->groupBy('m_categories.category_id');

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

    // [PUT] api/category/{id} <-- Update specific row
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

    // [PUT] api/category/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/category/{id} <-- SoftDelete specific row
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

    // [DELETE] api/category/delete/{id} <-- Permanent delete specific row
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
