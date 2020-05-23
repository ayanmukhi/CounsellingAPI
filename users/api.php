<?php

namespace users;

use src\config\connection as dbconnect;
use src\validations as validate;
use src\authenticate as auth;
use src\config as config;
use src\config\responses as res;


class apiClass {

    //function to get all user records
    // public function getALL($request, $response) {
    //     $jwt = new config\jwt();
    //     $auth = new auth\authorize();

    //     // if( $auth->checkAdmin($request, $response) != "legit") {
    //     //     return $auth->checkAdmin($request, $response);
    //     // }

    //     //get the object of database connection
    //     $dbobj = new dbconnect\dbconnection();
    //     $fm = $dbobj->connect();

    //     //object from response class
    //     $res = new res\userResponses();
   
        
    //     //specify the layout
    //     $findCommand = $fm->newFindCommand('Signup_USER');
        
    //     //execute the find command to get all student records
    //     $result = $findCommand->execute();

    //     //checking for errors in the result
    //     if (\FileMaker::isError($result)) {
    //         if( $result->getMessage() == "No records match the request" ) {
    //             $newresponse = $response->withStatus(404);
    //             return $newresponse->withJson(['success'=>false, 'message'=>'database is empty']);
    //         }
    //         else {
    //             $newresponse = $response->withStatus(404);
    //             return $newresponse->withJson(['success'=>false, 'message'=>'server error']);
    //         }
    //     }

    //     $data = $res->getAllRecords($result->getrecords());

    //     $newresponse = $response->withStatus(200);
    //     return $newresponse->withJson(['success'=>true, 'data'=>$data]);
        
    // }

