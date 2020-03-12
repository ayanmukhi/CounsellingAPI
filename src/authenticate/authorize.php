<?php
namespace src\authenticate;

use src\config as jwtns;

class authorize {

    public function checkUser($request, $response, $id) {

        $jwt = new jwtns\jwt();


        if( $request->hasHeader("Authorization") == false) {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["message"=>"required jwt token is not recieved"]);
        }

        $header = $request->getHeader("Authorization");
        $vars = substr($header[0],7);
        $token = json_decode($jwt->jwttokendecryption($vars));
        
        if( $token->verification == "failed") {
            $newresponse = $response->withStatus(401);
            return $newresponse->withJson(["message"=>"you are not authorized", "error"=>$token->msg]);
        }
        if( $token->role != "admin" and $token->id != $id ) {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["success" => false, "message"=>" you don't have access to other records "]);
        }
        return "legit";
    }

    public function checkAdmin($request, $response) {

        $jwt = new jwtns\jwt();

        if( $request->hasHeader("Authorization") == false) {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["message"=>"required jwt token is not recieved"]);
        }
        $header = $request->getHeader("Authorization");
        $vars = substr($header[0],7);
        $token = json_decode($jwt->jwttokendecryption($vars));
        if( $token->verification == "failed") {
            $newresponse = $response->withStatus(401);
            return $newresponse->withJson(["message"=>"you are not authorized"]);
        }
        if( $token->role != "admin") {
            $newresponse = $response->withStatus(400);
            return $newresponse->withJson(["message"=>"you cann't see other records"]);
        }
        return "legit";
    }

}