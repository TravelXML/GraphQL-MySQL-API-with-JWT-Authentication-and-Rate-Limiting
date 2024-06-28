<?php

namespace MyApp;

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class GraphQLSchema {
    private $db;
    private $jwtHandler;
    private $pageSize;

    public function __construct($db, $jwtHandler, $pageSize) {
        $this->db = $db;
        $this->jwtHandler = $jwtHandler;
        $this->pageSize = $pageSize;
    }

    public function createSchema() {
        // Fetch all table names
        $tables = $this->db->query("SHOW TABLES");

        $queryFields = [];
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $columns = $this->db->getColumns($tableName);

            $fields = [];
            foreach ($columns as $column) {
                $fields[$column] = ['type' => Type::string()];
            }

            $objectType = new ObjectType([
                'name' => ucfirst($tableName),
                'fields' => $fields,
            ]);

            $queryFields[$tableName] = [
                'type' => new ObjectType([
                    'name' => ucfirst($tableName) . 'Page',
                    'fields' => [
                        $tableName => ['type' => Type::listOf($objectType)],
                        'total' => ['type' => Type::int()],
                        'page' => ['type' => Type::int()],
                        'token' => ['type' => Type::string()],
                    ]
                ]),
                'args' => [
                    'page' => ['type' => Type::int()],
                ],
                'resolve' => function($root, $args, $context) use ($tableName) {
                    $page = isset($args['page']) ? $args['page'] : 1;
                    $offset = ($page - 1) * $this->pageSize;

                    // Validate the JWT token from context
                    if (!isset($context['token'])) {
                        throw new \Exception('Token is required.');
                    }

                    $token = $context['token'];
                    $this->jwtHandler->decode($token);

                    // Fetch total count of records
                    $total = $this->db->count($tableName);

                    // Fetch records with limit and offset
                    $records = $this->db->query("SELECT * FROM $tableName LIMIT ?, ?", ['ii', $offset, $this->pageSize]);

                    // Generate a new token for the next page
                    $newToken = $this->jwtHandler->encode(['page' => $page + 1]);

                    return [
                        $tableName => $records,
                        'total' => $total,
                        'page' => $page,
                        'token' => $newToken
                    ];
                }
            ];
        }

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $queryFields,
        ]);

        return new Schema([
            'query' => $queryType
        ]);
    }
}
