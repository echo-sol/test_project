<?php
/** 
 * Company: DNF Technologies SDN BHD (1360082-V)
 * Author: Mohammad Hafiz Hilmi
 * Supervision: Danial Abd Rahman
 * Version: 1.0
 * License: ©️Copyright DNFTECHNOLOGIES SDN BHD - For Internal Use Only
 * Description: Controller Bookings REST-API (CRUD)
**/

namespace App\Http\Controllers\API\V1\Bookings;

// STANDARD CORE
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// CONTROLLERS
use App\Http\Controllers\API\V1\Invoices\C_invoices as C_invoices;
use App\Http\Controllers\API\V1\Marketings\Discounts\C_discount_redeems as C_discount_redeems;

// MODELS
use App\Http\Models\API\V1\Bookings\M_bookings as M_default;
use App\Http\Models\API\V1\Bookings\M_booking_services as M_booking_services;
use App\Http\Models\API\V1\Services\M_service_categories as M_service_categories;
use App\Http\Models\API\V1\Marketings\Discounts\M_discount_redeems as M_discount_redeems;


class C_bookings extends Controller{

    private $module = "booking";
    private $create_required = ['invoice_id','status','created_by'];
    private $update_booking_chains = ['m_bookings','m_discount_redeems','m_booking_services'];
    private $create_booking_chains = ['m_invoices','m_bookings','m_booking_services'];

/************* CREATE  *************/

