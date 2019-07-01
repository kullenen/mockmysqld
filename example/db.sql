CREATE DATABASE IF NOT EXISTS example_db;
CREATE USER IF NOT EXISTS example_user@'%';
GRANT ALL PRIVILEGES ON example_db.* TO example_user@'%';

USE example_db;

