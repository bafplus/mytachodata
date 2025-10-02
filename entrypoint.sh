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

mysql -u root <<-EOSQL
    CREATE DATABASE IF NOT EXISTS mytacho;
    CREATE USER IF NOT EXISTS 'mytacho_user'@'%' IDENTIFIED BY 'mytacho_pass';
    GRANT ALL PRIVILEGES ON mytacho.* TO 'mytacho_user'@'%';
    FLUSH PRIVILEGES;
EOSQL

# Start Apache in the foreground
echo "Starting Apache..."
apache2-foreground
