<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Model Order Products REST-API (CRUD)
**/

namespace App\Http\Models\API\V1\Orders;

// STANDARD CORE
use Illuminate\Database\Eloquent\Model as Authenticable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;


class M_order_products extends Authenticable{

    use SoftDeletes;

    protected $table = 'm_order_products';
    protected $primaryKey = 'order_product_id';
    public $timestamps  = false;


/************* DELETE  *************/

    public function harddelete($id){
        $response = array();
        try{
            $dataCheck = $this->where(
                'order_id', '=', $id
            )->first();

            $mdata = $this->withTrashed()->where(
                'order_id', '=', $id
            )->forceDelete();

            if($mdata || empty($dataCheck)){
                $response["status"] = True;
                $response["msg"] = "Successfully deleted permanently";
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
