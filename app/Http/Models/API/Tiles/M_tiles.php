<?php
namespace App\Http\Models\API\Tiles;

// STANDARD CORE
use Illuminate\Database\Eloquent\Model as Authenticable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;


class M_tiles extends Authenticable{

    use SoftDeletes;

    protected $table = 'm_tiles';
    protected $primaryKey = 'tile_id';
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
