<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Discounts REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Marketings\Discounts;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Marketings\Discounts\M_discounts as M_default;
use App\Http\Models\API\V1\Marketings\Discounts\M_discount_redeems as M_discount_redeems;


class C_discounts extends Controller{

    private $module = "discount";
    private $create_required = [
        'name',
        'type',
        'start_date',
        'end_date',
        'value',
        'status',
        'created_by'
    ];

/************* CREATE  *************/

    // [POST] api/discount <-- Create new row
        function create(Request $reqs){

            $response = array();

            if($this->validation($this->create_required, $reqs)){

                $cdata = new M_default;
                foreach($reqs->input() as $colname => $value){
                    $cdata->{$colname} = $value;
                }
        
                $cdata->created_at = date("Y-m-d H:i:s");
                $cdata->save();

                // Return Data Get
                return $this->get($cdata->discount_id);
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parameters";
            }

            return response()->json($response);
        }

/************* GET/READ  *************/

    // [GET] api/discounts <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $filters = json_decode($reqs->input('filters'));
            $cdata = M_default::where($filters)
            ->leftJoin('m_discount_redeems','m_discounts.discount_id','m_discount_redeems.discount_id')
            ->selectRaw(

                'm_discounts.*,
                count(m_discount_redeems.discount_id) as redeem_totals

            ')
            ->groupBy('m_discounts.discount_id')
            ->get();

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



    // [GET] api/discount/{id} <-- Get specific row
        function get($id){
            $response = array();

            $cdata = M_default::where(
                'm_discounts.discount_id', '=', $id
            )
            ->leftJoin('m_discount_redeems','m_discounts.discount_id','m_discount_redeems.discount_id')
            ->selectRaw(

                'm_discounts.*,
                count(m_discount_redeems.discount_id) as redeem_totals

            ')
            ->groupBy('m_discounts.discount_id')
            ->first();

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

    // [PUT] api/discount/{id} <-- Update specific row
        function update(Request $reqs, $id){
            $response = array();

            if($this->validation(array('updated_by'), $reqs)){
                $cdata = M_default::find($id);

                foreach($reqs->input() as $colname => $value){
                    $cdata->{$colname} = $value;
                }
                
                $cdata->updated_at = date("Y-m-d H:i:s");
                $cdata->save();

                // Return Data Get
                return $this->get($cdata->discount_id);
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing updated_by";
            }

            return response()->json($response);
        }

    // [PUT] api/discount/restore/{id} <-- Restore deleted specific row
        function restore($id){
            $response = array();
            $discount = M_default::withTrashed()->find($id)->restore();
        
            if($discount){

                $discount_response = M_default::find($id)->update(['deleted_by'=>NULL]);

                $disount_redeems = M_discount_redeems::withTrashed()->where(
                    $this->module.'_id', '=', $id
                )->restore();

                
                if($disount_redeems){
                    $disount_redeems = M_discount_redeems::where(
                        $this->module.'_id', '=', $id
                    )->update(['deleted_by'=>NULL]);
                    // Return Data Get
                    return $this->get($id);
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Problem occured. Please try again";
                    $response["debug"] = "Cannot find restored redeems item";
                }


            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Fail to restore";
            }

            return response($response);
        }



/************* DELETE  *************/

    // [DELETE] api/discount/{id} <-- SoftDelete specific row
    function delete(Request $reqs, $id){
        $response = array();
        
        if($this->validation(array('deleted_by'), $reqs)){
            $discount = M_default::find($id);
            $discount->deleted_by = $reqs->input('deleted_by');
            $discount->save();

            if($discount->delete()){

                $discount_redeems = M_discount_redeems::where(
                    $this->module.'_id', '=', $id
                )->update(
                    ['deleted_by' => $reqs->input('deleted_by')]
                );

                $discount_redeems_delete = M_discount_redeems::where(
                    $this->module.'_id', '=', $id
                )->delete();

                if($discount_redeems_delete){
                    $response['status'] = True;
                    $response["msg"] = "Sucessfully Deleted";
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Problem occured. Please try again";
                    $response["debug"] = "Fail deleting redeemed record";
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

    // [DELETE] api/discount/delete/{id} <-- Permanent delete specific row
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
            $discount = new M_default();
            $discount_response = $discount->harddelete($id);
            if($discount_response["status"]){
                
                $discount_redeems = new M_discount_redeems();
                $discount_redeems_response = $discount_redeems->harddelete($id);
                if($discount_redeems_response['status']){
                    $response["status"] = TRUE;
                    $response["msg"] = $discount_redeems_response["msg"];
                }else{
                    $response["status"] = False;
                    $response["msg"] = $discount_redeems_response["msg"];
                    $response["debug"] = $discount_redeems_response["debug"];
                }

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
