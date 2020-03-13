<?php

namespace src\config\responses;

class mediaResponses {


    public function getResponse( $rec ) {
        $records = [];

        foreach ( $rec as $result ) {
            array_push($records, array(
                'media_id' => $result->_impl->_fields['_kp_MediaId_n'][0],
                'FileRef' => $result->_impl->_fields['FileRef_t'][0]
            ));
        }
        return $records;
    }

    
}