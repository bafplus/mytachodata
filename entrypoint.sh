#!/bin/bash
set -e

DB_NAME="mytacho"
DB_USER="mytacho_user"
DB_PASS="mytacho_pass"

# ------------------------
# Initialize MariaDB if needed
# ------------------------
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB..."
    mysqld --initialize-insecure --user=mysql --datadir=/var/lib/mysql
fi

# Start MariaDB in the background
echo "Starting MariaDB..."
mysqld_safe --datadir=/var/lib/mysql &
MYSQL_PID=$!

# Wait until MariaDB is ready
echo "Waiting for MariaDB to accept connections..."
until mysqladmin ping --silent >/dev/null 2>&1; do
    sleep 2
done
echo "MariaDB is ready."

# Create database and user if not exists
echo "Configuring database..."
mysql -u root <<-EOSQL
    CREATE DATABASE IF NOT EXISTS ${DB_NAME};
    CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL

# Keep MariaDB running in background, start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
