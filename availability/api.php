<?php

namespace availability;

use src\config\connection as dbconnect;
use src\authenticate as auth;
use src\config as config;
use src\config\responses as res;
use src\validations\availability as validate;

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


    //function to get all availability records
    function getAvailability($request, $response, $args) {


        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();
        
        //object from response class
        $res = new res\availabilityResponses();

        //request data validation objects
        $valid = new validate\validate();

        //token validation object
        $auth = new auth\authorize();

        if($valid->sicVal($args) != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, 'message'=>$valid->sicVal($args)]);
        }

        //User authentication based on jwt
        if( $auth->checkUser($request, $response, $args['counselor_id']) != "legit") {
            return $auth->checkUser($request, $response, $args['counselor_id']);
        }
        
        //specify the layout
        $findCommand = $fm->newFindCommand('CounselorAvailability_AVAILABILITY');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('_kf_Id_n', ' == '.$args['counselor_id']);


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
                return $newresponse->withJson(['success'=>false, 'message'=>'server error']);
            }
        }

        //get the FileMaker result set and changing i to proper response format
        $resFormat = $res->getResponse($result->getRecords());

        
        //response after successful record is fetched
        $newresponse = $response->withStatus(200);
        return $response->withJson(["success"=>true, "data"=>$resFormat]);


        print_r($args);
    }


    //function delete a availability record
    public function deleteAvailability($request, $response, $args) {

        //get the object of authentication class
        $auth = new auth\authorize();

        //get the object of jwt class
        $jwt = new config\jwt();

        //check whether the user is legit to delete the record with the provided sic
        if( $auth->checkUser($request, $response, $args['availability_id']) != "legit") {
            return $auth->checkUser($request, $response, $args['availability_id']);
        }
        
        //get the object of the connection class
        $dbobj = new dbconnect\dbconnection();

        //get the FileMaker connection object
        $fm = $dbobj->connect();

        //set the layout for searching the id
        $findCommand = $fm->newFindCommand('CounselorAvailability_AVAILABILITY');

        //specifying the matching criterian
        $findCommand->addFindCriterion('_kp_AvailabilityId_n', '=='.$args['availability_id']);

        //executing the find command
        $result = $findCommand->execute();

        //checking for errors for the above run command
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'record with given id doesnot exists']);
            }
            $findError = $result->getMessage(). " ( " . $result->getCode() . " ) ";
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, 'message'=>$findError]);
        
        }

        //get the record result set
        $record = $result->getRecords()[0]->_impl;

        //delete the specific record
        $delete = $record->delete();
        
        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true]);
    }

}