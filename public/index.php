<?php

//headers to avoid CORS policy
header ("Access-Control-Allow-Origin: http://localhost:4200");
header ("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header ("Access-Control-Allow-Headers: origin, x-requested-with, content-type, authorization");


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


require './../vendor/autoload.php';

//configuration for error display
$configuration = [
    'settings' => [
        'displayErrorDetails' => true,  
    ],
];


//default error handlers
$c = new \Slim\Container($configuration);               
$app = new \Slim\App($c);



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
    $app->get('/{counselor_id}', function(Request $request, Response $response, array $agrs){
        return availability\apiClass::getAvailability($request, $response, $agrs);
    });

    //api to delete a availability record
    $app->delete("/{availability_id}", function(Request $request, Response $response, array $args){
        return availability\apiClass::deleteAvailability($request, $response, $args);
    });

});



$app->run();