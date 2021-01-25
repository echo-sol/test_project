<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: -
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Conversations REST-API (CRUD)
**/


namespace App\Http\Controllers\API\V1\Conversations;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Conversations\M_conversations as M_default;
use App\Http\Models\API\V1\Customers\M_customers as M_customers;
use App\Http\Models\API\V1\Reviewers\M_reviewers as M_reviewers;
use App\Http\Models\API\V1\Bookings\M_bookings as M_bookings;


class C_conversations extends Controller{

    private $module = "conversation";
    private $create_required = ['reseller_id','customer_id','status','created_by'];

/************* CREATE  *************/

    // [POST] api/conversation <-- Create new row
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

    // [GET] api/conversations <-- Get all lists
    function list(Request $reqs){  
        $response = array();

        $paginate = ($reqs->input('paginate') == 'enable') ? true : false;            
        $limit = ($reqs->input('limit') != NULL) ? $reqs->input('limit') : false;

        $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
        $withorfilters = ($reqs->input('withorfilters') != NULL) ? json_decode($reqs->input('withorfilters')) : array();
        $orfilters = json_decode($reqs->input('orfilters'));  
        if($orfilters != NULL){

            $cdata = M_default::where('m_conversations.status','<>','0');
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
            $filters[] = ['m_conversations.status','<>','0'];
            $cdata = M_default::where($filters);
        }

        $cdata->select('m_conversations.*')
        ->orderBy('m_conversations.updated_at','DESC');

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
                $conversations = $cdata->toArray();
                $conversationData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($conversationData as $conversationKey => $conversation){

                    $reviewer_id = $conversation['reseller_id'];
                    $cdata2 = M_reviewers::where([
                        ['m_reviewers.reviewer_id','=',$reviewer_id]
                    ])
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
                        $conversations['data'][$conversationKey]['reviewer_details'] = $reviewer_details;
                    }else{
                        $conversations[$conversationKey]['reviewer_details'] = $reviewer_details;
                    }
                }

            // INJECT CUSTOMER DETAILS
                $conversationData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($conversationData as $conversationKey => $conversation){

                    $customer_id = $conversation['customer_id'];
                    $cdata3 = M_customers::where([
                        ['m_customers.customer_id','=',$customer_id]
                    ])
                    ->leftJoin('m_user_details', 'm_customers.user_id', 'm_user_details.user_id')
                    ->leftJoin('m_images', 'm_user_details.propic', 'm_images.image_id')
                    ->select(
                        //Customer Details
                        'm_user_details.fullname',

                        //Customer Images
                        'm_images.url as customer_propic_url',
                        'm_images.alt as customer_propic_alt',
                    )
                    ->first();
                    $customer_details = $cdata3->toArray();

                    if($paginate){
                        $conversations['data'][$conversationKey]['customer_details'] = $customer_details;
                    }else{
                        $conversations[$conversationKey]['customer_details'] = $customer_details;
                    }
                }

            // INJECT LATEST MESSAGE
                $conversationData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($conversationData as $conversationKey => $conversation){

                    $conversation_id = $conversation['conversation_id'];
                    $cdata4 = M_default::where([
                        ['m_conversations.conversation_id','=',$conversation_id]
                    ])
                    ->leftJoin('m_messages', 'm_conversations.conversation_id', 'm_messages.conversation_id')
                    ->select(
                        //Reseller Name
                        'm_messages.message',
                        'm_messages.type',
                        'm_messages.created_at',
                        'm_messages.created_by',

                    )
                    ->latest('m_messages.created_at')->first();
                    $latest_message = $cdata4->toArray();

                    if($paginate){
                        $conversations['data'][$conversationKey]['latest_message'] = $latest_message;
                    }else{
                        $conversations[$conversationKey]['latest_message'] = $latest_message;
                    }
                }

            // INJECT BOOKING DETAILS
                $conversationData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                foreach($conversationData as $conversationKey => $conversation){

                    $customer_id = $conversation['customer_id'];
                    $cdata5 = M_bookings::where([
                        ['m_bookings.customer_id','=',$customer_id]
                    ])
                    ->leftJoin('m_booking_services', 'm_bookings.booking_id', 'm_booking_services.booking_id')
                    ->leftJoin('m_service_details', 'm_booking_services.service_detail_id', 'm_service_details.service_detail_id')
                    ->leftJoin('m_booking_statuses','m_bookings.status','m_booking_statuses.booking_status_id')
                    ->leftJoin('m_services', 'm_service_details.service_id', 'm_services.service_id')
                    ->select(
                        //Service Details
                        'm_services.name as service_name',
                        'm_service_details.description as service_description',

                        //Booking Details
                        'm_bookings.status as booking_status',
                        'm_booking_statuses.status_name',

                    )
                    ->first();
                    $booking_details = $cdata5->toArray();

                    if($paginate){
                        $conversations['data'][$conversationKey]['booking_details'] = $booking_details;
                    }else{
                        $conversations[$conversationKey]['booking_details'] = $booking_details;
                    }
                }

                $cdata = $conversations;

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



