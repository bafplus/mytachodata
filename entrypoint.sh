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
mysqld_safe --datadir=/var/lib/mysql &

# Wait until MariaDB is ready
until mysqladmin ping --silent; do
    echo "Waiting for MariaDB..."
    sleep 2
done

# Create database and user if not exists
mysql -u root <<-EOSQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
EOSQL

# Start Apache in foreground
exec apache2-foreground
