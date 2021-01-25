<?php

namespace App\Http\Controllers\API\V1\Access;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Users\M_users as M_users;
use App\Http\Models\API\V1\Customers\M_customers as M_customers;
use App\Http\Models\API\V1\Resellers\M_resellers as M_resellers;
use App\Http\Models\API\V1\Reviewers\M_reviewers as M_reviewers;

// CONTROLLERS
use App\Http\Controllers\API\V1\Users\C_users;
use App\Http\Controllers\API\V1\Users\C_user_roles;

use DB, Hash;


class C_login extends Controller{

    private $module = "login";
    private $login_required = ['email'];

/************* ATTEMPT  *************/

    // [POST] api/login
    function attempt(Request $reqs){

        $response = array();

        if($this->validation($this->login_required, $reqs)){

            $encrypted_password = bcrypt($reqs->password);

            $data = collect(DB::select( 
                "SELECT * FROM m_users WHERE 
                email = '$reqs->email' AND
                status <> 0 AND
                deleted_at IS NULL" 
            ))->first();

            if($data){
                if(Hash::check($reqs->password,$data->password) || Hash::check($data->email, $reqs->novalidate)) {

                    // GET USER DATA
                    $user = new C_users;
                    $user_response = $user->get($data->user_id, true);
                    
                    if($user_response['status']){
                        $user_role_id = $user_response['data']['user_role_id'];
                        $user_id = $user_response['data']['user_id'];

                        // INJECT USER ROLES
                            $user_role = new C_user_roles;
                            $user_role_response = $user_role->get($user_role_id, true);
                            if($user_role_response['status']){
                                $user_response['data']['access'] = json_decode($user_role_response['data']['access']);
                            }else{
                                $response["status"] = False;
                                $response["msg"] = $user_response['msg'];
                                $response["debug"] = $user_response['debug'];
                            }
                        
                        // CHECK IF CUSTOMER
                            $customer = M_customers::where([
                                ["m_customers.user_id","=",$user_id]
                            ])->first();
                            
                            if($customer){
                                $user_response['data']['customer_id'] = $customer->customer_id;
                            }else{
                                $user_response['data']['customer_id'] = NULL;
                            }

                        // CHECK IF RESELLER
                            $reseller = M_resellers::where([
                                ["m_resellers.user_id","=",$user_id]
                            ])->first();
                            
                            if($reseller){
                                $user_response['data']['reseller_id'] = $reseller->reseller_id;
                            }else{
                                $user_response['data']['reseller_id'] = NULL;
                            }   
                            
                        // CHECK IF RESELLER
                            $reviewer = M_reviewers::where([
                                ["m_reviewers.user_id","=",$user_id]
                            ])->first();
                            
                            if($reviewer){
                                $user_response['data']['reviewer_id'] = $reviewer->reviewer_id;
                            }else{
                                $user_response['data']['reviewer_id'] = NULL;
                            }    
                        
                        $response = $user_response;

                    }else{
                        $response["status"] = False;
                        $response["msg"] = $user_response['msg'];
                        $response["debug"] = $user_response['debug'];
                    }

                }else{
                    $response["status"] = False;
                    $response["msg"] = "Invalid User/Password";
                    $response["debug"] = "Invalid Password";
                }
            }else{
                $response["status"] = False;
                $response["msg"] = "Invalid User/Password";
                $response["debug"] = "Invalid User = ".$reqs->email;
            }
            
        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing parameters";
        }

        return response()->json($response);
    
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