    // [POST] api/booking <-- Create new row
        function create(Request $reqs){

            $response = array();

            if($this->validation($this->create_booking_chains, $reqs)){

                $invoices_inputs = (object) $reqs->input($this->create_booking_chains[0]);
                $bookings_inputs = (object) $reqs->input($this->create_booking_chains[1]);
                $booking_services_inputs = (object) $reqs->input($this->create_booking_chains[2]);

                // [1] CREATE INVOICE
                    $invoice = new C_invoices;
                    $invoice_response = $invoice->create(new Request, $invoices_inputs);

                    if($invoice_response['status']){
                        $invoice_id = $invoice_response['data']['invoice_id'];

                        // [2] CREATE BOOKING
                            $bookings_inputs->invoice_id = $invoice_id;
                            if($this->validation($this->create_required, $bookings_inputs)){

                                $booking = new M_default;
                                $booking->created_at = date("Y-m-d H:i:s");
                                foreach($bookings_inputs as $colname => $value){
                                    $booking->{$colname} = $value;
                                }
                        
                                if($booking->save()){
                                    $booking_id = $booking['booking_id'];

                                    // [3] CREATE BOOKING SERVICE
                                        $booking_service = new C_booking_services;
                                        $booking_services_inputs->booking_id = $booking_id;
                                        $booking_service_response = $booking_service->create(new Request, $booking_services_inputs);

                                        if($booking_service_response['status']){

                                            // [4] CREATE DISCOUNT REDEEMS (IF ANY)
                                            if($reqs->input('m_discount_redeems') != NULL){
                                                $discount_redeems_inputs = (object) $reqs->input('m_discount_redeems');
                                                $discount_redeems_inputs->booking_id = $booking_id;

                                                $discount_redeems = new C_discount_redeems;
                                                $discount_redeems->create(new Request, $discount_redeems_inputs);
                                            }
                                            // RETURN BOOKING CREATE SUCCESS REGARDLESS
                                            $response = $this->get(new Request, $booking['booking_id'], true);

                                        }else{
                                            $response["status"] = False;
                                            $response["msg"] = $booking_service_response['msg'];
                                            $response["debug"] = $booking_service_response['debug'];
                                        }
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = "Problem occured. Please try again";
                                    $response["debug"] = "Cannot update booking status from database";
                                }
        
                            }else{
                                $response["status"] = False;
                                $response["msg"] = "Missing parameters";
                                $response["debug"] = "Missing parameters";
                            }

                    }else{
                        $response["status"] = False;
                        $response["msg"] = $invoice_response['msg'];
                        $response["debug"] = $invoice_response['debug'];
                    }
                    

            }else{
                    
                if($reqs->input('apicall') != NULL){
                    if($this->validation(array('updated_by'), $reqs)){
                            
                        $cdata = M_default::find($id);

                        foreach($reqs->input() as $colname => $value){
                            if($colname != 'apicall'){
                                $cdata->{$colname} = $value;
                            }
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
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing parent parameters";
                }
            }

            return response()->json($response);
        
        }

    // [POST] api/booking/service <-- Create new row
        function create_service(Request $reqs){

            $response = array();

            if($this->validation($this->create_booking_service_chains, $reqs)){

                $invoices_inputs = (object) $reqs->input($this->create_booking_service_chains[0]);
                $bookings_inputs = (object) $reqs->input($this->create_booking_service_chains[1]);
                $booking_services_inputs = (object) $reqs->input($this->create_booking_service_chains[2]);

                // [1] CREATE INVOICE
                    $invoice = new C_invoices;
                    $invoice_response = $invoice->create(new Request, $invoices_inputs);

                    if($invoice_response['status']){
                        $invoice_id = $invoice_response['data']['invoice_id'];

                        // [2] CREATE BOOKING
                            $bookings_inputs->invoice_id = $invoice_id;
                            if($this->validation($this->create_required, $bookings_inputs)){

                                $booking = new M_default;
                                $booking->created_at = date("Y-m-d H:i:s");
                                foreach($bookings_inputs as $colname => $value){
                                    $booking->{$colname} = $value;
                                }
                        
                                if($booking->save()){
                                    $booking_id = $booking['booking_id'];

                                    // [3] CREATE BOOKING SERVICES
                                        $booking_service = new C_booking_services;
                                        $booking_services_inputs->booking_id = $booking_id;
                                        $booking_service_response = $booking_service->create(new Request, $booking_services_inputs);

                                        if($booking_service_response['status']){

                                            // [4] CREATE DISCOUNT REDEEMS (IF ANY)
                                            if($reqs->input('m_discount_redeems') != NULL){
                                                $discount_redeems_inputs = (object) $reqs->input('m_discount_redeems');
                                                $discount_redeems_inputs->booking_id = $booking_id;

                                                $discount_redeems = new C_discount_redeems;
                                                $discount_redeems->create(new Request, $discount_redeems_inputs);
                                            }
                                            // RETURN BOOKING CREATE SUCCESS REGARDLESS
                                            $response = $this->get_service(new Request, $booking['booking_id'], true);

                                        }else{
                                            $response["status"] = False;
                                            $response["msg"] = $booking_service_response['msg'];
                                            $response["debug"] = $booking_service_response['debug'];
                                        }
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = "Problem occured. Please try again";
                                    $response["debug"] = "Cannot update booking status from database";
                                }
        
                            }else{
                                $response["status"] = False;
                                $response["msg"] = "Missing parameters";
                                $response["debug"] = "Missing parameters";
                            }

                    }else{
                        $response["status"] = False;
                        $response["msg"] = $invoice_response['msg'];
                        $response["debug"] = $invoice_response['debug'];
                    }
                    

            }else{
                    
                if($reqs->input('apicall') != NULL){
                    if($this->validation(array('updated_by'), $reqs)){
                            
                        $cdata = M_default::find($id);

                        foreach($reqs->input() as $colname => $value){
                            if($colname != 'apicall'){
                                $cdata->{$colname} = $value;
                            }
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
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing parent parameters";
                }
            }

            return response()->json($response);
        
        }

/************* GET/READ  *************/

    // [GET] api/bookings <-- Get all lists
        function list(Request $reqs){  
            $response = array();
            $paginate = ($reqs->input('paginate') == 'disable') ? false : true;;
            $cdata = M_default::where('m_bookings.status','<>','0');

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $cdata->where($filters);
            }
            
            $cdata->leftJoin('m_booking_statuses','m_bookings.status','m_booking_statuses.booking_status_id')

            ->leftJoin('m_invoices','m_bookings.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')
            
            ->leftJoin('m_customers','m_bookings.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')
            ->select(

                //bookings
                'm_bookings.*',
                'm_booking_statuses.status_name',

                //Payment
                'm_invoices.billing_address as billing_address',
                'm_payments.ref_id as payment_ref_id',
                'm_payments.notes as payment_notes',
                'm_payment_methods.method_name as payment_method_name',

                //Customer
                'm_customers.user_id as customer_user_id',
                'm_users.email as customer_email',
                'm_user_details.fullname as customer_fullname',
            );

            if($paginate){
                $cdata = $cdata->paginate(10);
            }else{
                $cdata = $cdata->get();
            }

            if($cdata){
            // INJECT BOOKING SERVICES DETAILS
                $bookings = $cdata->toArray();
                $bookingsData = ($paginate) ? $cdata->toArray()['data'] : $cdata->toArray();

                if(count($bookingsData) > 0){
                    foreach($bookingsData as $bookingKey => $booking){
                        $priceTotal = 0;

                        $booking_id = $booking['booking_id'];
                        $cdata2 = M_booking_services::where([
                            ['m_booking_services.booking_id','=',$booking_id]
                        ])
                        ->leftJoin('m_service_details', 'm_booking_services.service_detail_id', 'm_service_details.service_detail_id')
                        ->leftJoin('m_services', 'm_service_details.service_id', 'm_services.service_id')
                        ->leftJoin('m_reviewers', 'm_service_details.created_by', 'm_reviewers.user_id')
                        ->leftJoin('m_user_details','m_reviewers.user_id','m_user_details.user_id')
                        ->leftJoin('m_images','m_service_details.featured_img','=','m_images.image_id')
                        ->select(
                            //services
                            'm_service_details.*',
                            'm_services.name',

                            //Reviewer
                            'm_reviewers.reviewer_id',
                            'm_user_details.fullname as reviewer_fullname',

                            // Booking Services
                            'm_booking_services.price as price_sold',

                            //Images
                            'm_images.url as featured_img_url',
                            'm_images.alt as featured_img_alt',
                        )
                        ->get();

                        $booking_services = $cdata2->toArray();

                        foreach($booking_services as $booking_serviceKey => $booking_service){

                            // SET SERVICE ACTUAL PRICE SOLD
                            $priceSold = ($booking_services[$booking_serviceKey]['price_sold'] == NULL) ? $booking_services[$booking_serviceKey]['normal_price'] : $booking_services[$booking_serviceKey]['price_sold'];
                            $priceSold = $priceSold * 1;

                            // DEFINE SERVICE PRICE & TOTALS
                            $booking_services[$booking_serviceKey]['price_sold'] = $priceSold;

                            $priceTotal += $priceSold;
                        }

                    // INJECT DISCOUNTS
                    
                        $discountTotal = 0;
                        if($paginate){
                            $bookings['data'][$bookingKey]['discount_id'] = NULL;
                        }else{
                            $bookings[$bookingKey]['discount_id'] = NULL;
                        }
                        $cdata5 = M_discount_redeems::where([
                            ['m_discount_redeems.booking_id','=',$booking_id]
                        ])
                        ->leftJoin('m_discounts', 'm_discount_redeems.discount_id', 'm_discounts.discount_id')
                        ->select(
                            //Discounts
                            'm_discount_redeems.discount_redeem_id',
                            'm_discounts.discount_id',
                            'm_discounts.name',
                            'm_discounts.type',
                            'm_discounts.value',
                        )
                        ->first();
                        if($cdata5){
                            $discount_redeems = $cdata5->toArray();
                            if($paginate){
                                $bookings['data'][$bookingKey]['discount_id'] = $discount_redeems['discount_id'];
                            }else{
                                $bookings[$bookingKey]['discount_id'] = $discount_redeems['discount_id'];
                            }
                            if($discount_redeems['type'] == 2){
                                $discountTotal += $priceTotal * ($discount_redeems['value']/100);
                            }else{
                                $discountTotal += $discount_redeems['value'];
                            }
                        } 

                    // SUMMARY
                        if($paginate){
                            $bookings['data'][$bookingKey]['total_discount'] = $discountTotal;
                            $bookings['data'][$bookingKey]['total_price'] = $priceTotal - $discountTotal;
                            $bookings['data'][$bookingKey]['grand_total_price'] = number_format((float)($priceTotal - $discountTotal), 2, '.', '');
                            $bookings['data'][$bookingKey]['services'] = $booking_services;
                        }else{
                            $bookings[$bookingKey]['total_price'] = $priceTotal - $discountTotal;
                            $bookings[$bookingKey]['grand_total_price'] = number_format((float)($priceTotal - $discountTotal), 2, '.', '');
                            $bookings[$bookingKey]['services'] = $booking_services;
                        }
                    }
                }

            $cdata = $bookings;
            }

            $response["status"] = True;
            $response["data"] = $cdata;

            return response()->json($response);
        }



    // [GET] api/booking/{id} <-- Get specific row
        function get(Request $reqs, $id, $direct=false){
            $response = array();

            $cdata = M_default::where($this->module.'_id', '=', $id);

            $filters = ($reqs->input('filters') != NULL) ? json_decode($reqs->input('filters')) : array();
            $orfilters = json_decode($reqs->input('orfilters'));
            if($orfilters != NULL){
                $cdata->where(function ($q) use ($filters, $orfilters){
                    $q->where($filters);
                    foreach($orfilters as $orfilter){
                        $q->orWhere([$orfilter]);
                    }
                });
            }else{
                $cdata->where($filters);
            }

            $cdata->leftJoin('m_booking_statuses','m_bookings.status','m_booking_statuses.booking_status_id')

            ->leftJoin('m_invoices','m_bookings.invoice_id','m_invoices.invoice_id')
            ->leftJoin('m_payments','m_invoices.payment_id','m_payments.payment_id')
            ->leftJoin('m_payment_methods','m_payments.payment_method_id','m_payment_methods.payment_method_id')
            
            ->leftJoin('m_customers','m_bookings.customer_id','m_customers.customer_id')
            ->leftJoin('m_user_details','m_customers.user_id','m_user_details.user_id')
            ->leftJoin('m_users','m_customers.user_id','m_users.user_id')
            ->leftJoin('m_images','m_user_details.propic','=','m_images.image_id')
            ->select(

                //bookings
                'm_bookings.*',
                'm_booking_statuses.status_name',

                //Payment
                'm_invoices.billing_address as billing_address',
                'm_invoices.status as payment_status',
                'm_invoices.invoice_id as invoice_id',
                'm_invoices.payment_id as invoice_payment_id',
                'm_invoices.email as invoice_email',
                'm_payments.ref_id as payment_ref_id',
                'm_payments.notes as payment_notes',
                'm_payments.total as payment_total',
                'm_payment_methods.payment_method_id as payment_method_id',
                'm_payment_methods.method_name as payment_method_name',

                //Customer
                'm_customers.user_id as customer_user_id',
                'm_users.email as customer_email',
                'm_user_details.fullname as customer_fullname',
                'm_user_details.propic as customer_propic',
                'm_user_details.phone as customer_phone',
                'm_user_details.country_id as customer_country_id',
                'm_user_details.city_id as customer_city_id',
                'm_user_details.state_id as customer_state_id',
                'm_user_details.address1 as customer_address1',
                'm_user_details.address2 as customer_address2',
                'm_user_details.postcode as customer_postcode',
                'm_images.url as customer_propic_url',
                'm_images.alt as customer_propic_alt'

            );

            $cdata = $cdata->first();

            if($cdata){
            // INJECT BOOKING SERVICES DETAILS
                $booking = $cdata->toArray();
                $bookingData = $cdata->toArray();

                $priceTotal = 0;
                $weightTotal = 0;

                $booking_id = $booking['booking_id'];
                $cdata2 = M_booking_services::where([
                    ['m_booking_services.booking_id','=',$booking_id]
                ])
                ->leftJoin('m_service_details', 'm_booking_services.service_detail_id', 'm_service_details.service_detail_id')
                ->leftJoin('m_services', 'm_service_details.service_id', 'm_services.service_id')
                ->leftJoin('m_reviewers', 'm_service_details.created_by', 'm_reviewers.user_id')
                ->leftJoin('m_user_details','m_reviewers.user_id','m_user_details.user_id')
                ->leftJoin('m_images','m_service_details.featured_img','=','m_images.image_id')
                ->select(
                    //Services
                    'm_service_details.*',
                    'm_services.name',

                    //Reviewer
                    'm_reviewers.reviewer_id',
                    'm_user_details.fullname as reviewer_fullname',

                    // Booking Services
                    'm_booking_services.booking_service_id as booking_service_id',
                    'm_booking_services.price as price_sold',

                    //Images
                    'm_images.url as featured_img_url',
                    'm_images.alt as featured_img_alt',
                )
                ->get();

                $booking_services = $cdata2->toArray();

                foreach($booking_services as $booking_serviceKey => $booking_service){

                    // GET FEATURED IMAGE
                        $featured_img_url = $booking_services[$booking_serviceKey]['featured_img_url'];
                        $featured_img_alt = $booking_services[$booking_serviceKey]['featured_img_alt'];

                    // SET SERVICE ACTUAL PRICE SOLD
                        $priceSold = ($booking_services[$booking_serviceKey]['price_sold'] == NULL) ? $booking_services[$booking_serviceKey]['normal_price'] : $booking_services[$booking_serviceKey]['price_sold'];
                        $priceSold = $priceSold * 1;

                    // DEFINE SERVICE PRICE & TOTALS
                        $booking_services[$booking_serviceKey]['price_sold'] = $priceSold;
                        $priceTotal = $priceTotal + $priceSold;

                    // INJECT SERVICE CATEGORIES
                        $service_id = $booking_service['service_id'];
                        $cdata4 = M_service_categories::where([
                            ['m_service_categories.service_id','=',$service_id]
                        ])
                        ->leftJoin('m_categories', 'm_service_categories.category_id', 'm_categories.category_id')
                        ->select(
                            'm_categories.category_id',
                            'm_categories.name',
                            'm_categories.descriptions',
                        )
                        ->get();
                        $service_categories = $cdata4->toArray();
                        $booking_services[$booking_serviceKey]['service_categories'] = $service_categories;
                           
                }

                $booking['services'] = $booking_services;

            // INJECT DISCOUNTS
                $discountTotal = 0;
                $booking['discount_id'] = NULL;
                $cdata5 = M_discount_redeems::where([
                    ['m_discount_redeems.booking_id','=',$id]
                ])
                ->leftJoin('m_discounts', 'm_discount_redeems.discount_id', 'm_discounts.discount_id')
                ->select(
                    //Discounts
                    'm_discount_redeems.discount_redeem_id',
                    'm_discounts.discount_id',
                    'm_discounts.name',
                    'm_discounts.type',
                    'm_discounts.value',
                )
                ->first();
                if($cdata5){
                    $discount_redeems = $cdata5->toArray();
                    $booking['discount_id'] = $discount_redeems['discount_id']; 

                    if($discount_redeems['type'] == 2){
                        $discountTotal += $priceTotal * ($discount_redeems['value']/100);
                    }else{
                        $discountTotal += $discount_redeems['value'];
                    }
                } 

            // SUMMARY
                $booking['total_discount'] = $discountTotal;
                $booking['total_price'] = $priceTotal;
                $booking['grand_total_price'] = $priceTotal - $discountTotal;

                $cdata = $booking;

                $response["status"] = True;
                $response["data"] = $cdata;
            }else{
                $response["status"] = False;
                $response["msg"] = "Problem occured. Please try again";
                $response["debug"] = "Cannot retrieve from database";
            }

            return ($direct) ? $response : response()->json($response);

        }



/************* UPDATE  *************/

    // [PUT] api/booking/{id} <-- Update specific row
        function update(Request $reqs, $id){
            $response = array();

            if($this->validation($this->update_booking_chains, $reqs)){

                $bookings_inputs = (object) $reqs->input($this->update_booking_chains[0]);
                $discount_redeems_inputs = (object) $reqs->input($this->update_booking_chains[1]);
                $booking_services_inputs = (object) $reqs->input($this->update_booking_chains[2]);

                if($this->validation(array('updated_by'), $bookings_inputs)){

                    // [1] UPDATE BASE booking
                        $bookings = M_default::find($id);
                        foreach($bookings_inputs as $colname => $value){
                            $bookings->{$colname} = $value;
                        }
                        $bookings->updated_at = date("Y-m-d H:i:s");
                        $bookings->save();

                        $bookingsData = M_default::where(
                            $this->module.'_id', '=', $id
                        )->first()->toArray();

                    // [2] UPDATE DISCOUNT REDEEMS
                        if(isset($discount_redeems_inputs->discount_id)){
                            $discount_redeems = new C_discount_redeems;
                            $discount_redeems_inputs->booking_id = $bookingsData['booking_id'];
                            $discount_redeems_inputs->customer_id = $bookingsData['customer_id'];
                            $discount_redeems_response = $discount_redeems->update(new Request, $bookingsData['booking_id'], $discount_redeems_inputs);
                        }else{
                            $discount_redeems_response = [
                                "status" => TRUE
                            ];
                        }
                        if($discount_redeems_response['status']){

                            // [3] UPDATE BOOKING SERVICES
                                $booking_services = new C_booking_services;
                                $booking_services_inputs->booking_id = $bookingsData['booking_id'];
                                $booking_services_inputs->customer_id = $bookingsData['customer_id'];
                                $booking_services_response = $booking_services->update(new Request, $bookingsData['booking_id'], $booking_services_inputs);
                                if($booking_services_response['status']){
                                    $response = $this->get(new Request, $bookingsData['booking_id'], true);
                                }else{
                                    $response["status"] = False;
                                    $response["msg"] = $booking_services_response['msg'];
                                    $response["debug"] = $booking_services_response['debug'];
                                }

                        }else{
                            $response["status"] = False;
                            $response["msg"] = $discount_redeems_response['msg'];
                            $response["debug"] = $discount_redeems_response['debug'];
                        }
                            
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing updated_by";
                }

            }else{
                
                if($reqs->input('apicall') != NULL){
                    if($this->validation(array('updated_by'), $reqs)){
                            
                        $cdata = M_default::find($id);

                        foreach($reqs->input() as $colname => $value){
                            if($colname != 'apicall'){
                                $cdata->{$colname} = $value;
                            }
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
                }else{
                    $response["status"] = False;
                    $response["msg"] = "Missing parameters";
                    $response["debug"] = "Missing parent parameters";
                }
            }

            return response()->json($response);
        }

    // [PUT] api/booking/restore/{id} <-- Restore deleted specific row
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

    // [DELETE] api/booking/{id} <-- SoftDelete specific row
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

    // [DELETE] api/booking/delete/{id} <-- Permanent delete specific row
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
