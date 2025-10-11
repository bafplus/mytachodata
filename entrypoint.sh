#!/bin/bash
set -e

DB_NAME="mytacho"
DB_USER="mytacho_user"
DB_PASS="mytacho_pass"
HTML_DIR="/var/www/html"
ADMINLTE_DIR="$HTML_DIR/adminlte"

# --- Ensure AdminLTE exists (in case /var/www/html is mounted empty) ---
if [ ! -d "$ADMINLTE_DIR" ]; then
    echo "AdminLTE not found, downloading..."
    mkdir -p "$HTML_DIR"
    cd "$HTML_DIR"
    wget -q https://github.com/ColorlibHQ/AdminLTE/archive/refs/tags/v3.2.0.zip -O /tmp/adminlte.zip
    unzip -q /tmp/adminlte.zip -d "$HTML_DIR"
    mv "$HTML_DIR/AdminLTE-3.2.0" "$ADMINLTE_DIR"
    rm /tmp/adminlte.zip
    chown -R www-data:www-data "$ADMINLTE_DIR"
    echo "AdminLTE installed successfully."
fi

# --- Ensure MariaDB socket directory exists ---
mkdir -p /var/run/mysqld
chown mysql:mysql /var/run/mysqld

# --- Initialize MariaDB if not present ---
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB..."
    mysqld --initialize-insecure --user=mysql
fi

# --- Start MariaDB in background ---
mariadbd-safe --datadir=/var/lib/mysql --bind-address=0.0.0.0 &

# --- Wait until MariaDB is ready ---
until mysqladmin ping --silent; do
    echo "Waiting for MariaDB..."
    sleep 2
done

# --- Create main database and grant privileges ---
mysql -u root <<-EOSQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
GRANT CREATE, ALTER, DROP, INSERT, UPDATE, DELETE, SELECT, INDEX, REFERENCES, TRIGGER, EXECUTE ON *.* TO '${DB_USER}'@'localhost';
GRANT CREATE, ALTER, DROP, INSERT, UPDATE, DELETE, SELECT, INDEX, REFERENCES, TRIGGER, EXECUTE ON *.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
EOSQL

# --- Create tables in main database and default admin ---
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

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
);
INSERT INTO settings (setting_key, setting_value)
SELECT 'site_name', 'MyTacho' WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key='site_name');
INSERT INTO settings (setting_key, setting_value)
SELECT 'default_language', 'en' WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key='default_language');
INSERT INTO settings (setting_key, setting_value)
SELECT 'maintenance_mode', '0' WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key='maintenance_mode');
INSERT INTO settings (setting_key, setting_value)
SELECT 'allow_registration', '0' WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key='allow_registration');
INSERT INTO settings (setting_key, setting_value)
SELECT 'support_email', 'support@mytacho.com' WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key='support_email');
EOSQL

# --- Start Apache ---
exec apache2-foreground
