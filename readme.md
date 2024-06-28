# GraphQL MySQL API with JWT Authentication and Rate Limiting

A robust, scalable, and secure GraphQL API built with PHP, MySQL, and Redis. This project leverages Docker for containerization, providing a seamless development and deployment experience. Key features include JWT-based authentication, comprehensive rate limiting, and support for dynamic queries across multiple database tables. The API dynamically exposes services based on the RDBMS database and table structures, making it highly adaptable and flexible.


## Features

- **GraphQL API**: Leverage the power of GraphQL for efficient data querying and mutation.
- **JWT Authentication**: Secure your API with JSON Web Tokens, ensuring that only authenticated users can access your endpoints.
- **Rate Limiting**: Prevent abuse by limiting the number of requests per user within a specified timeframe using Redis.
- **Dynamic Querying**: Automatically generate queries for all available database tables, making your API flexible and powerful.
- **Dockerized Setup**: Easily set up and run your application using Docker and Docker Compose, ensuring a consistent development environment.
- **High Performance**: Optimized for performance with caching and efficient database querying.

## Why Use This Project?

- **Security**: Protect your API endpoints with JWT authentication.
- **Scalability**: Built to handle a large number of requests with rate limiting.
- **Flexibility**: Supports dynamic querying, making it adaptable to various use cases.
- **Ease of Use**: Simple setup and deployment with Docker.
- **Community and Support**: Join a growing community of developers and gain support through GitHub Issues and Pull Requests.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [Configuration](#configuration)
- [Running the Service](#running-the-service)
- [JWT Token Handling](#jwt-token-handling)
- [Rate Limiting](#rate-limiting)
- [Pagination](#pagination)
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

    ```bash
    curl --location 'http://localhost/api.php' \
    --header 'Content-Type: application/json' \
    --header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLm9yZyIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUuY29tIiwiaWF0IjoxNzE5NTk3MTU0LCJuYmYiOjE3MTk1OTcxNTQsInN1YiI6InVzZXIxMjMifQ.paf6YFnETH10TOGqZ5MKvOkZc1T6gIEwjL3tspXdgGI' \
    --data '{"query":"{ customers(page: 1) { customers { customerNumber, customerName, phone }, total, page, token } }","variables":{}}'
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

### Why Rate Limiting?

Rate limiting helps to protect your API from abuse and ensures fair usage among users. It prevents a single user from overwhelming the system, thus maintaining the performance and availability of the API for all users.

### Implementation Strategy

We use Redis to store and manage the request counts for each user. The rate limiter checks the number of requests made by a user within a specified time window and allows or blocks the request based on the configured limits.

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

## Pagination

Pagination is essential for managing large datasets by breaking them down into smaller, more manageable chunks. This improves performance and usability by allowing users to fetch data page by page.

### Why Pagination?

- **Performance**: Reduces the load on the server and database by limiting the amount of data processed and sent over the network.
- **User Experience**: Improves the user experience by providing data in smaller, more digestible chunks.
- **Scalability**: Enables the API to handle large datasets efficiently.

### Implementation Strategy

We implement pagination using page numbers and a fixed page size. The client specifies the page number and the number of items per page, and the server returns the corresponding subset of data.

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

## API Usage

### Example Query for Products

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
### Response

![image](https://github.com/TravelXML/GraphQL-MySQL-API-with-JWT-Authentication-and-Rate-Limiting/assets/8361967/4e51fde7-fd8f-456b-b886-f04f8264f20e)

### Example Query for Customers

```graphql
{
  customers(page: 1) {
    customers {
      customerNumber
      customerName
      phone
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

```bash
curl --location 'http://localhost/api.php' \
--header 'Content-Type

: application/json' \
--header 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLm9yZyIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUuY29tIiwiaWF0IjoxNzE5NTk3MTU0LCJuYmYiOjE3MTk1OTcxNTQsInN1YiI6InVzZXIxMjMifQ.paf6YFnETH10TOGqZ5MKvOkZc1T6gIEwjL3tspXdgGI' \
--data '{"query":"{ customers(page: 1) { customers { customerNumber, customerName, phone }, total, page, token } }","variables":{}}'
```

### Response

![image](https://github.com/TravelXML/GraphQL-MySQL-API-with-JWT-Authentication-and-Rate-Limiting/assets/8361967/b7ca58ab-3494-4d7d-8c1a-b11bc941d837)



## Troubleshooting

- **Docker Issues**: Ensure Docker and Docker Compose are installed and running.
- **Redis Connection**: Verify that Redis is running and accessible.
- **JWT Token Issues**: Ensure the token is correctly generated and included in the request headers.

