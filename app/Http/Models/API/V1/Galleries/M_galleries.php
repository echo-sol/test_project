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


class M_galleries extends Authenticable{

    use SoftDeletes;

    protected $table = 'm_galleries';
    protected $primaryKey = 'gallery_id';
    public $timestamps  = false;
    protected $fillable = ['deleted_by'];


/************* DELETE  *************/

    public function harddelete($id){
        $response = array();
        try{

        $gallery = $this->withTrashed()->find($id);

        if($gallery){

            if($gallery->forceDelete()){
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
