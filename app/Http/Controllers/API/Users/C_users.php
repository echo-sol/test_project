<?php

namespace App\Http\Controllers\API\Users;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// MODELS
use App\Http\Models\API\V1\Users\M_users as M_default;


class C_users extends Controller{

    private $module = "user";
    private $create_required = ['user_role_id'];

/************* CREATE  *************/

    // [POST] api/user <-- Create new row
        function create(Request $reqs){

            $response = array();

            if($this->validation($this->create_required, $reqs)){
                dd( $reqs);
                $users_inputs = (object) $reqs->input($this->create_user_chains[0]);
                $user_details_inputs = (object) $reqs->input($this->create_user_chains[1]);

                if($this->validation($this->create_required, $users_inputs)){


                    // [0.1] CHECK PASSWORD CONFIRMATION
                    if($this->check_password($users_inputs->password, $users_inputs->password_confirm)){
                        unset($users_inputs->password_confirm);
                        
                        // [0.2] CHECK EMAIL AVAILABILITY
                        if($this->check_email_availability($users_inputs->email)){
                            

                            // [1] CREATE USER
                            $user = new M_default;
                            foreach($users_inputs as $colname => $value){
                                $user->{$colname} = $value;
                            }
                            // Encrypt Password
                                if($user->password){
                                    $user->password = bcrypt($user->password);
                                }

                            $user->created_at = date("Y-m-d H:i:s");
                            if($user->save()){
                                $user_id = $user->user_id;

                                // [2] CREATE USER DETAILS
                                    $user_detail = new C_user_details;
                                    $user_details_inputs->user_id = $user_id;
                                    $user_detail_response = $user_detail->create(new Request, $user_details_inputs);

                                    if($user_detail_response['status']){
                                        $response = $this->get($user_id, true);
                                    }else{
                                        $response["status"] = False;
                                        $response["msg"] = $user_detail_response['msg'];
                                        $response["debug"] = $user_detail_response['debug'];
                                    }
                            }else{
                                $response["status"] = False;
                                $response["msg"] = "Problem occured. Please try again";
                                $response["debug"] = "Cannot create user from database";
                            }

                        }else{
                            $response["status"] = False;
                            $response["msg"] = "Username/email already exist";
                            $response["msg"] = 'Email '.$users_inputs->email." already exist";
                        }

                    }else{
                        $response["status"] = False;
                        $response["msg"] = "Password not match";
                        $response["debug"] = "Password not match";
                    }


                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing user parameters";
                }
            
            }else{

                $response["status"] = False;
                $response["msg"] = "Missing parameters";
                $response["debug"] = "Missing parent parameters";
                dd( $response);
            }

            return response()->json($response);
        
        }

    // CHECK PASSWORD
        function check_password($password, $password_confirm){
            return ($password === $password_confirm) ? TRUE : FALSE;
        }

    // CHECK EMAIL AVAILABILITY
        function check_email_availability($email){
            $cdata = M_default::where(
                'm_users.email', '=', $email
            )->first();
                
            return ($cdata) ? FALSE : TRUE;
        }
    
    // CHECK USERNAME AVAILABILITY
        function check_username_availability($username){
            $cdata = M_default::where(
                'm_users.username', '=', $username
            )->first();
                
            return ($cdata) ? FALSE : TRUE;
        }

/************* GET/READ  *************/

        function reply(Request $reqs){  
            return 'testing';
        }

    // [GET] api/users <-- Get all lists
        function list(Request $reqs, $direct = false){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'enable') ? true : false;

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata = M_default::where('m_users.status','<>','0');
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $filters[] = ['m_users.status','<>','0'];
                $cdata = M_default::where($filters);
            }
            
            // HIDE SUPER
            $cdata->where('m_users.user_role_id','>','0');
    

                if($paginate){
                    $cdata = $cdata->paginate(10);
                }else{
                    $cdata = $cdata->get();
                }

                $response["status"] = True;
                $response["data"] = $cdata;

            return ($direct) ? $response : response()->json($response);
        }



    // [GET] api/user/{id} <-- Get specific row
        function get($id, $direct=false){
            $response = array();

            $cdata = M_default::leftjoin('m_user_roles', 'm_user_roles.user_role_id', '=', 'm_users.user_role_id')
                ->leftjoin('m_user_details', 'm_user_details.user_id', '=', 'm_users.user_id')
                ->leftjoin('m_images', 'm_user_details.propic', '=', 'm_images.image_id')
                ->leftjoin('m_cities', 'm_user_details.city_id', '=', 'm_cities.city_id')
                ->leftjoin('m_states', 'm_user_details.state_id', '=', 'm_states.state_id')
                ->select(
                'm_users.username', 
                'm_users.email', 
                'm_users.facebook', 
                'm_users.google', 
                'm_users.user_role_id', 
                'm_users.status',
                'm_user_roles.name as user_role_name', 
                'm_user_roles.description as user_role_description', 
                'm_user_details.*',
                'm_cities.name as city_name',
                'm_states.name as state_name',
                'm_images.url as user_propic_url',
                'm_images.alt as user_propic_alt'
                )->where(
                'm_users.user_id', '=', $id
            )->first();

            if($cdata){
                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve get user from database";
            }

            return ($direct) ? $response : response()->json($response);
        }




/************* UPDATE  *************/

    // [PUT] api/user/{id} <-- Update specific row
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

    // [PUT] api/user/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/user/{id} <-- SoftDelete specific row
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

    // [DELETE] api/user/delete/{id} <-- Permanent delete specific row
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