    //function to get all counselors details
    public function getAllCounselors($request, $response) {
        $jwt = new config\jwt();

        //get the object of database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //object from response class
        $res = new res\userResponses();
   
        
        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');
        
        //specify the role match criteria
        $findCommand->addFindCriterion('Role_t', "counselor");

        //execute the find command to get all student records
        $result = $findCommand->execute();

        //checking for errors in the result
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'database is empty']);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server error']);
            }
        }
        // print_r($result->getrecords() );
        // exit(0);
        $data = $res->getAllRecords($result->getrecords());

        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true, 'data'=>$data]);
        
    }

    //function to get contact of user
    // public function getContactDetails($id) {
    //     $jwt = new config\jwt();

    //     //get the object of database connection
    //     $dbobj = new dbconnect\dbconnection();
    //     $fm = $dbobj->connect();

    //     //object from response class
    //     $res = new res\userResponses();
   
        
    //     //fetch contact details.
    //     $findCommand = $fm->newFindCommand('InsertUserContact_CONTACT');

    //     //specify the email and password match criteria
    //     $findCommand->addFindCriterion('_kf_Id_n', ' == '.$id);


    //     $contact;

    //     //execute the above command
    //     $contactResult = $findCommand->execute(); 

        

    //     //checking for errors in the result
    //     if (\FileMaker::isError($contactResult)) {
    //         if( $contactResult->getMessage() == "No records match the request" ) {
    //             return NULL;
    //         }
    //         $contactResultError = 'Find Error: '. $contactResult->getMessage(). ' (' . $contactResult->code. ')';
    //         return $contactResultError;
            
    //     }
    //     return $contactResult->getRecords();
        
    // }


    // //function to get counselor availability
    // public function getCounselorAvailabilityDetails($id) {
    //     $jwt = new config\jwt();

    //     //get the object of database connection
    //     $dbobj = new dbconnect\dbconnection();
    //     $fm = $dbobj->connect();

    //     //object from response class
    //     $res = new res\userResponses();
   
        
    //     //specify the layout
    //     $findCommand = $fm->newFindCommand('CounselorAvailability_AVAILABILITY');
        
    //     //specify the role match criteria
    //     $findCommand->addFindCriterion('_kf_Id_n', $id);

    //     //execute the find command to get all student records
    //     $result = $findCommand->execute();

    //     //checking for errors in the result
    //     if (\FileMaker::isError($result)) {
    //         if( $result->getMessage() == "No records match the request" ) {
    //             return NULL;
    //         }
    //         $findError = 'Find Error: '. $result->getMessage(). ' (' . $result->code. ')';
    //         return "server error";
    //     }

    //     return $result->getrecords();
        
    // }
    
    //function to get a single user record
    public function get($request, $response, $args) {


        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();
        
        //object from response class
        $res = new res\userResponses();

        //request data validation objects
        $valid = new validate\validateFields();

        //token validation object
        $auth = new auth\authorize();

        if($valid->sicVal($args) != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, 'message'=>$valid->sicVal($args)]);
        }

        //User authentication based on jwt
        if( $auth->checkUser($request, $response, $args['id']) != "legit") {
            return $auth->checkUser($request, $response, $args['id']);
        }
        
        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('_kp_Id_n', ' == '.$args['id']);


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

        //get the FileMaker result set and changing it to proper response format
        $resFormat = $res->getresponse($result->getRecords()[0]);
        
        //response after successful record is fetched
        $newresponse = $response->withStatus(200);
        return $response->withJson(["success"=>true, "data"=>$resFormat]);
       
    }


    //function to insert a new user record
    public function insertNewUser($request, $response) {
        
        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //get the object of validating class
        $valid = new validate\validateFields();

        //object from response class
        $res = new res\userResponses();

        $vars = json_decode($request->getBody());

        //validating the data in body
        $return = $valid->valUserData($vars, "post", $request, $response);
        
       
        if ($return != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, "message"=>$return]);
        }

        $values = $res->insertUser($vars);
            
        //populating fields of student layout
        $rec = $fm->createRecord('Signup_User', $values);
        $insertResult = $rec->commit();

        //checking error populating fields in globalstudent layout
        if (\FileMaker::isError($insertResult)) {
            $findError = 'Find Error: '. $insertResult->getMessage(). ' (' . $insertResult->code. ')';
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, "UserRecordCreation"=>$findError, "length" => strlen($vars->image), "fields" => $values]);
        }

        //returning success response
        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true]);
    }


    //function to delete a user record
    public function deleteUser($request, $response, $args) {

        //get the object of authentication class
        $auth = new auth\authorize();

        //get the object of jwt class
        $jwt = new config\jwt();

        

        //check whether the user is legit to delete the record with the provided sic
        if( $auth->checkUser($request, $response, $args['id']) != "legit") {
            return $auth->checkUser($request, $response, $args['id']);
        }
        
        //get the object of the connection class
        $dbobj = new dbconnect\dbconnection();

        //get the FileMaker connection object
        $fm = $dbobj->connect();

        //set the layout for searching the id
        $findCommand = $fm->newFindCommand('Signup_USER');

        //specifying the matching criterian
        $findCommand->addFindCriterion('_kp_Id_n', '=='.$args['id']);

        //executing the find command
        $result = $findCommand->execute();

        //checking for errors for the above run command
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'record with given id doesnot exists']);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server error']);
            }
        }

        //get the record result set
        $record = $result->getRecords()[0]->_impl;
        // print_r($record);

        //delete the specific record
        $delete = $record->delete();
        
        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true]);
    }


    //function to update a user record
    public function updateUser($request, $response) {
        //object from response class
        $res = new res\userResponses();

        //object for token decyption class
        $jwt = new config\jwt();

        //onject for authenticating a user
        $auth = new auth\authorize();

        //validating class object
        $valid = new validate\validateFields();

        //coonection class object
        $dbobj = new dbconnect\dbconnection();

        $updContact = json_decode(\contact\apiClass::updateContact($request, $response));
        // print_r($updContact->success);
        // exit(0);
        if( $updContact->success == "false" ) {
            return $response->withJson(["success"=>false, "message"=>$updContact->message], 400);
        }

        //connection to get the filemaker connection object
        $fm = $dbobj->connect();

        //get the request body
        $vars = json_decode($request->getBody());
        
        //validating the request header data
        $return = $valid->valUserData($vars, "put", $request, $response);
        if($return != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, "message"=>$return]); 
        }
        

        //except admin none can update other records 
        if( $auth->checkUser($request, $response, $vars->id) != "legit") {
            return $auth->checkUser($request, $response, $vars->id);
        }

        //getting the new values in array format
        $values = $res->insertUser($vars);
        unset($values["Role_t"]);
        
        //commands to find the specific record
        $findCommand = $fm->newFindCommand('Signup_USER');
        $findCommand->addFindCriterion('_kp_Id_n', '=='.$vars->id);
        $result = $findCommand->execute();

        //checking for error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'record with given sic doesnot exists']);
            }
            else {
                $findError = $result->getMessage(). ' (' . $result->code. ')';
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, "userRecordSearchError"=>$findError]);
            }
        }

        //getting the specific record Id
        $record = $result->getRecords()[0]->_impl;
        $rec = $record->getRecordId();

        $newEdit = $fm->newEditCommand('Signup_USER', $rec, $values);
        $result = $newEdit->execute(); 

        //checking for any error
        if (\FileMaker::isError($result)) {
             $findError = $result->getMessage(). ' (' . $result->code. ')';
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, "userRecordUpdationError"=>$findError]);
            
        }

        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true]);

    }


    //function for login
    public function loginUser($request, $response) {
        $res = new res\userResponses();
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();
        $vars = json_decode($request->getBody());


        $valid = new validate\validateFields();

        if( $valid->loginVal($vars) != "no error") {
            //$newresponse = $response->withStatus(401);
            return $response->withJson(['success'=>false, 'message'=>$valid->loginVal($vars)], 401);
        }


        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('_ka_Username_t', '=='.$vars->username);
        $findCommand->addFindCriterion('Password_t', '=='.$vars->password);

        //execute the above command
        $result = $findCommand->execute(); 

        //checking for any error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'credentials are not valid']);
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server error']);
            }
        }
        
        //change the fetched data to proper response data.
        $resFormat = $res->loginResponse($result->getRecords());


        //response after successful record is fetched
        $newresponse = $response->withStatus(200);
        return $response->withJson(["success"=>true, "token"=>$resFormat['token']]);

    }
}