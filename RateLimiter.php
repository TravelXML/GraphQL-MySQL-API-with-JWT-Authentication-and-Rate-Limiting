<?php

namespace MyApp;

use Redis;
use Exception;

class RateLimiter {
    private $redis;
    private $maxRequests;
    private $windowSize;

    public function __construct($redisHost, $maxRequests, $windowSize) {
        $this->redis = new Redis();
        try {
            $this->redis->connect($redisHost);
            error_log("Connected to Redis at $redisHost");
        } catch (Exception $e) {
            throw new Exception("Could not connect to Redis: " . $e->getMessage());
        }
        $this->maxRequests = $maxRequests;
        $this->windowSize = $windowSize;
    }

    public function isRateLimited($token, $service) {
        $currentTime = microtime(true);
        $windowStart = $currentTime - $this->windowSize;
        $redisKey = "rate_limit:{$token}:{$service}";

        // Clean up old requests
        $this->redis->zRemRangeByScore($redisKey, 0, $windowStart);

        // Get the count of requests in the current window
        $requestCount = $this->redis->zCount($redisKey, $windowStart, $currentTime);

        if ($requestCount >= $this->maxRequests) {
            error_log("Rate limit exceeded for $token on service $service");
            return true;
        }

        // Add the current request to the sorted set
        $this->redis->zAdd($redisKey, $currentTime, $currentTime);

        // Set expiration for the key slightly longer than the window size to ensure cleanup
        $this->redis->expire($redisKey, $this->windowSize + 1);

        return false;
    }
}
