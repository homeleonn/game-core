<?php

namespace App\Services\JWT;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JWT
{
    public function __construct(
        private string $secret,
        private string $algorithm = 'HS256'
    )
    {

    }

    public function encode($payload)
    {
        return FirebaseJWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function decode(string $jwt)
    {
        return FirebaseJWT::decode($jwt, new Key($this->secret, $this->algorithm));
    }
}
