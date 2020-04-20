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

        return array( 'data'=>array( 'sic'=>$id, 'role'=>$role), 'token'=>$token); 
    }

    function getActivityRes($result) {
        return array(
            'activities' => $result->_impl->_fields['activities'][0]
        );
    }

    //return the proper get response format
    function getresponse($result) {

        $id = $result->_impl->_fields['_kp_Id_n'][0];
        $availabilityDetails = \users\apiClass::getCounselorAvailabilityDetails($id);
        // print_r($availabilityDetails);
        // exit(0);
        $Availability = [];
        if( $availabilityDetails != null) {
            foreach ($availabilityDetails as $details) {
                array_push($Availability, array(
                    'Status'=>$details->_impl->_fields['Status_t'][0],
                    'Time'=>$details->_impl->_fields['Time_t'][0],
                    'Day'=>explode("\n", $details->_impl->_fields['Day_t'][0]),
                    'Type'=>$details->_impl->_fields['Type_t'][0],
                    'Location'=>$details->_impl->_fields['Location_t'][0],
                    'Rating_t'=>$details->_impl->_fields['Rating_n'][0]
                ));
            }
        } 
        
        $dob = "";
        if ( $result->_impl->_fields['Dob_d'][0] != "" ) {
            $dob = date("dS-M-Y", strtotime($result->_impl->_fields['Dob_d'][0]));
        }
        
        return array(
            'id' => $result->_impl->_fields['_kp_Id_n'][0],
            'name' => $result->_impl->_fields['Name_t'][0],
            'gender' => $result->_impl->_fields['Gender_t'][0],
            'dob' => $dob,
            'username' => $result->_impl->_fields['_ka_Username_t'][0],
            'password' => $result->_impl->_fields['Password_t'][0],
            'role' => $result->_impl->_fields['Role_t'][0],
            'image' => $result->_impl->_fields['Image_t'][0],
            'availability' => $Availability
        );
    }

    function getAllRecords($result) {
        
        //database connection
        $dbobj = new dbconnectns\dbconnection();
        $fm = $dbobj->connect();
        

        $records=[];
        $counselorData=[];

        //looping through each records
        foreach ($result as $rec) {
            $temp = userResponses::getresponse($rec);
            array_push($records, $temp);
            
        }

        
        return $records;
    }

    function insertUser($vars) {

        //changing angular date format to FileMaker date format
        $dob = date("m/d/Y", strtotime($vars->dob));

        //creating the required response format
        $student = array(
            'Name_t' => $vars->name,
            'Dob_d' => "$dob",
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