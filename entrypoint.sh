#!/bin/bash
set -e

# Make sure directories exist and have correct permissions
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

# Initialize MariaDB database directory if empty
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

# Start MariaDB service in the background
echo "Starting MariaDB..."
mysqld_safe --user=mysql --datadir=/var/lib/mysql &

# Wait for MariaDB to be ready
echo "Waiting for MariaDB to start..."
until mysqladmin ping -h localhost --silent; do
    sleep 1
done

# Configure MariaDB authentication so PHP can connect without issue
echo "Configuring database users..."
mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');"
mysql -u root -e "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED VIA mysql_native_password USING PASSWORD('');"
mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;"
mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;"
mysql -u root -e "FLUSH PRIVILEGES;"

# Run the setup_database.php script to create schema and populate data
echo "Initializing SpendWise database schema..."
php /var/www/html/setup_database.php

# Keep Apache running in the foreground
echo "Starting Apache web server..."
exec apache2-foreground
