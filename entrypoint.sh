#!/bin/bash
set -e

# Initialize MariaDB only if no data exists
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

# Start supervisord (manages Apache and MariaDB)
exec /usr/bin/supervisord -n
