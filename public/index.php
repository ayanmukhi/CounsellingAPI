<?php

//headers to avoid CORS policy
header ("Access-Control-Allow-Origin: http://localhost:4200");
header ("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header ("Access-Control-Allow-Headers: origin, x-requested-with, content-type, authorization");


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\UploadedFile;


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
$container['upload_directory'] = __DIR__ . './../uploads';


//app to handle uploaded files of html
$app->post('/upload', function(Request $request, Response $response) {
    // echo "aaa";
    // print_r($request->getUploadedFiles());
    $directory = $this->get('upload_directory');

    $uploadedFiles = $request->getUploadedFiles();
    // print_r($uploadedFiles);

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['myFile'];
    // print_r($uploadedFile);
    // exit(0);
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $filename = moveUploadedFile($directory, $uploadedFile);
        $response->write('uploaded ' . $filename . '<br/>');
        return $response->withJson(['success' => true],200);
    }
    return $response->withJson(['success' => false],500);
});

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploadedFile file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}


//grouping the APIs for accessing users.
$app->group('/api/v1/users', function () use ($app) {
    

    //api for admin to get all users records.
    $app->get('/counselors', function(Request $request, Response $response) {
        return users\apiClass::getAllCounselors($request, $response);
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
    $app->delete("/{availability_id}", function(Request $request, Response $response, array $args){
        return availability\apiClass::deleteAvailability($request, $response, $args);
    });

});

$app->get('/api/v1/appointedSeeker/{id}', function(Request $request, Response $response, array $args) {
    return bookings\apiClass::getAppointedUser($request, $response, $args);
});

$app->get('/api/v1/bookedCounselor/{id}', function(Request $request, Response $response, array $args) {
    return bookings\apiClass::getBookedCounselor($request, $response, $args);
});



$app->run();