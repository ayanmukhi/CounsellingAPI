<?php

//headers to avoid CORS policy
header ("Access-Control-Allow-Origin: http://localhost:4200");
header ("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header ("Access-Control-Allow-Headers: origin, x-requested-with, content-type, authorization");


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\UploadedFile;
use src\config as jwtns;
use src\authenticate as auth;
use src\config\connection as dbconnect;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


// require './../vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

//configuration for error display
$configuration = [
    'settings' => [
        'displayErrorDetails' => true,      
    ],
];




//default error handlers
$c = new \Slim\Container($configuration);               
$app = new \Slim\App($c);


$container = $app->getContainer();
$container['image_upload_directory'] = __DIR__ . './../uploads/image';
$container['video_upload_directory'] = __DIR__ . './../uploads/video';


//app to handle uploaded files of html
$app->post('/upload', function(Request $request, Response $response) {
    //get uploaded files
    $uploadedFiles = $request->getUploadedFiles();
    $fileInfo = $request->getParsedBody();
    
    $fileTitle = $fileInfo['fileTitle'];
    $fileExtension = $fileInfo['fileExt'];


    //set saving directory
    if(strpos($fileExtension, 'image') !== false){
        $directory = $this->get('image_upload_directory');
    } 
    else if( strpos($fileExtension, 'video') !== false){


        $directory = $this->get('video_upload_directory');

        //checking for jwt token
        if( $request->hasHeader("Authorization") == false) {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["message"=>"required jwt token is not recieved"]);
        }
    
        $header = $request->getHeader("Authorization");
        $vars = substr($header[0],7);
        $token = json_decode($jwt->jwttokendecryption($vars));
        
        if( $token->verification == "failed" ) {
            $newresponse = $response->withStatus(401);
            return $newresponse->withJson(["message"=>"you are not authorized", "error"=>$token->verification]);
        }
    } 
    else {
        return $response->withJson([ 'success' => false, 'message' => 'fileType not accepted'], 400);
    }
    return upload( $response, $directory, $fileExtension, $uploadedFiles, $fileTitle);    
});


function upload( Response $response, $directory, $fileExtension, $uploadedFiles, $fileTitle ) {    
    

    $jwt = new jwtns\jwt();

    
    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['myFile'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        
        $filename = moveUploadedFile($directory, $uploadedFile, $fileExtension, $fileTitle);
        $response->write('uploaded ' . $filename . '<br/>');


        //checking for image or video
        if( strpos($fileExtension, 'video') !== false){

            //storing the video file path to the FM DB
            $insertResult = \media\apiClass::insertNewMedia("http://filemaker/uploads/video/" . $filename, $fileTitle);
            if( $insertResult['success'] ) {
                return $response->withJson(['success' => true, 'message' => $insertResult['message']],200);
            }
            return $response->withJson(['success' => false, 'message' => $insertResult['message'] ],500);
        }
        else {

            //returning the image file path to frontend
            return $response->withJson([ 'success' => true ], 200);
        }
    }
    return $response->withJson(['success' => false, "message" => $uploadedFile->getError()],500);
};

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploadedFile file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, UploadedFile $uploadedFile, $fileExtension, $fileTitle)
{

    if (strpos($fileExtension, 'video') !== false) {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
    }
    else {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = sprintf('%s.%0.8s', $fileTitle, $extension);



        //coonection class object
        $dbobj = new dbconnect\dbconnection();

        //connection to get the filemaker connection object
        $fm = $dbobj->connect();


        //commands to find the specific record with email
        $findCommand = $fm->newFindCommand('Signup_USER');
        $findCommand->addFindCriterion('_ka_Username_t', '=='.$fileTitle);
        $result = $findCommand->execute();
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'record with given email doesnot exists']);
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


        //insert the image file path to fm record
        $newEdit = $fm->newEditCommand('Signup_USER', $rec, array( "ImageFileRef_t" => "http://filemaker/uploads/image/" . $filename ));
        $result = $newEdit->execute(); 

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
        
    }
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    
    

    return $filename;
}


//grouping the APIs for accessing users.
$app->group('/api/v1/users', function () use ($app) {
    

    //api for admin to get all users records.
    $app->get('/counselors', function(Request $request, Response $response) {
        // return users\apiClass::getAllCounselors($request, $response);

        $result = users\apiClass::getCounselorDetails();
        if( $result['success'] == 1) {
            return $response->withJson(['success' => true, 'data' => $result['data']]);
        }
        return $response->withJson(['success' => false, 'data' => $result['error']]);
    });



    //api for get user
    $app->get('/{id}', function (Request $request, Response $response, array $args) {
        return users\apiClass::get($request, $response, $args);
    });


    //api to insert a new user
    $app->post('', function(Request $request, Response $response) {
        return users\apiClass::insertNewUser($request, $response);
    });


    //update a student record
    $app->put('', function(Request $request, Response $response) { 
        return users\apiClass::updateUser($request, $response);
    });


    //delete a user record
    $app->delete('/{id}', function(Request $request, Response $response, array $args) {
        return users\apiClass::deleteUser($request, $response, $args);
    });


});


//api for login
$app->post('/api/v1/login', function(Request $request, Response $response){
    return users\apiClass::loginUser($request, $response);
});

//grouping APIs for accessing media
$app->group('/api/v1/media', function() use ($app) {

    //api to get all media records according to type
    $app->get('/{type}', function(Request $request, Response $response, array $args) {
        return media\apiClass::getAllMedia($request, $response,$args);
    });  
});


//grouping APIs for accesing availability
$app->group('/api/v1/availabilities', function() use ($app) {



    //api to insert new availability record
    $app->post('', function(Request $request, Response $response) {
        return availability\apiClass::insertNewAvailability($request, $response);
    });

    //api to get counselor's specific record
    $app->get('/{id}', function(Request $request, Response $response, array $agrs){
        return availability\apiClass::getAvailability($request, $response, $agrs);
    });

    //api to delete a availability record
    $app->delete("/{id}", function(Request $request, Response $response, array $args){
        return availability\apiClass::deleteAvailability($request, $response, $args);
    });

    //update a counselor's availability criteria
    $app->put('', function(Request $request, Response $response) {
        return availability\apiClass::updateAvailability($request, $response);
    });

});



$app->get('/api/v1/appointedSeeker/{id}', function(Request $request, Response $response, array $args) {
    return bookings\apiClass::getAppointedUser($request, $response, $args);
});

//grouping APIs for accesing bookings
$app->group('/api/v1/bookedCounselor', function() use ($app) {

    //get details of a booked counselor
    $app->get('/{id}', function (Request $request, Response $response, array $args) {
        return bookings\apiClass::getBookedCounselor($request, $response, $args);
    });

    //insert a new appointment 
    $app->post('', function (Request $request, Response $response) {
        return bookings\apiClass::insretBooking($request, $response);
    });
});

//grouping APIs for accesing contacts
$app->group('/api/v1/contact', function() use ($app) {

    //update of a contact
    $app->put('', function (Request $request, Response $response, array $args) {
        return contact\apiClass::updateContact($request, $response);
    });
});


$app->get('/appointment/{id}', function(Request $request, Response $response, array $args) {
        $result = users\apiClass::getCounselorAvailabilityDetails($args['id']);
        print_r(($result));
});

$app->get('/{id}', function(Request $request, Response $response, array $args) {
    $result = users\apiClass::getCounselorDetails( $args['id']);
    if( $result['success'] == 1) {
        return $response->withJson(['success' => true, 'data' => $result['data']]);
    }
    return $response->withJson(['success' => false, 'data' => $result['error']]);
});



$app->run();