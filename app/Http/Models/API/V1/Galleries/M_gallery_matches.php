<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Model Galleries REST-API (CRUD)
**/

namespace App\Http\Models\API\V1\Galleries;

// STANDARD CORE
use Illuminate\Database\Eloquent\Model as Authenticable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;


class M_gallery_matches extends Authenticable{

    use SoftDeletes;

    protected $table = 'm_gallery_matches';
    protected $primaryKey = 'gallery_match_id';
    public $timestamps  = false;


/************* DELETE  *************/

    public function harddelete($id){
        $response = array();
        try{

            $dataCheck = $this->where(
                'gallery_id', '=', $id
            )->first();

            $mdata = $this->withTrashed()->where(
                'gallery_id', '=', $id
            )->forceDelete();

            if($mdata || empty($dataCheck)){
                $response["status"] = True;
                $response["msg"] = "Successfully deleted permanently";
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Query to delete product detail fail";
            }
            
        }catch(\Illuminate\Database\QueryException $err){
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = $err -> getMessage();
        }
        return $response;
    }
}   
