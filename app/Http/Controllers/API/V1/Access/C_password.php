<?php

namespace App\Http\Controllers\API\V1\Access;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Users\M_users as M_users;
use DB, Hash;


class C_login extends Controller{

    private $module = "login";
    private $login_required = ['email','password'];

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

                    unset($data->password);

                    $response["status"] = True;
                    $response["msg"] = "User authenticated";
                    $response["data"] = $data;
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
