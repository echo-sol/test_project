<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Shipping_Methods REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Shippings;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Shippings\M_shipping_methods as M_default;
use App\Http\Models\API\V1\Shippings\M_shipping_method_coverages as M_shipping_method_coverages;


class C_shipping_methods extends Controller{

    private $module = "shipping_method";
    private $create_required = ['method_name','description','status','created_by'];

/************* CREATE  *************/

    // [POST] api/shipping/method <-- Create new row
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

    // [GET] api/shipping/methods <-- Get all lists
        function list(Request $reqs){  

            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;
            $cdata = M_default::where('m_shipping_methods.status','<>','0');

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
            
            if($cdata){
            // INJECT SHIPPING COVERAGES
                $shipping_methods = $cdata->toArray();
                $shipping_methodsData = $cdata->toArray();

                foreach($shipping_methodsData as $shipping_methodKey => $shipping_method){
                    $priceTotal = 0;

                    $shipping_method_id = $shipping_method['shipping_method_id'];
                    $cdata2 = M_shipping_method_coverages::where([
                        ['m_shipping_method_coverages.shipping_method_id','=',$shipping_method_id]
                    ])
                    ->leftJoin('m_states', 'm_shipping_method_coverages.state_id', 'm_states.state_id')
                    ->leftJoin('m_countries', 'm_shipping_method_coverages.country_id', 'm_countries.country_id')
                    ->leftJoin('m_cities', 'm_shipping_method_coverages.city_id', 'm_cities.city_id')
                    ->select(
                        'm_shipping_method_coverages.shipping_method_coverage_id',
                        'm_shipping_method_coverages.country_id',
                        'm_shipping_method_coverages.state_id',
                        'm_shipping_method_coverages.city_id',
                        'm_states.name as state_name',
                        'm_countries.name as country_name',
                        'm_cities.name as city_name',
                    )
                    ->get();
                    
                    $shipping_method_coverages = $cdata2->toArray();
                    $shipping_method_coveragesData = $cdata2->toArray();

                    foreach($shipping_method_coveragesData as $shipping_method_coverageKey => $shipping_method_coverage){
                        $scope = NULL;
                        foreach($shipping_method_coverage as $coverageKey => $coverage){
                            if($coverage == NULL){
                                unset($shipping_method_coverages[$shipping_method_coverageKey][$coverageKey]);
                            }
                            if($coverageKey == "state_id" && $coverage != NULL){
                                $scope = "state";
                            }elseif($coverageKey == "country_id" && $coverage != NULL){
                                $scope = "country";
                            }elseif($coverageKey == "city_id" && $coverage != NULL){
                                $scope = "city";
                            }
                            $shipping_method_coverages[$shipping_method_coverageKey]['scope'] = $scope;
                        }
                    }
                    $shipping_methods[$shipping_methodKey]['coverages'] = $shipping_method_coverages;
                }

                $cdata = $shipping_methods;

                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return response()->json($response);
        }


    
    // [GET] api/shipping/method/{id} <-- Get specific row
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
            
            $cdata = $cdata->first();

            if($cdata){
            // INJECT SHIPPING COVERAGES
                $shipping_method = $cdata->toArray();

                $priceTotal = 0;

                $cdata2 = M_shipping_method_coverages::where([
                    ['m_shipping_method_coverages.shipping_method_id','=',$shipping_method['shipping_method_id']]
                ])
                ->leftJoin('m_states', 'm_shipping_method_coverages.state_id', 'm_states.state_id')
                ->leftJoin('m_countries', 'm_shipping_method_coverages.country_id', 'm_countries.country_id')
                ->leftJoin('m_cities', 'm_shipping_method_coverages.city_id', 'm_cities.city_id')
                ->select(
                    'm_shipping_method_coverages.shipping_method_coverage_id',
                    'm_shipping_method_coverages.country_id',
                    'm_shipping_method_coverages.state_id',
                    'm_shipping_method_coverages.city_id',
                    'm_states.name as state_name',
                    'm_countries.name as country_name',
                    'm_cities.name as city_name',
                )
                ->get();
                
                $shipping_method_coverages = $cdata2->toArray();
                $shipping_method_coveragesData = $cdata2->toArray();
                $coverage_scope = array();

                foreach($shipping_method_coveragesData as $shipping_method_coverageKey => $shipping_method_coverage){
                    $scope = NULL;
                    foreach($shipping_method_coverage as $coverageKey => $coverage){
                        if($coverage == NULL){
                            unset($shipping_method_coverages[$shipping_method_coverageKey][$coverageKey]);
                        }
                        if($coverageKey == "state_id" && $coverage != NULL){
                            $scope = "state";
                        }elseif($coverageKey == "country_id" && $coverage != NULL){
                            $scope = "country";
                        }elseif($coverageKey == "city_id" && $coverage != NULL){
                            $scope = "city";
                        }
                        $shipping_method_coverages[$shipping_method_coverageKey]['scope'] = $scope;
                    }

                    if($scope != NULL){
                        $coverage_scope[$scope][] = $shipping_method_coverage;
                    }
                }
                $shipping_method['coverages'] = $shipping_method_coverages;
                $shipping_method['coverage_scopes'] = $coverage_scope;

                $cdata = $shipping_method;

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

    // [PUT] api/shipping/method/{id} <-- Update specific row
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

    // [PUT] api/shipping/method/restore/{id} <-- Restore deleted specific row
    function restore($id){
        $response = array();
        $cdata = M_default::withTrashed()->find($id)->restore();
    
        if($cdata){

            $cdata2 = M_default::find($id);
            $cdata2->deleted_by = NULL;
            $cdata2->save();

            $cdata3 = M_default::where(
                $this->module, '=', $id
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

    // [DELETE] api/shipping/method/{id} <-- SoftDelete specific row
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

    // [DELETE] api/shipping/method/delete/{id} <-- Permanent delete specific row
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
