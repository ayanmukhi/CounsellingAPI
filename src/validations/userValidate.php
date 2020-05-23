<?php
namespace src\validations;

use src\config as config;


class validateFields
{

    function sicVal($args) {
        $id = $args['id'];
        if (preg_match("/^\d+$/", $id) == false) {
        return 'sic must be a number';
        }
        return "no error";
    }

    public function valUserData($vars, $method, $request, $response) {
        $obj = new config\duplicate();

        $count = 0;

        foreach($vars as $key) {
            $count++;
        }
        
        if($method == "put") {
            if( $count != 13) {
                return "reqst body is not appropriate";
            }

            $id = $vars->id;
            if (preg_match("/^\d+$/",$id) == false) {
                $newresponse = $response->withStatus(400);
                return $newresponse->withJson(["success"=>false, 'message'=>'sic must be a number']);
            }
        }
        else {
            if( $count != 7) {
                return $count."reqst body is not appropriate";
            }
        }
            

        $name = $vars->name;
        if( preg_match("/^[a-zA-Z]+(\s[a-zA-Z]*)*$/", $name) == false ) {
            return "name is not valid";
        }
        


        $dob = $vars->dob;
        if($dob != "") {
            $today = date("Y-m-d");
            $diff = date_diff(date_create($dob), date_create($today));
            $age = $diff->format('%y');
            if( $age < 15) {
                return "age must be between 15 - 30 years";
            }
        }
        

        $gender = $vars->gender;
        if( preg_match("/^(male|female|other|)$/", $gender) == false) {
            return "gender is invalid";
        }



        $password = $vars->password;
        if(( preg_match("/(?=[a-z])/", $password) == false) or ( preg_match("/(?=[A-Z])/", $password) == false) or ( preg_match("/(?=[0-9])/", $password) == false) or ( strlen($password) < 3)) {
            return "password is not valid";
        } 

        $email = $vars->username;
        if(preg_match("/[a-zA-Z0-9]+@([a-zA-z]+)/", $email) == false) {
            return "username is not valid";
        }

        
        if( $method == "put") {
            if(($obj->checkemail($email, $request, $response) != -1) and ($obj->checkemail($email, $request, $response) != $vars->id)) {
                return "email already used";
            }
        }
        else  {
            // print_r($obj->checkemail($email, $request, $response));
            if ($obj->checkemail($email, $request, $response) != -1) {
                return "email already used";
            }
        }
     
        return "no error";
    }

    public function loginVal($vars) {

        if( array_key_exists('username', $vars) == false || $vars->username == null) {
            return 'username is required ';
        }
        if( array_key_exists('password', $vars) == false || $vars->password == null) {
            return 'password is required';
        }
        $username = $vars->username;
        $password = $vars->password;
    
        if(preg_match("/[a-zA-Z0-9]+@([a-zA-z]+)/", $username) == false) {
            return "username is not valid";
        }
    
        if(( preg_match("/(?=[a-z])/", $password) == false) or ( preg_match("/(?=[A-Z])/", $password) == false) or ( preg_match("/(?=[0-9])/", $password) == false) or ( strlen($password) < 3)) {
            return "password is not valid";
        }
        return "no error";
        
    }
}