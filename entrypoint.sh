#!/bin/bash
set -e

# ------------------------
# Initialize MariaDB if needed
# ------------------------
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB..."
    mysqld --initialize-insecure --user=mysql

    # Start temporary MariaDB to create initial DB and user
    service mysql start

    # Create main database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS mytachodata;"
    mysql -e "CREATE USER 'user'@'%' IDENTIFIED BY 'password';"
    mysql -e "GRANT ALL PRIVILEGES ON mytachodata.* TO 'user'@'%';"
    mysql -e "FLUSH PRIVILEGES;"

    service mysql stop
fi

# Start MariaDB in the background
echo "Starting MariaDB..."
mysqld_safe --datadir=/var/lib/mysql &

# Wait a few seconds for DB to start
sleep 5

# Optionally create your per-user database(s) here
# Example:
# mysql -e "CREATE DATABASE IF NOT EXISTS user1_db;"

# Start Apache in the foreground
echo "Starting Apache..."
apache2-foreground
