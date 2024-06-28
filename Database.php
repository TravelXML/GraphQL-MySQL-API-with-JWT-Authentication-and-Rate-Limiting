<?php

namespace MyApp;

use Exception;
use mysqli;

class Database {
    private $connection;

    public function __construct($config) {
        $this->connection = new mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['dbname']
        );

        if ($this->connection->connect_error) {
            throw new Exception('Connection failed: ' . $this->connection->connect_error);
        }
    }

    public function query($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        if ($params) {
            $stmt->bind_param(...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function count($table) {
        $result = $this->query("SELECT COUNT(*) as count FROM $table");
        return $result[0]['count'];
    }

    public function getColumns($table) {
        $result = $this->query("SHOW COLUMNS FROM $table");
        return array_column($result, 'Field');
    }

    public function tableExists($table) {
        $result = $this->query("SHOW TABLES LIKE ?", ['s', $table]);
        return count($result) > 0;
    }
}
