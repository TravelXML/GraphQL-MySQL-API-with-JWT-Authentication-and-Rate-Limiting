#!/bin/sh
# Stop the containers
#docker stop graphql_web_1
#docker stop graphql-mysql_class_web_1
#docker stop graphql-mysql_class_db_1
#docker stop graphql-mysql_class_redis_1
#docker stop graphql_db_1

# Remove the stopped containers
#docker rm graphql_web_1
#docker rm graphql-mysql_class_web_1
#docker rm graphql-mysql_class_db_1
#docker rm graphql-mysql_class_redis_1
#docker rm graphql_db_1

# Verify the containers are removed
docker ps -a

# Restart Docker Compose
docker-compose down
docker-compose up -d --build