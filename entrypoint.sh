#!/bin/bash
set -euo pipefail

DB_NAME="mytacho"
DB_USER="mytacho_user"
DB_PASS="mytacho_pass"

# Initialize MariaDB if not present
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB..."
    if mysqld --initialize-insecure --user=mysql; then
        echo "Initialized with --initialize-insecure"
    else
        echo "Falling back to mysql_install_db..."
        mysql_install_db --user=mysql --datadir=/var/lib/mysql
    fi
fi

# Start MariaDB in background
echo "Starting MariaDB..."
mysqld_safe --datadir=/var/lib/mysql &

# Wait until MariaDB is ready
until mysqladmin ping --silent; do
    echo "Waiting for MariaDB..."
    sleep 1
done

# Create database and app user(s)
mysql -u root <<-EOSQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;
-- Create user for TCP (%)
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';
-- Also ensure user for localhost/socket exists (some clients use socket)
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOSQL

# Create users table and a default admin user (hashed password 'admin')
ADMIN_HASH=$(php -r "echo password_hash('admin', PASSWORD_DEFAULT);")

mysql -u root <<-EOSQL
USE \`${DB_NAME}\`;

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

echo "Database and users ready."

# Start apache in foreground (original behaviour)
exec apache2-foreground
