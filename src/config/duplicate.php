<?php
namespace src\config;

use src\config\connection as dbconnect;
use PDO;

class duplicate 
{

    public function checkemail($email, $request, $response) 
    {
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('_ka_Username_t', '=='.$email);

        //execute the above command
        $result = $findCommand->execute(); 

        $record = [];

        //checking for any error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                return -1;
            }
            else {
                return -1;
            }
        }
        else {
            $record = $result->getRecords();
        }

        
        
        if(count($record) == 1 ) {
            return $record[0]->_impl->_fields['_kp_Id_n'][0];
        } else {
            return -1;
        }
    }

    
    public function checkphone($phone) 
    {
        $dbobj = new dbconnect\dbconnection();
        $fm = $dbobj->connect();

        //specify the layout
        $findCommand = $fm->newFindCommand('Signup_USER');

        //specify the email and password match criteria
        $findCommand->addFindCriterion('phone', '=='.$phone);

        //execute the above command
        $result = $findCommand->execute(); 

        $record = [];

        //checking for any error
        if (\FileMaker::isError($result)) {
            if( $result->getMessage() == "No records match the request" ) {
                
            }
            else {
                $newresponse = $response->withStatus(404);
                return $newresponse->withJson(['success'=>false, 'message'=>'server error']);
            }
        }
        else {
            $record = $result->getRecords();
        }

        
        
        if(count($record) == 1 ) {
            return $record[0]->_impl->_fields['sic'][0];
        } else {
            return -1;
        }
    }
}
  
?>