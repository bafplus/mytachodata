#!/bin/bash
set -e

# ------------------------
# Load ENV variables with defaults if not provided
# ------------------------
DB_NAME="${DB_NAME:-mytacho}"
DB_USER="${DB_USER:-mytacho_user}"
DB_PASS="${DB_PASS:-mytacho_pass}"
DB_HOST="${DB_HOST:-127.0.0.1}"

# ------------------------
# Initialize MariaDB if needed
# ------------------------
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB..."
    mysqld --initialize-insecure --user=mysql
fi

# Start MariaDB in the background
echo "Starting MariaDB..."
mysqld_safe --datadir=/var/lib/mysql &

# Wait until MariaDB is ready
until mysqladmin ping --silent; do
    echo "Waiting for MariaDB to be ready..."
    sleep 2
done

# Create database and user if not exists
mysql -u root <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;
    CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL

# ------------------------
# Start Apache in the foreground
# ------------------------
echo "Starting Apache..."
exec apache2-foreground
