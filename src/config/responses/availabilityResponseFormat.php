<?php

namespace src\config\responses;


class availabilityResponses {

    //function to make a array of required fields
    public function insertResponse( $vars ) {
        return array(
            "_kf_Id_n" => $vars->counselor_id,
            "Type_t" => $vars->type,
            "Day_t" => $vars->day,
            "Time_t" => $vars->time,
            "Location_t" => $vars->location
        );
    }

    //function to make a array format required for get response
    public function getResponse($records) {
        $res = [];
        foreach ($records as $result) {
            array_push($res, array(
                'availability_id' => $result->_impl->_fields['_kp_AvailabilityId_n'][0],
                'type' => $result->_impl->_fields['Type_t'][0],
                'day' => $result->_impl->_fields['Day_t'][0],
                'time' => $result->_impl->_fields['Time_t'][0],
                'status' => $result->_impl->_fields['Status_t'][0],
                'location' => $result->_impl->_fields['Location_t'][0],
                'Rating' => $result->_impl->_fields['Rating_n'][0],
            ));
        }

        return $res;
    }
}