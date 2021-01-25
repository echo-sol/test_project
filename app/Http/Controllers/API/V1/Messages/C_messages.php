<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Messages REST-API (CRUD)
**/


namespace App\Http\Controllers\API\V1\Messages;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Messages\M_messages as M_default;
use App\Http\Models\API\V1\Conversations\M_conversations as M_conversations;
use App\Http\Models\API\V1\Customers\M_customers as M_customers;
use App\Http\Models\API\V1\Reviewers\M_reviewers as M_reviewers;


class C_messages extends Controller{

    private $module = "message";
    private $create_required = ['conversation_id','message','status','created_by'];

/************* CREATE  *************/

    // [POST] api/message <-- Create new row
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

    // [GET] api/messages <-- Get all lists
    function list(Request $reqs){  
        $response = array();

        $paginate = ($reqs->input('paginate') == 'enable') ? true : false;            
        $limit = ($reqs->input('limit') != NULL) ? $reqs->input('limit') : false;

        $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
        $withorfilters = ($reqs->input('withorfilters') != NULL) ? json_decode($reqs->input('withorfilters')) : array();
        $orfilters = json_decode($reqs->input('orfilters'));  
        if($orfilters != NULL){

            $cdata = M_default::where('m_messages.status','<>','0');
            $cdata->where(function ($q) use ($withorfilters, $orfilters){
                $q->where($withorfilters);
                foreach($orfilters as $orfilter){
                    $q->orWhere([$orfilter]);
                }
            });

            if(count($withorfilters) > 0){
                $cdata->where($filters);
            }

        }else{
            $filters[] = ['m_messages.status','<>','0'];
            $cdata = M_default::where($filters);
        }

        $cdata->select('m_messages.*');

        if($reqs->input('orderby') != NULL){
            $orderby = json_decode($reqs->input('orderby'));
            if(count($orderby) > 1){
                if($orderby[1] == 'ASC' || $orderby[1] == 'DESC'){
                    $cdata->orderBy($orderby[0],$orderby[1]);
                }
            }
        }

        if($paginate){
            $cdata = $cdata->paginate(10);
        }else{
            if($limit){
                $cdata = $cdata->skip(0)->take($limit)->get();
            }else{
                $cdata = $cdata->get();
            }
        }

        if(count($cdata->toArray()) > 0){
            // INJECT REVIEWER DETAILS
                $messages = $cdata->toArray();
                $messageData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($messageData as $messageKey => $message){

                    $conversation_id = $message['conversation_id'];
                    $cdata2 = M_conversations::where([
                        ['m_conversations.conversation_id','=',$conversation_id]
                    ])
                    ->leftJoin("m_reviewers","m_conversations.reseller_id","m_reviewers.reviewer_id")
                    ->leftJoin("m_users","m_reviewers.user_id","m_users.user_id")
                    ->leftJoin("m_user_details","m_users.user_id","m_user_details.user_id")
                    ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')                  
                    ->select(
                        //REVIEWER
                        "m_reviewers.*",
                        "m_reviewers.status as reviewer_status",

                        //USERS
                        "m_users.email",
                        "m_users.username",

                        //USER DETAILS
                        "m_user_details.fullname",
                        "m_user_details.phone",

                        //Images
                        'm_images.url as reviewer_propic_url',
                        'm_images.alt as reviewer_propic_alt',
                    )
                    ->first();
                    $reviewer_details = $cdata2->toArray();

                    if($paginate){
                        $messages['data'][$messageKey]['reviewer_details'] = $reviewer_details;
                    }else{
                        $messages[$messageKey]['reviewer_details'] = $reviewer_details;
                    }
                }

            // INJECT CUSTOMER DETAILS
                $messageData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($messageData as $messageKey => $message){

                    $conversation_id = $message['conversation_id'];
                    $cdata3 = M_conversations::where([
                        ['m_conversations.conversation_id','=',$conversation_id]
                    ])
                    ->leftJoin('m_messages', 'm_conversations.conversation_id', 'm_messages.conversation_id')
                    ->leftJoin('m_customers', 'm_conversations.customer_id', 'm_customers.customer_id')
                    ->leftJoin('m_user_details', 'm_customers.user_id', 'm_user_details.user_id')
                    ->leftJoin('m_images', 'm_user_details.propic', 'm_images.image_id')
                    ->select(
                        //Reseller Name
                        'm_user_details.fullname',

                        //Reseller Images
                        'm_images.url as customer_propic_url',
                        'm_images.alt as customer_propic_alt',
                    )
                    ->first();
                    $customer_details = $cdata3->toArray();

                    if($paginate){
                        $messages['data'][$messageKey]['customer_details'] = $customer_details;
                    }else{
                        $messages[$messageKey]['customer_details'] = $customer_details;
                    }
                }

                $cdata = $messages;

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



    // [GET] api/message/{id} <-- Get specific row
    function get(Request $reqs, $id){
        $response = array();

        $paginate = ($reqs->input('paginate') == 'enable') ? true : false;            
        $limit = ($reqs->input('limit') != NULL) ? $reqs->input('limit') : false;

        $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
        $withorfilters = ($reqs->input('withorfilters') != NULL) ? json_decode($reqs->input('withorfilters')) : array();
        $orfilters = json_decode($reqs->input('orfilters'));

        if($orfilters != NULL){
            $cdata = M_default::where('m_messages.message_id', '=', $id);
            $cdata->where(function ($q) use ($filters, $orfilters){
                $q->where($filters);
                foreach($orfilters as $orfilter){
                    $q->orWhere([$orfilter]);
                }
            });
        }else{
            $filters[] = ['m_messages.message_id', '=', $id];
            $cdata = M_default::where($filters);
        }
        $cdata = $cdata->first();

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

    // [PUT] api/message/{id} <-- Update specific row
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

    // [PUT] api/message/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/message/{id} <-- SoftDelete specific row
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

    // [DELETE] api/message/delete/{id} <-- Permanent delete specific row
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
