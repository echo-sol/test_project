<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Faqs REST-API (CRUD)
**/


namespace App\Http\Controllers\API\V1\Highscores;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Highscores\M_highscores as M_default;


class C_highscores extends Controller{

    private $module = "highscore";
    private $create_required = ['name','level','score','email','country_id','status','created_by'];

/************* CREATE  *************/

    // [POST] api/highscore <-- Create new row
    function create(Request $reqs){

        $response = array();

        if($this->validation($this->create_required, $reqs)){

            $cdata = new M_default;
            foreach($reqs->input() as $colname => $value){
                $cdata->{$colname} = $value;
            }
    
            $cdata->created_at = date("Y-m-d H:i:s");
            $cdata->save();

            $response["status"] = True;
            $response["data"] = $cdata;
        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing parameters";
        }

        return response()->json($response);
    
    }

/************* GET/READ  *************/

    // [GET] api/highscores <-- Get all lists
    function list(Request $reqs){  
        $response = array();
        $limit = ($reqs->input('limit') != NULL) ? $reqs->input('limit') : false;

        $filters = json_decode($reqs->input('filters'));

        if($reqs->input('topfilter') != NUll){
            if($reqs->input('topfilter') == "enable"){

                $current_month = date("Y-m-00 00:00:00");
                 $filters[] = ['m_highscores.created_at','<=',$current_month];

                $cdata = M_default::where($filters)
                ->leftJoin('m_countries','m_highscores.country_id','=','m_countries.country_id')
                ->select(
                    \DB::raw('CONCAT(MONTH(m_highscores.created_at),YEAR(m_highscores.created_at)) as highscore_date'),
                    \DB::raw('MAX(m_highscores.score) as max'),
                    "m_highscores.*",
                    //country 
                    'm_countries.code as country_code',
                    'm_countries.name as country_name'
                )
                ->groupBy('highscore_date')
                ->distinct()
                ->orderBy('created_at','desc')
                ->get();
            }
        }else{
            if($reqs->input('datefilter') != NUll){
                if($reqs->input('datefilter') == "enable"){
                    $current_month = date("Y-m-00 00:00:00");
                    $filters[] = ['m_highscores.created_at','>=',$current_month];
                }
            }

            if($reqs->input('orderby') != NULL){
                $orderby = json_decode($reqs->input('orderby'));
                if(count($orderby) > 1){
                    if($orderby[1] == 'ASC' || $orderby[1] == 'DESC'){
                        $cdata = M_default::where($filters)->orderBy($orderby[0],$orderby[1])->leftJoin('m_countries','m_highscores.country_id','=','m_countries.country_id')
                        ->select(
                            'm_highscores.*',
                            'm_countries.code as country_code',
                            'm_countries.name as country_name'
                        )->get();
                    }
                }
            }
            else{
                $cdata = M_default::where($filters)->orderBy('created_at','desc')->leftJoin('m_countries','m_highscores.country_id','=','m_countries.country_id')
                ->select(
                    'm_countries.code as country_code',
                    'm_countries.name as country_name'
                )->get();
            }
        }
        if($limit){
            $cdata = $cdata->skip(0)->take($limit);
        }
        
        if($cdata){
            $response["status"] = True;
            $response["data"] = $cdata;
        }else{
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = "Cannot retrieve from database";
        }

        return response()->json($response);
    }



    // [GET] api/highscore/{id} <-- Get specific row
    function get($id){
        $response = array();

        $cdata = M_default::where(
            $this->module.'_id', '=', $id
        )->first();

        if($cdata){
            $response["status"] = True;
            $response["data"] = $cdata;
        }else{
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = "Cannot retrieve from database";
        }

        return response()->json($response);
    }




/************* UPDATE  *************/

    // [PUT] api/highscore/{id} <-- Update specific row
    function update(Request $reqs, $id){
        $response = array();

        if($this->validation(array('updated_by'), $reqs)){
            $cdata = M_default::find($id);

            foreach($reqs->input() as $colname => $value){
                $cdata->{$colname} = $value;
            }
            
            $cdata->updated_at = date("Y-m-d H:i:s");
            $cdata->save();
            $response["status"] = True;
            $response["data"] = $cdata;
        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing updated_by";
        }

        return response()->json($response);
    }

    // [PUT] api/highscore/restore/{id} <-- Restore deleted specific row
    function restore($id){
        $response = array();
        $cdata = M_default::withTrashed()->find($id)->restore();
    
        if($cdata){

            $cdata2 = M_default::find($id);
            $cdata2->deleted_by = NULL;
            $cdata2->save();

            $cdata3 = M_default::where(
                $this->module.'_id', '=', $id
            )->get();

            if($cdata3){
                $response["status"] = True;
                $response["data"] = $cdata2;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot find restored item";
            }

        }else{
            $response["status"] = False;
            $response["msg"] = "Problem occured. Please try again";
            $response["debug"] = "Fail to restore";
        }

        return response($response);
    }



/************* DELETE  *************/

    // [DELETE] api/faq/{id} <-- SoftDelete specific row
    function delete(Request $reqs, $id){
        $response = array();
        
        if($this->validation(array('deleted_by'), $reqs)){
            $cdata = M_default::find($id);
            $cdata->deleted_by = $reqs->input('deleted_by');
            $cdata->save();

            if($cdata->delete()){
                $response['status'] = True;
                $response["msg"] = "Sucessfully Deleted";
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Fail deleting record";
            }
        }else{
            $response["status"] = False;
            $response["msg"] = "Missing parameters";
            $response["debug"] = "Missing deleted_by";
        }

        return response($response);
    }

    // [DELETE] api/faq/delete/{id} <-- Permanent delete specific row
    function harddelete(Request $req,$id){

        $response = array();
        $error = null;
        $apiusername = config('custom.APIHardDelete.APIusername');
        $apipassword = config('custom.APIHardDelete.APIpassword');

        // Validate Username
        if($req->input("username")){
            if($req->input("username") != $apiusername){
                $error = "Invalid access"; 
                $debug = "Username not the same";
            }
        }else{
            $error = "Invalid access";
            $debug = "Username empty";

        }
    
        //Validate password
        if($req->input("pass")){
            if($req->input("pass") != $apipassword){
                $error = "Invalid access";
                $debug = "Password not the same";
            }
        }else{
            $error = "Invalid access";
            $debug = "Password empty";
        }

        // Validate ID
        if(!isset($id)){
            $error = "Invalid ID"; 
            $debug = "ID empty";
        }

        if(!$error){
            $cdata = new M_default();
            $data = $cdata->harddelete($id);
            if($data["status"]){
                $response["status"] = TRUE;
                $response["msg"] = $data["msg"];
            }else{
                $response["status"] = False;
                $response["msg"] = $data["msg"];
                $response["debug"] = $data["debug"];
            }

        }else{
            $response["status"] = False;
            $response["msg"] = $error;
            $response["debug"] = $debug;
        }

        return response($response);


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
