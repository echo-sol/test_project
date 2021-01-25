<?php

namespace App\Http\Models\API\V1\Users;

// STANDARD CORE
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;

// AUTH
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class M_user_role_access extends Authenticatable{

    use SoftDeletes;

    protected $table = 'm_user_role_access';
    protected $primaryKey = 'user_role_access_id';
    public $timestamps  = false;


/************* DELETE  *************/

    public function harddelete($id){
        $response = array();
        try{
        $user = $this->withTrashed()->find($id);
        if($user){

            if($user->forceDelete()){
                $response["status"] = True;
                $response["msg"] = "Successfully deleted permanently";

            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Query to delete fail";
            }
        }else{
                $response["status"] = False;
                $response["msg"] = "Invalid Data";
                $response["debug"] = "Data missing from db";
        }
        }catch(\Illuminate\Database\QueryException $err){
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = $err -> getMessage();
        }
        return $response;
    }

}   
