#!/bin/bash
set -e

DB_NAME="mytacho"
DB_USER="mytacho_user"
DB_PASS="mytacho_pass"

# Initialize MariaDB if not present
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB..."
    mysqld --initialize-insecure --user=mysql
fi

# Start MariaDB in background
mkdir -p /var/run/mysqld
chown mysql:mysql /var/run/mysqld
mariadbd-safe --datadir=/var/lib/mysql &

# Wait until MariaDB is ready
until mysqladmin ping --silent; do
    echo "Waiting for MariaDB..."
    sleep 2
done

# Create database and users if not exists
mysql -u root <<-EOSQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME};

-- Create user for remote connections
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';

-- Create user for local connections (phpMyAdmin usually connects as localhost)
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';

GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOSQL

# Create users table and default admin if not exists
ADMIN_HASH=$(php -r "echo password_hash('admin', PASSWORD_DEFAULT);")

mysql -u root <<-EOSQL
USE ${DB_NAME};

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    language VARCHAR(5) DEFAULT 'en',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, role, language)
SELECT 'admin', '${ADMIN_HASH}', 'admin', 'en'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='admin');
EOSQL

# Start Apache in foreground
exec apache2-foreground
