<?php

header ("Access-Control-Allow-Origin: http://localhost:4200");
header ("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header ("Access-Control-Allow-Headers: origin, x-requested-with, content-type, authorization");

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


use src\config\connection as dbconnect;
use src\validations as validate;
use src\authenticate as auth;
use src\config as config;
use src\config\responses as res;

require './../vendor/autoload.php';
$configuration = [
    'settings' => [
        'displayErrorDetails' => true,  
    ],
];

//default error handlers
$c = new \Slim\Container($configuration);               
$app = new \Slim\App($c);

//grouping the api's for accessing users.
$app->group('/api/v1/users', function () use ($app) {
    
    //api for admin to get all users records.
    $app->get('', function(Request $request, Response $response) {
        $jwt = new config\jwt();
        $auth = new auth\authorize();

        if( $auth->checkAdmin($request, $response) != "legit") {
            return $auth->checkAdmin($request, $response);
        }

        //get the object of database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //object from response class
        $res = new res\userResponses();

        
        
        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');
        
        //execute the find command to get all student records
        $result = $findCommand->execute();

        //checking for errors in the result
        if (FileMaker::isError($result)) {
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

        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true, 'data'=>$data]);
        

    });

    //api for get user
    $app->get('/{id}', function (Request $request, Response $response, array $args) {

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
        if (FileMaker::isError($result)) {
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
        $resFormat = $res->getresponse($result->getRecords()[0]);

        
        //response after successful record is fetched
        $newresponse = $response->withStatus(200);
        return $response->withJson(["success"=>true, "data"=>$resFormat]);
       
    });


    //api to insert a new user
    $app->post('', function(Request $request, Response $response) {

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
        
       
        if($return != "no error") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, "message"=>$return]); 
        }

        $values = $res->insertUser($vars);
            
        //populating fields of student layout
        $rec = $fm->createRecord('Signup_User', $values);
        $insertResult = $rec->commit();

        //checking error populating fields in globalstudent layout
        if (FileMaker::isError($insertResult)) {
            $findError = 'Find Error: '. $insertResult->getMessage(). ' (' . $insertResult->code. ')';
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, "UserRecordCreation"=>$findError, "length" => strlen($vars->image), "fields" => $values]);
        }      

        //returning success response
        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true]);
    });


    //api to delete a user
    $app->delete('/{id}', function (Request $request, Response $response, array $args) {

        //get the object of authentication class
        $auth = new auth\authorize();

        //get the object of jwt class
        $jwt = new config\jwt();

        //get the object of validation class
        $valid = new validate\validateFields();

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
        if (FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'record with given sic doesnot exists']);
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
    });

    //update a student record
    $app->put('', function(Request $request, Response $response) 
    { 

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
        

        //only admin can update other records no one else
        if( $auth->checkUser($request, $response, $vars->id) != "legit") {
            return $auth->checkUser($request, $response, $vars->id);
        }

        //getting the new values in array format
        $values = $res->insertUser($vars);
        
        //commands to find the specific record
        $findCommand = $fm->newFindCommand('Signup_USER');
        $findCommand->addFindCriterion('_kp_Id_n', '=='.$vars->id);
        $result = $findCommand->execute();

        //checking for error
        if (FileMaker::isError($result)) {
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
        if (FileMaker::isError($result)) {
             $findError = $result->getMessage(). ' (' . $result->code. ')';
            $newresponse = $response->withStatus(404);
            return $newresponse->withJson(['success'=>false, "userRecordUpdationError"=>$findError]);
            
        }

        $newresponse = $response->withStatus(200);
        return $newresponse->withJson(['success'=>true]);

    });

});


//api for login
$app->post('/api/v1/login', function(Request $request, Response $response   ){ 


    $res = new res\userResponses();
    $dbobj = new dbconnect\dbconnection();
    $fm = $dbobj->connect();
    $vars = json_decode($request->getBody());


    $valid = new validate\validateFields();

    if( $valid->loginVal($vars) != "no error") {
        $newresponse = $response->withStatus(401);
        return $newresponse->withJson(['success'=>false, 'message'=>$valid->loginVal($vars)]);
    }


    //specify the layout
    $findCommand = $fm->newFindCommand('Signup_USER');

    //specify the email and password match criteria
    $findCommand->addFindCriterion('_ka_Username_t', '=='.$vars->username);
    $findCommand->addFindCriterion('Password_t', '=='.$vars->password);

    //execute the above command
    $result = $findCommand->execute(); 

    //checking for any error
    if (FileMaker::isError($result)) {
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
    return $response->withJson(["success"=>true, "data"=>$resFormat['data'], "token"=>$resFormat['token']]);

});

$app->run();