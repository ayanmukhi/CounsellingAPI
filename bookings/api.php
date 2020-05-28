<?php

namespace bookings;

use src\config\connection as dbconnect;
use src\authenticate as auth;
use src\config as config;
use src\config\responses as res;
use src\validations\availability as validate;

class apiClass
{


    //function to book a new counselor
    public function insretBooking( $request, $response) {
       //database connection
       $dbobj = new dbconnect\dbconnection();
       $fm = $dbobj->connect();

       $val = json_decode( $request->getBody() );
       
       $values = array (
           '_kf_Id_n' => $val->seekerId,
           'Date_d' => date("m/d/Y", strtotime($val->date)),
           '_kf_AvailabilityId_n' => $val->availabilityId 
       );

       
       //specify the layout
       $rec = $fm->createRecord('Bookings_BOOKINGS', $values); 
       $insertResult = $rec->commit();

       //checking error populating fields in globalstudent layout
       if(\FileMaker::isError($insertResult)) {
            $findError = 'Find Error: '. $insertResult->getMessage(). ' (' . $insertResult->code. ')';
            return $response->withJson(['success' => false, 'message' => $findError], 400);
        }

        return $response->withJson([ 'success' => true ], 200);
    }

    //function to get all counselor bookings
    public function getCounselorBookings($id, $counselor) {


        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //get the object of validating class
        $valid = new validate\validate();

        if($valid->sicVal($id) != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, 'message'=>$valid->sicVal($id)]);
        }
        
        //specify the layout
        $findCommand = $fm->newFindCommand('bookings_BOOKINGS');

        //specify the email and password match criteria
        if( $counselor ) {
            $findCommand->addFindCriterion('_kf_AvailabilityId_n', ' == '.$id);
        }
        else {
            $findCommand->addFindCriterion('_kf_Id_n', ' == '.$id);
        }
        


        //execute the above command
        $result = $findCommand->execute(); 

        //checking for any error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() != "No records match the request" ) {
                return 'server user error';
            }
            else {
                
                return null;
            }
        }      

        


        if( $counselor ) {
            return $result->getRecords()[0];
        }
        else {
            return $result->getRecords();
        }
            
       
    }

    //function to get all counselor bookings
    public function getAppointedUser($request, $response, $args) {


        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //get the object of validating class
        $valid = new validate\validate();

        $id = $args['id'];

        if($valid->sicVal($id) != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, 'message'=>$valid->sicVal($id)]);
        }
        
        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');
        $phoneFindCommand = $fm->newFindCommand('InsertUserContact_CONTACT');


        //specify the email and password match criteria
        $findCommand->addFindCriterion('_kp_Id_n', ' == '.$id);
        $phoneFindCommand->addFindCriterion('_kf_Id_n', ' == '.$id);


        //execute the above command
        $result = $findCommand->execute(); 

        //checking for any error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'no record exist with this id']);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server user error']);
            }
        }



        $phoneResult = $phoneFindCommand->execute(); 
        $phone;
        //checking for any error
        if (\FileMaker::isError($phoneResult)) {
            if( $phoneResult->getMessage() == "No records match the request" ) {
                $user = array(
                    'clientImage' => $result->getRecords()[0]->_impl->_fields['ImageFileRef_t'][0],
                    'clientName' => $result->getRecords()[0]->_impl->_fields['Name_t'][0],
                    'clientGender' => $result->getRecords()[0]->_impl->_fields['Gender_t'][0],
                    'clientEmail' => $result->getRecords()[0]->_impl->_fields['_ka_Username_t'][0],
                    'clientPhone' => null
                );
                return $response->withJson(['success'=>false, 'data'=>$user], 200);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server user error']);
            }
        }   
        
        

        $user = array(
            'clientImage' => $result->getRecords()[0]->_impl->_fields['ImageFileRef_t'][0], 
            'clientName' => $result->getRecords()[0]->_impl->_fields['Name_t'][0],
            'clientGender' => $result->getRecords()[0]->_impl->_fields['Gender_t'][0],
            'clientEmail' => $result->getRecords()[0]->_impl->_fields['_ka_Username_t'][0],
            'clientPhone' => $phoneResult->getRecords()[0]->_impl->_fields['_ku_Phone_n'][0]
        );
        return $response->withJson(['success'=>false, 'data'=>$user], 200);
        
        
            
       
    }


    //function to get details of booked counselor
    public function getBookedCounselor($request, $response, $args) {


        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //get the object of validating class
        $valid = new validate\validate();

        $id = $args['id'];

        if($valid->sicVal($id) != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, 'message'=>$valid->sicVal($id)]);
        }
        

        $counselorFindCommand = $fm->newFindCommand('CounselorAvailability_AVAILABILITY');
        $counselorFindCommand->addFindCriterion('_kp_AvailabilityId_n', '=='.$id);
        $counselorResult = $counselorFindCommand->execute();

        //checking for any error
        if (\FileMaker::isError($counselorResult)) {
            if( $counselorResult->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'no record exist with this id']);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server user error']);
            }
        }

        // print_r($counselorResult->getRecords()[0]);
        $id = $counselorResult->getRecords()[0]->_impl->_fields['_kf_Id_n'][0];
        $location = $counselorResult->getRecords()[0]->_impl->_fields['Location_t'][0];
        $type = $counselorResult->getRecords()[0]->_impl->_fields['Type_t'][0];
        $time = $counselorResult->getRecords()[0]->_impl->_fields['Time_t'][0];

        // echo $rec;
        // exit();

        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');
        $phoneFindCommand = $fm->newFindCommand('InsertUserContact_CONTACT');


        //specify the email and password match criteria
        $findCommand->addFindCriterion('_kp_Id_n', ' == '.$id);
        $phoneFindCommand->addFindCriterion('_kf_Id_n', ' == '.$id);


        //execute the above command
        $result = $findCommand->execute(); 

        //checking for any error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'no record exist with this id']);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server user error']);
            }
        }



        $phoneResult = $phoneFindCommand->execute(); 
        $phone;
        //checking for any error
        if (\FileMaker::isError($phoneResult)) {
            if( $phoneResult->getMessage() == "No records match the request" ) {
                $user = array(
                    'clientImage' => $result->getRecords()[0]->_impl->_fields['ImageFileRef_t'][0],
                    'clientName' => $result->getRecords()[0]->_impl->_fields['Name_t'][0],
                    'clientGender' => $result->getRecords()[0]->_impl->_fields['Gender_t'][0],
                    'clientEmail' => $result->getRecords()[0]->_impl->_fields['_ka_Username_t'][0],
                    'clientLocation' => $location,
                    'clientType' => $type,
                    'clientTime' => $time,
                    'clientPhone' => null
                );
                return $response->withJson(['success'=>false, 'data'=>$user], 200);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server user error']);
            }
        }   
        
        

        $user = array(
            'clientImage' => $result->getRecords()[0]->_impl->_fields['ImageFileRef_t'][0], 
            'clientName' => $result->getRecords()[0]->_impl->_fields['Name_t'][0],
            'clientGender' => $result->getRecords()[0]->_impl->_fields['Gender_t'][0],
            'clientEmail' => $result->getRecords()[0]->_impl->_fields['_ka_Username_t'][0],
            'clientPhone' => $phoneResult->getRecords()[0]->_impl->_fields['_ku_Phone_n'][0],
            'clientLocation' => $location,
            'clientType' => $type,
            'clientTime' => $time,
        );
        return $response->withJson(['success'=>false, 'data'=>$user], 200);      
       
    }


}