    // [GET] api/conversation/{id} <-- Get specific row
    function get(Request $reqs, $id){
        $response = array();

        $paginate = ($reqs->input('paginate') == 'enable') ? true : false;            
        $limit = ($reqs->input('limit') != NULL) ? $reqs->input('limit') : false;

        $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
        $withorfilters = ($reqs->input('withorfilters') != NULL) ? json_decode($reqs->input('withorfilters')) : array();
        $orfilters = json_decode($reqs->input('orfilters'));

        if($orfilters != NULL){
            $cdata = M_default::where('m_conversations.conversation_id', '=', $id);
            $cdata->where(function ($q) use ($filters, $orfilters){
                $q->where($filters);
                foreach($orfilters as $orfilter){
                    $q->orWhere([$orfilter]);
                }
            });

            if(count($withorfilters) > 0){
                $cdata->where($filters);
            }
        }else{
            $filters[] = ['m_conversations.conversation_id', '=', $id];
            $cdata = M_default::where($filters);
        }

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

        // $cdata->selectRaw('m_conversations.*');
            // ->orderBy('m_conversations.conversation_id');

        $cdata = $cdata->first();


        if($cdata){
            // INJECT REVIEWER DETAILS
                $conversations = $cdata->toArray();
                $conversationData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();
                    // dd($conversationData);
                    $reviewer_id = $conversations['reseller_id'];
                    $cdata2 = M_reviewers::where([
                        ['m_reviewers.reviewer_id','=',$reviewer_id]
                    ])
                    ->leftJoin("m_users","m_reviewers.user_id","m_users.user_id")
                    ->leftJoin("m_user_details","m_users.user_id","m_user_details.user_id")
        
                    ->leftJoin("m_services","m_users.user_id","m_services.created_by")
                    ->leftJoin("m_service_details","m_services.service_id","m_service_details.service_id")
                    ->leftJoin("m_service_reviews","m_service_details.service_id","m_service_reviews.service_id")
                    ->leftJoin("m_reviews","m_service_reviews.review_id","m_reviews.review_id")
                    
                    ->leftjoin('m_cities', 'm_user_details.city_id', '=', 'm_cities.city_id')
                    ->leftjoin('m_states', 'm_user_details.state_id', '=', 'm_states.state_id')
                    ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')                    
                    ->select(
                        //REVIEWER
                        "m_reviewers.*",
                        "m_reviewers.status as reviewer_status",

                        //USERS
                        "m_users.email",
                        "m_users.username",

                        //USER DETAILS
                        'm_user_details.*',
                        'm_cities.name as city_name',
                        'm_states.name as state_name',

                        //SERVICES DETAILS
                        "m_services.service_id",
                        "m_service_details.service_detail_id",
                        "m_service_details.normal_price",
                        "m_service_details.description as service_description",

                        //REVIEWS
                        "m_reviews.review_id",
                        "m_reviews.title as review_title",
                        "m_reviews.rating as review_rating",
                        "m_reviews.message as review_message",

                        //Images
                        'm_images.url as reviewer_propic_url',
                        'm_images.alt as reviewer_propic_alt',
                    )
                    ->first();
                    $reviewer_details = $cdata2->toArray();

                    if($paginate){
                        $conversations['data']['reviewer_details'] = $reviewer_details;
                    }else{
                        $conversations['reviewer_details'] = $reviewer_details;
                    }

            // INJECT CUSTOMER DETAILS
                $conversationData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                    $customer_id = $conversations['customer_id'];
                    $cdata3 = M_customers::where([
                        ['m_customers.customer_id','=',$customer_id]
                    ])
                    ->leftJoin('m_user_details', 'm_customers.user_id', 'm_user_details.user_id')
                    ->leftJoin('m_images', 'm_user_details.propic', 'm_images.image_id')
                    ->select(
                        //Reseller Details
                        'm_user_details.fullname',

                        //Reseller Images
                        'm_images.url as customer_propic_url',
                        'm_images.alt as customer_propic_alt',
                    )
                    ->first();
                    $customer_details = $cdata3->toArray();

                    if($paginate){
                        $conversations['data']['customer_details'] = $customer_details;
                    }else{
                        $conversations['customer_details'] = $customer_details;
                    }

            // INJECT BOOKING DETAILS
                $bookingData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                $customer_id = $conversations['customer_id'];
                $cdata4 = M_bookings::where([
                    ['m_bookings.customer_id','=',$customer_id]
                ])
                ->leftJoin('m_booking_services', 'm_bookings.booking_id', 'm_booking_services.booking_id')
                ->leftJoin('m_service_details', 'm_booking_services.service_detail_id', 'm_service_details.service_detail_id')
                ->leftJoin('m_booking_statuses','m_bookings.status','m_booking_statuses.booking_status_id')
                ->leftJoin('m_services', 'm_service_details.service_id', 'm_services.service_id')
                ->select(
                    //Service Details
                    'm_services.name as service_name',
                    'm_service_details.description as service_description',

                    //Booking Details
                    'm_bookings.status as booking_status',
                    'm_booking_statuses.status_name',

                )
                ->first();
                $booking_details = $cdata4->toArray();

                if($paginate){
                    $conversations['data']['booking_details'] = $booking_details;
                }else{
                    $conversations['booking_details'] = $booking_details;
                }

                

                $cdata = $conversations;

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




/************* UPDATE  *************/

    // [PUT] api/conversation/{id} <-- Update specific row
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

    // [PUT] api/conversation/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/conversation/{id} <-- SoftDelete specific row
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

    // [DELETE] api/conversation/delete/{id} <-- Permanent delete specific row
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
