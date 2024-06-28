<?php

require 'vendor/autoload.php';
require 'config.php';
require 'JwtHandler.php';

use MyApp\JwtHandler;

$config = include('config.php');
$jwtHandler = new JwtHandler($config['jwt_secret']);

$payload = [
    'iss' => 'http://example.org', // Issuer
    'aud' => 'http://example.com', // Audience
    'iat' => time(), // Issued at
    'nbf' => time(), // Not before
    'sub' => 'user123', // Subject (e.g., user ID)
];

$token = $jwtHandler->encode($payload);

header('Content-Type: application/json');
echo json_encode(['token' => $token]);
