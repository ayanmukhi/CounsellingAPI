<?php

namespace src\config\responses;

use src\config\connection as dbconnectns;

class userResponses {

    //return the proper login response format
    function loginresponse($result) {
        
        $jwt = new \src\config\jwt();

        //extracting the id and role from the record.
        $id = $result[0]->_impl->_fields['_kp_Id_n'][0];
        $role = $result[0]->_impl->_fields['Role_t'][0];

        //generating the token
        $token = $jwt->jwttokenencryption($id, $role);

        return array( 'data'=>array( 'sic'=>$id, 'status'=>$role), 'token'=>$token); 
    }

    function getActivityRes($result) {
        return array(
            'activities' => $result->_impl->_fields['activities'][0]
        );
    }

    //return the proper get response format
    function getresponse($result) {

        $dob = "";
        if ( $result->_impl->_fields['Date_d'][0] != "" ) {
            $dob = date("dS-M-Y", strtotime($result->_impl->_fields['Date_d'][0]));
        }
        
        return array(
            'id' => $result->_impl->_fields['_kp_Id_n'][0],
            'name' => $result->_impl->_fields['Name_t'][0],
            'gender' => $result->_impl->_fields['Gender_t'][0],
            'dob' => $dob,
            'username' => $result->_impl->_fields['_ka_Username_t'][0],
            'password' => $result->_impl->_fields['Password_t'][0],
            'role' => $result->_impl->_fields['Role_t'][0],
        );
    }

    function getAllRecords($result) {
        
        //database connection
        $dbobj = new dbconnectns\dbconnection();
        $fm = $dbobj->connect();
        

        $seekerRecords = [];
        $counselorRecords = [];

        //looping through each records
        foreach ($result as $rec) {

            //saving the current record role type
            $role = $rec->_impl->_fields['Role_t'][0];

            if( $role === "seeker") {
                array_push($seekerRecords, userResponses::getresponse($rec));
            }
            else if( $role  === "counselor" ) {
                array_push($counselorRecords, userResponses::getresponse($rec));
            }
            else {
                //do not returning any admin record data
            }
        }

        //combining both the records in one array
        return array( 
            "seekers" => $seekerRecords,
            "counselor" => $counselorRecords
        );
    }

    function insertUser($vars) {

        //changing angular date format to FileMaker date format
        $dob = date("m/d/Y", strtotime($vars->dob));

        //creating the required response format
        $student = array(
            'Name_t' => $vars->name,
            'Date_d' => "$dob",
            'Gender_t' => "$vars->gender",
            '_ka_Username_t' => "$vars->username",
            'Password_t' => "$vars->password",
            'Role_t' => $vars->role,
            'Image_r' => $vars->image
        );
        return $student;
    }


    
    function insertHobbyRecord($vars, $sic) {
        $hobby = implode("\n",$vars->hobby);
        return array(
            'sic' => $sic,
            'hobby_name' => $hobby

        );
    }
    function insertActivity($vars) {
        return array(
            'sic' => $vars->sic,
            'activities' => $vars->activity
        );
    }
    
    
}