<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Referrals REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Marketings\Referrals;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Marketings\Referrals\M_referrals as M_default;
use App\Http\Models\API\V1\Marketings\Referrals\M_referral_redeems as M_referral_redeems;
use App\Http\Models\API\V1\Users\M_user_details as M_user_details;


class C_referrals extends Controller{

    private $module = "referral";
    private $create_required = [
        'user_id',
        'name',
        'type',
        'value',
        'status',
        'created_by'
    ];

/************* CREATE  *************/

    // [POST] api/referral <-- Create new row
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
                return $this->get($cdata->referral_id);
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parameters";
            }

            return response()->json($response);
        
        }

/************* GET/READ  *************/

    // [GET] api/referrals <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $filters = json_decode($reqs->input('filters'));
            $cdata = M_default::where($filters)
            ->leftJoin('m_referral_redeems','m_referrals.referral_id','m_referral_redeems.referral_id')
            ->leftJoin('m_user_details','m_referrals.user_id','m_user_details.user_id')
            ->selectRaw(

                'm_referrals.*,
                m_user_details.fullname,
                count(m_referral_redeems.referral_id) as redeem_totals

            ')
            ->groupBy('m_referrals.referral_id')
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
        


    // [GET] api/referral/{id} <-- Get specific row
        function get($id){
            $response = array();

            $cdata = M_default::where(
                'm_referrals.referral_id', '=', $id
            )
            ->leftJoin('m_referral_redeems','m_referrals.referral_id','m_referral_redeems.referral_id')
            ->leftJoin('m_user_details','m_referrals.user_id','m_user_details.user_id')
            ->selectRaw(

                'm_referrals.*,
                m_user_details.fullname,
                count(m_referral_redeems.referral_id) as redeem_totals

            ')
            ->groupBy('m_referrals.referral_id')
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

    // [PUT] api/referral/{id} <-- Update specific row
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
                return $this->get($cdata->referral_id);
            }else{
                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing updated_by";
            }

            return response()->json($response);
        }

    // [PUT] api/referral/restore/{id} <-- Restore deleted specific row
        function restore($id){
            $response = array();
            $referral = M_default::withTrashed()->find($id)->restore();
        
            if($referral){

                $referral_response = M_default::find($id)->update(['deleted_by'=>NULL]);

                $referral_redeems = M_referral_redeems::withTrashed()->where(
                    $this->module.'_id', '=', $id
                )->restore();

                if($referral_redeems){
                    $referral_redeems = M_referral_redeems::where(
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

    // [DELETE] api/referral/{id} <-- SoftDelete specific row
        function delete(Request $reqs, $id){
            $response = array();
            
            if($this->validation(array('deleted_by'), $reqs)){
                $referral = M_default::find($id);
                $referral->deleted_by = $reqs->input('deleted_by');
                $referral->save();

                if($referral->delete()){

                    $referral_redeems = M_referral_redeems::where(
                        $this->module.'_id', '=', $id
                    )->update(
                        ['deleted_by' => $reqs->input('deleted_by')]
                    );

                    $referral_redeems_delete = M_referral_redeems::where(
                        $this->module.'_id', '=', $id
                    )->delete();

                    if($referral_redeems_delete){
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

    // [DELETE] api/referral/delete/{id} <-- Permanent delete specific row
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
                $referral = new M_default();
                $referral_response = $referral->harddelete($id);
                if($referral_response["status"]){

                    $referral_redeems = new M_referral_redeems();
                    $referral_redeems_response = $referral_redeems->harddelete($id);
                    if($referral_redeems_response['status']){
                        $response["status"] = TRUE;
                        $response["msg"] = $referral_redeems_response["msg"];
                    }else{
                        $response["status"] = False;
                        $response["msg"] = $referral_redeems_response["msg"];
                        $response["debug"] = $referral_redeems_response["debug"];
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
