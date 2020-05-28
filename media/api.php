<?php

namespace media;

use src\config\connection as dbconnect;
use src\validations as validate;
use src\authenticate as auth;
use src\config as config;
use src\config\responses as res;

use src\config as jwtns;

class apiClass {




    function insertNewMedia( $fileRef, $fileTitle ) {
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();
        
        //object from response class
        $res = new res\mediaResponses();

        $values = array (
            'FileTitle_t' => $fileTitle, 
            'FileRef_t' => $fileRef,
            'Status_t' => 'inactive',
            'MediaType_t' => 'video',
            // 'FileTitle_t' => $fileTitle,
        );

        $rec = $fm->createRecord('InsertMedia_MEDIA', $values);
        $insertResult = $rec->commit();

        //checking error populating fields in globalstudent layout
        if (\FileMaker::isError($insertResult)) {
            $findError = 'Find Error: '. $insertResult->getMessage(). ' (' . $insertResult->code. ')';
            return array(
                'success' => false, 
                'message' => $findError
            );
        }

        return array('success'=>true, 'message' => $fileTitle);

    }

    //function to get media records
    public function getALLMedia($request, $response, $args)
    {
        //database connection
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();
        
        //object from response class
        $res = new res\mediaResponses();

        if (preg_match("/^[a-zA-Z][\sa-zA-Z]*$/", $args['type']) == false) {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success"=>false, 'message'=>"type of media should not contain any numbers or special characters"]);
        }

        
        //specify the layout
        $findCommand = $fm->newFindCommand('InsertMedia_MEDIA');

        //specify the email and password match criteria
        $findCommand->addFindCriterion("MediaType_t", "==".$args['type']);
        $findCommand->addFindCriterion("Status_t", "active");


        //execute the above command
        $result = $findCommand->execute();

        

        //checking for any error
        if (\FileMaker::isError($result)) {
            
                $findError = $result->getMessage()." ( " . $result->getCode() . " ) ";
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=> $findError]);
        
        }

        //get the FileMaker result set and changing i to proper response format
        $resFormat = $res->getResponse($result->getRecords());

        //response after successful record is fetched
        $newresponse = $response->withStatus(200);
        return $response->withJson(["success"=>true, "data"=>$resFormat]);
    }

}