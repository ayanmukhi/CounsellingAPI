<?php

namespace availability;

// require_once 'D:/PHPMailer-master';

use src\config\connection as dbconnect;
use src\authenticate as auth;
use src\config as config;
use src\config\responses as res;
use src\validations\availability as validate;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class apiClass {

    //function to create new record of availability table
    function insertNewAvailability($request, $response) {
        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //get the object of validating class
        $valid = new validate\validate();

        //object from response class
        $res = new res\availabilityResponses();

        $vars = json_decode($request->getBody());

        //validating the data in body
        $return = $valid->valAvailabilityData($vars, "post", $request, $response);
        
       
        if ($return != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, "message"=>$return]);
        }

        $values = $res->insertResponse($vars);

        //populating fields of student layout
        $rec = $fm->createRecord('CounselorAvailability_AVAILABILITY', $values);
        $insertResult = $rec->commit();

        //checking error populating fields in globalstudent layout
        if (\FileMaker::isError($insertResult)) {
            $findError = 'Find Error: '. $insertResult->getMessage(). ' (' . $insertResult->code. ')';
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, "AvailabilityRecordCreation"=>$findError, "fields" => $values]);
        }
 


        return $response->withJson(["success" => true], 200);
    }

    function updateAvailability( $request, $response) {

        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //get the object of validating class
        $valid = new validate\validate();

        //object from response class
        $res = new res\availabilityResponses();

        $vars = json_decode($request->getBody());

        //validating the data in body
        $return = $valid->valAvailabilityData($vars, "put", $request, $response);
        
       
        if ($return != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, "message"=>$return]);
        }

        $findCommand = $fm->newFindCommand("CounselorAvailability_AVAILABILITY");
        $findCommand->addFindCriterion('_kp_AvailabilityId_n', "==".$vars->availId);
        $result = $findCommand->execute();

        if( \FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'record with given availability ID doesnot exists']);
            }
            else {
                $findError = $result->getMessage(). ' (' . $result->code. ')';
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, "userRecordSearchError"=>$findError]);
            }
        }

        //getting the specific record Id
        $record = $result->getRecords()[0]->_impl;
        $rec_ID = $record->getRecordId();

        $rec = $fm->getRecordById('CounselorAvailability_AVAILABILITY', $rec_ID);
        $rec->setField('_kf_Id_n', $vars->counselor_id);
        $rec->setField('Type_t', $vars->type);
        $rec->setField('Time_t', $vars->time);
        $rec->setField('Location_t', $vars->location);
        $days = "";
        foreach( $vars->day as $day) {
            $days = $days . $day . "\n" ; 
        }
        // echo $days;
        $rec->setField('Day_t', $days);


        $resultUpdate = $rec->commit();
        
        //checking for any error
        if (\FileMaker::isError($resultUpdate)) {
            
             $findError = $resultUpdate->getMessage(). ' (' . $resultUpdate->code. ')';
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, "avaialabilityRecordUpdationError"=>$findError, "record Id" => $rec]);
            
        }

        return $response->withJson(['success' => true], 200);

    }


    // function to format the records fetched from the availabilty portal
    function getAvailabilityFromPortal( $portalRecods ) {

        $portalData = [];
        foreach( $portalRecods as $record) {

            $val = $record->_impl->_fields;


            $day = explode("\n", $val['user_AVAILABILITY_id::Day_t'][0]);
            
            $availId = $val['user_AVAILABILITY_id::_kp_AvailabilityId_n'][0];
            $bookigs = json_decode(apiClass::getCounselorAvailabilityDetailsFromPortal( $availId ));
            if( $bookigs->success) {
                $bookingsData = $bookigs->data;
            }
            else {
                $bookingsData = $bookigs->error;
            }

            array_push ( $portalData , array(
                'Type' => $val['user_AVAILABILITY_id::Type_t'][0],
                'Day' => $day,
                'Time' => $val['user_AVAILABILITY_id::Time_t'][0],
                'Rating' => $val['user_AVAILABILITY_id::Rating_n'][0],
                'Location' => $val['user_AVAILABILITY_id::Location_t'][0],
                'AvailabilityId' => $availId,
                'Bookings' => $bookingsData
            ));
        }
        return $portalData;
    }


    // function to get all data related to the specific avaialbility
    public function getCounselorAvailabilityDetailsFromPortal($id) {
        //coonection class object
        $dbobj = new dbconnect\dbconnection();

        //varriable for returning in json format
        $ret = [];

        //connection to get the filemaker connection object
        $fm = $dbobj->connect();

        // $findCommand = $fm->getLayout('CounselorBookingDetails_AVAILABILITY');


        //commands to find the specific record
        $findCommand = $fm->newFindCommand('CounselorBookingDetails_AVAILABILITY');
        $findCommand->addFindCriterion('_kp_AvailabilityId_n', '=='.$id);
        $result = $findCommand->execute();

        //checking for error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $ret['success'] = false;
                $ret['error'] = null;
                return json_encode($ret);
            }
            else {
                $findError = $result->getMessage(). ' (' . $result->code. ')';
                $ret['success'] = false;
                $ret['error'] = $findError;
                return json_encode($ret);
            }
        }

        // $currentLayout = $fm->getLayout('CounselorBookingDetails_AVAILABILITY');

        //getting the records from result object
        $record = $result->getRecords()[0];

        //getting the portal records
        $relatedSetsArray = ($record->getRelatedSet('BOOKINGS 2'));

        if (\FileMaker::isError($relatedSetsArray)) {
            if( $relatedSetsArray->getMessage() == "Related set \"BOOKINGS 2\" not present." ) {
                $ret['success'] = true;
                $ret['data'] = null;
                return json_encode($ret);
            }
            else {
                $findError = $relatedSetsArray->getMessage(). ' (' . $relatedSetsArray->code. ')';
                $ret['success'] = false;
                $ret['error'] = $findError;
                return json_encode($ret);
            }
        }

        $requiredVal = apiClass::getCounselorBookingsDetailsFromPortal( $relatedSetsArray );
        $ret['success'] = true;
        $ret['data'] = $requiredVal;
        return json_encode($ret);
    }


    //function to format counselor bookings data feteched from the bookings portal
    function getCounselorBookingsDetailsFromPortal( $data ) {
        $portalData = [];
        foreach ($data as $ele) {
            $val = $ele->_impl->_fields;
            array_push ( $portalData , array(
                // 'AvailabilityId' => $val['BOOKINGS 2::_kf_AvailabilityId_n'][0],
                'SeekerId' => $val['BOOKINGS 2::_kf_Id_n'][0],
                'Date' => $val['BOOKINGS 2::Date_d'][0]
            ));
        }

        return $portalData;
    }


    // function to get counselor availability
    public function getCounselorAvailabilityDetails($id) {
        $jwt = new config\jwt();

        //get the object of database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //object from response class
        $res = new res\userResponses();
   
        
        //specify the layout
        $findCommand = $fm->newFindCommand('CounselorAvailability_AVAILABILITY');
        
        //specify the role match criteria
        $findCommand->addFindCriterion('_kf_Id_n', $id);

        //execute the find command to get all student records
        $result = $findCommand->execute();

        //checking for errors in the result
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                return NULL;
            }
            $findError = 'Find Error: '. $result->getMessage(). ' (' . $result->code. ')';
            return "server error";
        }

        return $result->getrecords();
        
    }


    //function delete a availability record
    public function deleteAvailability($request, $response, $args) {

        //get the object of authentication class
        $auth = new auth\authorize();

        //get the object of jwt class
        $jwt = new config\jwt();


        $id = $args['id'];

        if( $request->hasHeader("Authorization") == false) {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["message"=>"required jwt token is not recieved"]);
        }

        $header = $request->getHeader("Authorization");
        $vars = substr($header[0],7);
        $token = json_decode($jwt->jwttokendecryption($vars));
        
        if ($token->verification == "failed") {
            $newresponse = $response->withStatus(401);
            return $newresponse->withJson(["message"=>"you are not authorized", "error"=>$token->msg]);
        }
        
        //get the object of the connection class
        $dbobj = new dbconnect\dbconnection();

        //get the FileMaker connection object
        $fm = $dbobj->connect();

        


        //commands to find the specific record
        $findCommand = $fm->newFindCommand('CounselorBookingDetails_AVAILABILITY');
        $findCommand->addFindCriterion('_kp_AvailabilityId_n', '=='.$id);
        $result = $findCommand->execute();

        //checking for error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $ret['success'] = false;
                $ret['error'] = null;
                return json_encode($ret);
            }
            else {
                $findError = $result->getMessage(). ' (' . $result->code. ')';
                $ret['success'] = false;
                $ret['error'] = $findError;
                return json_encode($ret);
            }
        }

        //getting the records from result object
        $record = $result->getRecords()[0];
        
        //get the record result set
        $recordId = $result->getRecords()[0]->_impl;

        // //delete the specific record
        $delete = $record->delete();


        $availabilityRecord = $record->_impl->_fields;

        //getting the portal records
        $relatedSetsArray = ($record->getRelatedSet('BOOKINGS 2'));

        if (\FileMaker::isError($relatedSetsArray)) {
            if( $relatedSetsArray->getMessage() == "Related set \"BOOKINGS 2\" not present." ) {
                $ret['success'] = true;
                $ret['data'] = null;
                return json_encode($ret);
            }
            else {
                $findError = $relatedSetsArray->getMessage(). ' (' . $relatedSetsArray->code. ')';
                $ret['success'] = false;
                $ret['error'] = $findError;
                return json_encode($ret);
            }
        }

        $requiredVal = apiClass::getCounselorBookingsDetailsFromPortal( $relatedSetsArray );
        $msg = apiClass::sendMail( $requiredVal, $availabilityRecord);
        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true, "mailMessage" => $msg]);
    }


    //function to send mail
    private function sendMail( $bookings, $availabilityRecord) {

        //get the object of the connection class
        $dbobj = new dbconnect\dbconnection();

        //get the FileMaker connection object
        $fm = $dbobj->connect();

        $counselorType = $availabilityRecord['Type_t'][0];
        $counselorId = $availabilityRecord['_kf_Id_n'][0];
        $counselorName = "";

        //seraching for counselor details
        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('_kp_Id_n', ' == '.$counselorId);


        //execute the above command
        $result = $findCommand->execute(); 

        //checking for any error
        if (\FileMaker::isError($result)) {
        }   
        else {
            $record = ($result->getRecords())[0];
            $counselorName = $record->_impl->_fields['Name_t'][0];
        } 

        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('_kp_Id_n', ' == '.$availabilityRecord['_kf_Id_n'][0]);


        //execute the above command
        $result = $findCommand->execute(); 

        //checking for any error
        if (\FileMaker::isError($result)) {
        }   
        else {
            $record = ($result->getRecords())[0];
            $counselorName = $record->_impl->_fields['Name_t'][0];
        } 

        //searching for seeker details

        foreach( $bookings as $booking ) {
            //specify the layout
            $findCommand = $fm->newFindCommand('Signup_USER');

            //specify the email and password match criteria
            $findCommand->addFindCriterion('_kp_Id_n', ' == '.$booking['SeekerId']);


            //execute the above command
            $result = $findCommand->execute(); 

            //checking for any error
            if (\FileMaker::isError($result)) {
            }   
            else {




                $record = ($result->getRecords())[0];
                $seekerName = $record->_impl->_fields['Name_t'][0];
                $seekerEmail = $record->_impl->_fields['_ka_Username_t'][0];

                 //sending mail
                 $to      = "ayan.mukhi@hotmail.com";
                 $subject = 'Cancellation of Appointment';
                 $message = 'hello ' . $seekerName . " due to some tragedy " . $counselorName . " has cancelled your booking of " . $counselorType ." cousneling on " . $booking['Date'] . " which is deeply regretted";
                 $headers = 'From: counselling@example.com' . "\r\n";

                

                $mail = new PHPMailer;

                $mail->isSMTP();                            // Set mailer to use SMTP
                $mail->Host = 'smtp.gmail.com';             // Specify main and backup SMTP servers
                $mail->SMTPAuth = true;                     // Enable SMTP authentication
                $mail->Username = 'ayan.mukhi50@gmail.com';          // SMTP username
                $mail->Password = 'mindfire@5'; // SMTP password
                $mail->SMTPSecure = 'tls';                  // Enable TLS encryption, `ssl` also accepted
                $mail->Port = 587;                          // TCP port to connect to

                $mail->setFrom('ayan.mukhi50@gmail.com', 'Counselling World');
                $mail->addReplyTo('info@example.com', 'Counseling Worl');
                $mail->addAddress($seekerEmail);   // Add a recipient
                

                $mail->isHTML(true);  // Set email format to HTML


                $mail->Subject = 'Cancellation of booking';
                $mail->Body    = $message;

                if(!$mail->send()) {
                    return 'Message could not be sent.' . '   Mailer Error: ' . $mail->ErrorInfo;
                } else {
                    //get the record result set
                    $recordId = $result->getRecords()[0]->_impl;

                    // //delete the specific record
                    $delete = $record->delete();
                    return 'Message has been sent';
                }
            } 
        }
    }

}