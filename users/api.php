<?php

namespace users;

use src\config\connection as dbconnect;
use src\validations as validate;
use src\authenticate as auth;
use src\config as config;
use src\config\responses as res;
use src\config as jwtns;


class apiClass {

    

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


        $data = $res->getAllRecords($result->getrecords());
        // $data = apiClass::CounselorProfileData( $result->getRecords());
        // print_r ( $data);
        // exit(0);
        

        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true, 'data'=>$data]);
        
    }
    

    
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
        $record = ($result->getRecords())[0];  

        //get the FileMaker result set and changing it to proper response format
        $resFormat = $res->getresponse($record);

        
        //fetching data from contact portal
        $contacts = $record->getRelatedSet('user_CONTACT_id');
        
        //checking if the counselor have a contact or not
        if (gettype($contacts) == 'array') {
            $contact = \contact\apiClass::getContactFromPortal($contacts);
            $resFormat['contact'] = $contact;
            return $response->withJson(["success"=>true, "data"=>$resFormat], 200);
        }
        $newresponse = $response->withStatus(200);
        return $response->withJson(["success"=>true, "data"=>$resFormat], 200);
                 
       
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

        //get the request body
        $vars = json_decode($request->getBody());
        // print_r($vars);

        $updContact = json_decode(\contact\apiClass::updateContact($request, $response));
        // print_r($updContact->success);
        // exit(0);
        if( $updContact->success == "false" ) {
            return $response->withJson(["success"=>false, "message"=>$updContact->message], 400);
        }

        //connection to get the filemaker connection object
        $fm = $dbobj->connect();

        

        //validating the request header data
        $return = $valid->valUserData($vars, "put", $request, $response);
        if($return != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, "mese"=>$return]); 
        }
        

        //except admin none can update other records 
        if( $auth->checkUser($request, $response, $vars->id) != "legit") {
            return $auth->checkUser($request, $response, $vars->id);
        }

        
        //commands to find the specific record
        $findCommand = $fm->newFindCommand('Signup_USER');
        $findCommand->addFindCriterion('_kp_Id_n', '=='.$vars->id);
        $result = $findCommand->execute();

        //checking for error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'record with given id doesnot exists']);
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

        // $newEdit = $fm->newEditCommand('Signup_USER', $rec, $values);
        // $result = $newEdit->execute(); 

        //changing angular date format to FileMaker date format
        $dob = date("m/d/Y", strtotime($vars->dob));

        $rec = $fm->getRecordById('Signup_USER', $rec_ID);
        $rec->setField('Name_t', $vars->name);
        $rec->setField('Dob_d', $dob);
        $rec->setField('Gender_t', $vars->gender);
        $rec->setField('_ka_Username_t', $vars->username);

        $check =  property_exists($vars, "image");
        if( $check ) {
            $rec->setField('Image_t', $vars->image);
        }


        $result = $rec->commit();

        //checking for any error
        if (\FileMaker::isError($result)) {
            // print_r( $result);
             $findError = $result->getMessage(). ' (' . $result->code. ')';
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, "userRecordUpdationError"=>$findError, "fields" => $values, "record Id" => $rec]);
            
        }

        //run a script in FM
        $scripts = $fm->listScripts();
        $newPerformScript = $fm->newPerformScriptCommand('Signup_USER', $scripts[4]);
        $scriptResult = $newPerformScript->execute(); 
        if (\FileMaker::isError($scriptResult)) {
            
                $findError = $scriptResult->getMessage(). ' (' . $scriptResult->code. ')';
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, "runningScriptError"=>$findError]);
        
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

    
    // function to get data of all a counselors
    public function getCounselorDetails() {
        
        //coonection class object
        $dbobj = new dbconnect\dbconnection();

        //varriable for returning in json format
        $ret = [];

        //connection to get the filemaker connection object
        $fm = $dbobj->connect();

        
        //declaring temporary varriables to store data for each record temporarily
        $allData = [];
        $availability = [];
        $contact = null;
        $dob = "";

        //commands to find the specific record
        $findCommand = $fm->newFindCommand('counselorFetchData_USER');
        $findCommand->addFindCriterion('Role_t', 'counselor');
        $result = $findCommand->execute();

        

        //checking for error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $ret['success'] = false;
                $ret['error'] = 'no record with this availability is exist';
                return $ret;
            }
            else {
                $findError = $result->getMessage(). ' (' . $result->code. ')';
                $ret['success'] = false;
                $ret['error'] = $findError;
                return $ret;
                
            }
        }

        $records = $result->getRecords();
        

        foreach( $records as $record) {
            
            //fetching the data from availability portal
            $availabilities = $record->getRelatedSet('user_AVAILABILITY_id');
            
            //checking whether the counselor have a availability or not
            if (gettype($availabilities) == 'array') {
                $availability = \availability\apiClass::getAvailabilityFromPortal($availabilities);
            }
            else {
                $availability = null;
            }


            //fetching data from contact portal
            $contacts = $record->getRelatedSet('user_CONTACT_id');

            //checking if the counselor have a contact or not
            if (gettype($contacts) == 'array') {
                $contact = \contact\apiClass::getContactFromPortal($contacts);
            }
            else {
                $contact = null;
            }           

            //checking date value before formatting
            if ( $record->_impl->_fields['Dob_d'][0] != "" ) {
                $dob = date("Y-m-d", strtotime($record->_impl->_fields['Dob_d'][0]));
            }

            array_push( $allData , array(
                'id' => $record->_impl->_fields['_kp_Id_n'][0],
                'name' => $record->_impl->_fields['Name_t'][0],
                'gender' => $record->_impl->_fields['Gender_t'][0],
                'dob' => $dob,
                'username' => $record->_impl->_fields['_ka_Username_t'][0],
                'password' => $record->_impl->_fields['Password_t'][0],
                'image' => $record->_impl->_fields['ImageFileRef_t'][0],
                'contact' => $contact ,
                'availability' => $availability,
            ));
            
            
        }
        
        // $portalData = apiClass::getAvailabilityFromPortal($record);
        $ret['success'] = true;
        $ret['data'] = $allData;
        return $ret;
    }



    

    



}