<?php

require 'vendor/autoload.php';
require 'config.php';
require 'Database.php';
require 'GraphQLSchema.php';
require 'JwtHandler.php';
require 'RateLimiter.php';

use GraphQL\GraphQL;
use GraphQL\Error\FormattedError;
use GraphQL\Error\DebugFlag;
use MyApp\Database;
use MyApp\JwtHandler;
use MyApp\RateLimiter;
use MyApp\GraphQLSchema;

// Configurations
$config = include('config.php');

try {
    // Initialize Database
    $db = new Database($config['db']);

    // Initialize JWT Handler
    $jwtHandler = new JwtHandler($config['jwt_secret']);

    // Get the client's token
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        throw new Exception('Authorization header not found.');
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decodedToken = $jwtHandler->decode($token);

    // Ensure the token has a 'sub' property
    if (!isset($decodedToken->sub)) {
        throw new Exception('Invalid token structure: "sub" property not found.');
    }

    // Initialize Rate Limiter
    $rateLimiter = new RateLimiter('redis', 2, 1); // 'redis' should be the service name if using docker-compose

    // Define allowed services
    $allowedServices = ['products', 'customers']; // add more services as needed

    // Extract the service from the query
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!isset($input['query'])) {
        throw new Exception('No query provided.');
    }

    // Extract service name from the query
    preg_match('/\{ (\w+)\(/', $input['query'], $matches);
    if (!isset($matches[1]) || !in_array($matches[1], $allowedServices)) {
        throw new Exception('Service not allowed.');
    }
    $service = $matches[1];

    // Check rate limit
    if ($rateLimiter->isRateLimited($decodedToken->sub, $service)) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests. Please try again later.']);
        exit();
    }

    // Validate input parameters
    $query = $input['query'];
    $variables = isset($input['variables']) ? $input['variables'] : [];

    // Initialize GraphQL Schema
    $graphqlSchema = new GraphQLSchema($db, $jwtHandler, 10);
    $schema = $graphqlSchema->createSchema();

    $context = ['token' => $token];

    $rootValue = ['prefix' => 'You said: '];
    $result = GraphQL::executeQuery($schema, $query, $rootValue, $context, $variables);
    $output = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::RETHROW_INTERNAL_EXCEPTIONS);
} catch (\GraphQL\Error\Error $e) {
    $output = [
        'errors' => [
            FormattedError::createFromException($e)
        ]
    ];
} catch (\Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage()
            ]
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($output);
