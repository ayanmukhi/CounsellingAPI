<?php

namespace src\config\responses;

class contactResponses { 

    //function for update and insert operation
    public function updateResponse($result) {

        return array (
            'State_t' => $result->state,
            'StreetName_t' => $result->streetName,
            'District_t' => $result->district,
            'Pin_n' => $result->pin,
            '_ku_Phone_n' => $result->phone,
            '_kf_Id_n' => $result->id
        );

    }
}