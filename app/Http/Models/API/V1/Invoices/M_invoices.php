<?php
/**
 * CHECKLSITS:
 * 1) update namespace __foldername__
 * 2) update class __M_CLASSNAME__
 * 3) update $table __m_tablename__
 * 4) update $primaryKey __id__
 * 
 */

namespace App\Http\Models\API\V1\Invoices;

// STANDARD CORE
use Illuminate\Database\Eloquent\Model as Authenticable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;


class M_invoices extends Authenticable{

    use SoftDeletes;

    protected $table = 'm_invoices';
    protected $primaryKey = 'invoice_id';
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
