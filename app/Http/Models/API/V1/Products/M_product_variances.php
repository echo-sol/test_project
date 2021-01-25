<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Danial Abd Rahman
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Model Product Variances REST-API (CRUD)
**/

namespace App\Http\Models\API\V1\Products;

// STANDARD CORE
use Illuminate\Database\Eloquent\Model as Authenticable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Schema;


class M_product_variances extends Authenticable{

    use SoftDeletes;

    protected $table = 'm_product_variances';
    protected $primaryKey = 'product_variance_id';
    public $timestamps  = false;



    // CREATE NEW COLUMNS
    static public function createColumns($columns){
        $_this = new M_product_variances;

        if(!empty($columns)){
            foreach($columns as $column => $columnData){
                if (!Schema::hasColumn($_this->table, $column)){
                    Schema::table($_this->table, function($table) use($column) {
                        $table->string($column, 255)->after('product_detail_id')->nullable();
                    });
                }
            }
        }
        
    }

    // GET TABLE COLUMNS
    static public function getTableColumns() {
        $_this = new M_product_variances;
        return $_this->getConnection()->getSchemaBuilder()->getColumnListing($_this->getTable());
    }

/************* DELETE  *************/

    public function harddelete($id){
        $response = array();
        try{

            $dataCheck = $this->where(
                'product_detail_id', '=', $id
            )->first();

            $mdata = $this->withTrashed()->where(
                'product_detail_id', '=', $id
            )->forceDelete();

            if($mdata || empty($dataCheck)){
                $response["status"] = True;
                $response["msg"] = "Successfully deleted permanently";
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Query to delete product variance fail";
            }
        }catch(\Illuminate\Database\QueryException $err){
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = $err -> getMessage();
        }
        return $response;
    }
}   
