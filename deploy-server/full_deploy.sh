#!/bin/bash

# LDN Inbox Installation Script
# This script automates the installation of dependencies and configuration for LDN inbox
# Version 1.0
# Author: MP


# Exit on error
set -e


#############################################################################
#                              CONST
#############################################################################

# Linux like colors for output. It looks fancyt
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'

# No Color
NC='\033[0m'

#############################################################################
#                              FUNCTIONS
#############################################################################

# Functions for printing color messages
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

#############################################################################
#                       INSTALLATION BEGINNING
#############################################################################

# LDN inbox is installed as a root so we have to check it
# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root or with sudo"
    exit 1
fi

# Welcome message
print_message "Welcome to the LDN Inbox Installation Script"
print_message "This script will install and configure the necessary components for LDN inbox"
# TDOD echo

# Get domain name
read -p "Enter your domain name (e.g., example.com): " DOMAIN_NAME

# Select database type
echo "Select database type:"
echo "1) MySQL"
echo "2) PostgreSQL"
echo "3) MongoDB"
read -p "Enter your choice (1-3): " DB_CHOICE

case $DB_CHOICE in
    1)
        DB_TYPE="mysql"
        ;;
    2)
        DB_TYPE="postgresql"
        ;;
    3)
        DB_TYPE="mongodb"
        ;;
    *)
        print_error "Invalid choice. Exiting."
        exit 1
        ;;
esac

# Set database password
read -sp "Enter password for database user 'ldn_user': " DB_PASSWORD
echo

# Confirm installation
echo
print_message "Installation will proceed with the following settings:"
read -sp "Enter password for database user 'ldn_user': " DB_PASSWORD
echo

# Confirm installation
echo
print_message "Installation will proceed with the following settings:"
echo "Domain: $DOMAIN_NAME"
echo "Database: $DB_TYPE"
echo
read -p "Continue with installation? (y/n): " CONFIRM

if [[ $CONFIRM != "y" && $CONFIRM != "Y" ]]; then
    print_message "Installation cancelled"
    exit 0
fi

#############################################################################
#                             APT
#############################################################################

# Update system
print_message "Updating system packages..."
apt update && apt upgrade -y

# Install common dependencies
print_message "Installing common dependencies..."
# TODO review wget, uznip, git
apt install -y software-properties-common curl wget unzip git

# Install Apache
print_message "Installing Apache web server..."
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# Allow Apache through firewall
print_message "Configuring firewall for Apache..."
if command -v ufw &> /dev/null; then
    ufw allow in "Apache Full"
else
    print_warning "UFW not installed. Skipping firewall configuration."
fi

# Install PHP and common extensions
print_message "Installing PHP and extensions..."
apt install -y php php-cli php-fpm php-json php-common php-zip php-curl php-xml php-mbstring

#############################################################################
#                             APT - DATABASE
#############################################################################

# Install database specific packages
case $DB_TYPE in
    mysql)
        print_message "Installing MySQL server and PHP extensions..."
        apt install -y mysql-server php-mysql
        systemctl start mysql
        systemctl enable mysql

        print_message "Creating MySQL database and user..."
        mysql -e "CREATE DATABASE IF NOT EXISTS ldn_inbox;"
        mysql -e "CREATE USER IF NOT EXISTS 'ldn_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
        mysql -e "GRANT ALL PRIVILEGES ON ldn_inbox.* TO 'ldn_user'@'localhost';"
        mysql -e "FLUSH PRIVILEGES;"
        ;;

    postgresql)
        print_message "Installing PostgreSQL server and PHP extensions..."
        apt install -y postgresql postgresql-contrib php-pgsql
        systemctl start postgresql
        systemctl enable postgresql

        print_message "Creating PostgreSQL database and user..."
        sudo -u postgres psql -c "CREATE USER ldn_user WITH PASSWORD '$DB_PASSWORD';"
        sudo -u postgres psql -c "CREATE DATABASE ldn_inbox OWNER ldn_user;"
        ;;

    mongodb)
        print_message "Installing MongoDB server and PHP extensions..."
        apt install -y gnupg
        wget -qO - https://www.mongodb.org/static/pgp/server-6.0.asc | apt-key add -
        echo "deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/ubuntu $(lsb_release -cs)/mongodb-org/6.0 multiverse" | tee /etc/apt/sources.list.d/mongodb-org-6.0.list
        apt update
        apt install -y mongodb-org php-mongodb
        systemctl start mongod
        systemctl enable mongod
        ;;
esac
# Create web directory
print_message "Creating web directory..."
mkdir -p /var/www/ldn-inbox

# Configure Apache
print_message "Configuring Apache virtual host..."
cat > /etc/apache2/sites-available/ldn-inbox.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    ServerAdmin webmaster@$DOMAIN_NAME
    DocumentRoot /var/www/ldn-inbox

    <Directory /var/www/ldn-inbox>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/ldn-inbox-error.log
    CustomLog \${APACHE_LOG_DIR}/ldn-inbox-access.log combined
</VirtualHost>
EOF

# Enable site and necessary modules
print_message "Enabling Apache site and modules..."
a2ensite ldn-inbox.conf
a2enmod rewrite
systemctl restart apache2

# Set proper permissions
print_message "Setting file permissions..."
chown -R www-data:www-data /var/www/ldn-inbox
chmod -R 755 /var/www/ldn-inbox

# Create config file
print_message "Creating configuration file template..."
cat > /var/www/ldn-inbox/config.php << EOF
<?php
// LDN Inbox Configuration

return [
    'baseUrl' => 'http://$DOMAIN_NAME',
    'inboxPath' => '/inbox/',
    'maxNotificationSize' => 100 * 1024, // 100KB
    'supportedContentTypes' => [
        'application/ld+json',
        'application/activity+json',
        'application/json'
    ],
    // Database configuration
    'database' => [
        'type' => '$DB_TYPE',

        // MySQL configuration
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'ldn_inbox',
            'username' => 'ldn_user',
            'password' => '$DB_PASSWORD',
            'table' => 'notifications'
        ],

        // PostgreSQL configuration
        'postgresql' => [
            'host' => 'localhost',
            'port' => 5432,
            'dbname' => 'ldn_inbox',
            'username' => 'ldn_user',
            'password' => '$DB_PASSWORD',
            'table' => 'notifications'
        ],

        // MongoDB configuration
        'mongodb' => [
            'uri' => 'mongodb://localhost:27017',
            'database' => 'ldn_inbox',
            'collection' => 'notifications'
        ]
    ]
];
?>
EOF

# Ask about SSL
print_message "Installation complete!"
echo
read -p "Would you like to set up SSL with Let's Encrypt? (y/n): " SETUP_SSL

if [[ $SETUP_SSL == "y" || $SETUP_SSL == "Y" ]]; then
    print_message "Installing Certbot..."
    apt install -y certbot python3-certbot-apache

    print_message "Setting up SSL certificate..."
    certbot --apache -d $DOMAIN_NAME

    # Update config to use HTTPS
    sed -i "s|http://$DOMAIN_NAME|https://$DOMAIN_NAME|" /var/www/ldn-inbox/config.php
fi

# Final message
echo
print_message "LDN Inbox installation completed successfully!"
echo "Next steps:"
echo "1. Download and place the LDN Inbox PHP code in /var/www/ldn-inbox/"
echo "2. Run the database setup script: php setup.php $DB_TYPE"
echo "3. Make sure to test your installation"
echo
print_message "Thank you for using LDN Inbox!"
