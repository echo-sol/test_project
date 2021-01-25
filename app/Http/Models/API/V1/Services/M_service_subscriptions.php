<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Model Service_Details REST-API (CRUD)
**/

namespace App\Http\Models\API\V1\Services;

// STANDARD CORE
use Illuminate\Database\Eloquent\Model as Authenticable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;


class M_service_subscriptions extends Authenticable{

    use SoftDeletes;

    protected $table = 'm_service_subscriptions';
    protected $primaryKey = 'service_subscription_id';
    public $timestamps  = false;


/************* DELETE  *************/

    public function harddelete($id){
        $response = array();
        try{

            $dataCheck = $this->where(
                'service_subscription_id', '=', $id
            )->first();

            $mdata = $this->withTrashed()->where(
                'service_subscription_id', '=', $id
            )->forceDelete();

            if($mdata || empty($dataCheck)){
                $response["status"] = True;
                $response["msg"] = "Successfully deleted permanently";
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Query to delete service detail fail";
            }
            
        }catch(\Illuminate\Database\QueryException $err){
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = $err -> getMessage();
        }
        return $response;
    }
}   
