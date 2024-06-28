<?php

namespace MyApp;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtHandler {
    private $secret;

    public function __construct($secret) {
        $this->secret = $secret;
    }

    public function encode($payload) {
        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function decode($token) {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }
}
