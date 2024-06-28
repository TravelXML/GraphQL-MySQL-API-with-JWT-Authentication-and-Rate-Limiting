# GraphQL MySQL API with JWT Authentication and Rate Limiting

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [Configuration](#configuration)
- [Running the Service](#running-the-service)
- [JWT Token Handling](#jwt-token-handling)
- [Rate Limiting](#rate-limiting)
- [API Usage](#api-usage)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## Overview

This project provides a GraphQL API for interacting with a MySQL database. The API includes JWT-based authentication and rate limiting to ensure secure and efficient access. It leverages Docker for containerization, ensuring an easy setup and deployment process.

## Architecture

The architecture consists of the following components:

1. **MySQL Database**: Stores the data for various entities.
2. **Redis**: Used for rate limiting to manage API request rates.
3. **PHP-FPM with Apache**: Hosts the GraphQL API and handles requests.
4. **Docker Compose**: Manages the containerized environment, making it easy to set up and run the entire stack.

### Components

- **Database.php**: Handles database interactions.
- **GraphQLSchema.php**: Defines the GraphQL schema and resolvers.
- **JwtHandler.php**: Manages JWT token creation and validation.
- **RateLimiter.php**: Implements rate limiting using Redis.
- **api.php**: Entry point for handling GraphQL requests with JWT authentication and rate limiting.

## Prerequisites

- Docker
- Docker Compose

## Setup

1. Clone the repository:

    ```bash
    git clone https://github.com/yourusername/graphql-mysql-class.git
    cd graphql-mysql-class
    ```

2. Build and start the Docker containers:

    ```bash
    docker-compose up -d --build
    ```

## Configuration

The configuration is managed through the `config.php` file. It includes settings for the MySQL database, JWT secret, and other relevant configurations.

### Example `config.php`

```php
<?php

return [
    'db' => [
        'host' => 'db',
        'user' => 'myuser',
        'password' => 'mypassword',
        'database' => 'mydatabase',
    ],
    'jwt_secret' => 'your_jwt_secret',
];
```

## Running the Service

1. **Start the containers:**

    ```bash
    docker-compose up -d --build
    ```

2. **Generate a JWT token:**

    Access the endpoint to generate a token:

    ```bash
    curl http://localhost/GenerateToken.php
    ```

    Copy the token from the response.

3. **Make requests to the GraphQL endpoint:**

    ```bash
    curl -X POST -H "Content-Type: application/json" -H "Authorization: Bearer your-jwt-token" --data '{ "query": "{ products(page: 1) { products { productCode, productName }, total, page, token } }" }' http://localhost/api.php
    ```

## JWT Token Handling

The JWT tokens are used to authenticate API requests. The `JwtHandler.php` file handles the creation and validation of these tokens.

### Generating a JWT Token

```php
<?php

require 'JwtHandler.php';

$jwtHandler = new MyApp\JwtHandler('your_jwt_secret');
$token = $jwtHandler->encode(['sub' => 'user123']);

echo $token;
```

### Validating a JWT Token

The token is validated in `api.php` by decoding it and checking its validity.

## Rate Limiting

Rate limiting is implemented using Redis to ensure that each user cannot exceed a defined number of requests per second.

### RateLimiter.php

```php
<?php

namespace MyApp;

class RateLimiter {
    private $redis;
    private $maxRequests;
    private $windowSeconds;

    public function __construct($host, $maxRequests, $windowSeconds) {
        $this->redis = new \Redis();
        $this->redis->connect($host);

        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function isRateLimited($key, $service) {
        $redisKey = "ratelimit:{$service}:{$key}";
        $current = $this->redis->incr($redisKey);

        if ($current == 1) {
            $this->redis->expire($redisKey, $this->windowSeconds);
        }

        if ($current > $this->maxRequests) {
            error_log("Rate limit exceeded for key: $redisKey");
            return true;
        }

        error_log("Current count for key $redisKey: $current");
        return false;
    }
}
```

## API Usage

### Example Query

```graphql
{
  products(page: 1) {
    products {
      productCode
      productName
    }
    total
    page
    token
  }
}
```

### Making a Request

```bash
curl -X POST -H "Content-Type: application/json" -H "Authorization: Bearer your-jwt-token" --data '{ "query": "{ products(page: 1) { products { productCode, productName }, total, page, token } }" }' http://localhost/api.php
```

## Troubleshooting

- **Docker Issues**: Ensure Docker and Docker Compose are installed and running.
- **Redis Connection**: Verify that Redis is running and accessible.
- **JWT Token Issues**: Ensure the token is correctly generated and included in the request headers.

