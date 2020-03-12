<?php
namespace src\config;

use \Firebase\JWT\JWT as token; 

class jwt 
{
    private $secretkey;

    public function __construct() {
        $this->secretkey = 'secretsad';
    }

    public function jwttokenencryption($id, $role) 
    {   



        //constructing payload
        $payload = json_encode(array('role' => $role, 'id' => strval($id)));

        //encrypting using firebase library
        $encoded = token::encode($payload, $this->secretkey, 'HS256');

        return $encoded;

    }

    public function jwttokendecryption($token) {
       
        $msg = "";
        $obj = [];
        $error = false;
        $decoded = "";
        //decypting signature using firebase library
        try {
            
            $decoded = token::decode($token, $this->secretkey, array('HS256'));

        } catch(\Exception $e) {
            $msg = $e->getMessage();
            $error = true;
        } finally {
            if($error) {
                $obj["verification"] = "failed";
                $obj["msg"] = $msg;
                return json_encode($obj);   
            } else {
                $obj["verification"] = "passed";
                $data = json_decode($decoded);
                $obj["id"] = $data->id;
                $obj["role"] = $data->role;
                return json_encode($obj);
            }        
        }
        
        
        
    }

}
  
?>