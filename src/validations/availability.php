<?php

namespace src\validations\availability;

use src\config as config;

class validate {

    //function to validate the sic
    function sicVal($args) {
        if (preg_match("/^\d+$/",$args) == false) {
        return 'availability id must be a number';
        }
        return "no error";
    }

    //function to validate put and post fields
    function valAvailabilityData($vars, $method, $request, $response) {
        $obj = new config\duplicate();

        $count = 0;

        foreach($vars as $key) {
            $count++;
        }
        
        if($method == "put") {
            if( $count != 6) {
                return "reqst body is not appropriate";
            }

            $id = $vars->counselor_id;
            if (preg_match("/^\d+$/",$id) == false) {
                $newresponse = $response->withStatus(400);
                return $newresponse->withJson(["success"=>false, 'message'=>'cousenlor id  must be a number']);
            }
        } else {
            if( $count != 5) {
                return $count."reqst body is not appropriate";
            }
        }


        //validate type
        $str = $vars->type;
        if( preg_match("/^[a-zA-Z]+(\s[a-zA-Z]*)*$/", $str) == false ) {
            return "type is not valid";
        }


        //validate day
        $str = $vars->day;
        if( preg_match("/^[a-zA-Z]+(\s[a-zA-Z]*)*$/", $str) == false ) {
            return "day is not valid";
        }


        //validate location
        $str = $vars->location;
        if( preg_match("/^[a-zA-Z]+(\s[a-zA-Z]*)*$/", $str) == false ) {
            return "location is not valid";
        }


        //validate time 
        $time = $vars->time;
        if( preg_match("/^[0-9]{2}:[0-9]{2}(AM|PM)$/", $time) == false ) {
            return "time is not valid";
        }

        return "no error";

    }

}