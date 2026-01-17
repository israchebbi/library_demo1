<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
//secret key used to sign tokens 
$secret_key ="library_secret_key_123";
//create jwt token 
function createJWT($user){
    global $secret_key;
    //token pplayload (data inside token)
    $playload = [
        "iss" => "library-api",
        "iat" => time(),
        "exp" => time()+3600,
        "data"=> [
            "id" => $user['id'],
            "role" =>$user['role']
        ]
    ];
    //encode token using hs256 algorithm
    return JWT::encode($playload, $secret_key , 'HS256');
}
//verify JWT token 
function verifyJWT($token) {
    global $secret_key ; 
    //decode token and verify signature 
    return JWT::decode ($token , new key($secret_key, 'HS256'));
}
//jwt stores user identity 
//token expires automatically after an hour
//role will be used for permission 
?>