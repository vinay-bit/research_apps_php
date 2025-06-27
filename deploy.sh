#!/bin/bash

# Research Apps Deployment Script
# This script helps deploy the Research Apps application to a Linux server

set -e  # Exit on any error

# Configuration
APP_NAME="research_apps"
DEPLOY_DIR="/var/www/html/$APP_NAME"
BACKUP_DIR="/var/backups/$APP_NAME"
DB_NAME="research_apps_db"
DB_USER="research_user"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_error "This script should not be run as root for security reasons."
        print_status "Please run as a regular user with sudo privileges."
        exit 1
    fi
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if Apache is installed
    if ! command -v apache2 &> /dev/null; then
        print_error "Apache2 is not installed. Please install it first."
        exit 1
    fi
    
    # Check if MySQL is installed
    if ! command -v mysql &> /dev/null; then
        print_error "MySQL is not installed. Please install it first."
        exit 1
    fi
    
    # Check if PHP is installed
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed. Please install it first."
        exit 1
    fi
    
    print_status "All prerequisites are met."
}

# Function to create backup
create_backup() {
    if [ -d "$DEPLOY_DIR" ]; then
        print_status "Creating backup of existing installation..."
        sudo mkdir -p "$BACKUP_DIR"
        sudo cp -r "$DEPLOY_DIR" "$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S)"
        print_status "Backup created successfully."
    fi
}

# Function to setup database
setup_database() {
    print_status "Setting up database..."
    
    # Prompt for MySQL root password
    read -s -p "Enter MySQL root password: " MYSQL_ROOT_PASS
    echo
    
    # Prompt for new database user password
    read -s -p "Enter password for database user '$DB_USER': " DB_PASS
    echo
    
    # Create database and user
    mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    print_status "Database setup completed."
    
    # Save database credentials for later use
    echo "DB_HOST=localhost" > /tmp/db_config
    echo "DB_NAME=$DB_NAME" >> /tmp/db_config
    echo "DB_USER=$DB_USER" >> /tmp/db_config
    echo "DB_PASS=$DB_PASS" >> /tmp/db_config
}

# Function to deploy application files
deploy_files() {
    print_status "Deploying application files..."
    
    # Create deployment directory
    sudo mkdir -p "$DEPLOY_DIR"
    
    # Copy files (assuming script is run from project root)
    sudo cp -r . "$DEPLOY_DIR/"
    
    # Set proper ownership and permissions
    sudo chown -R www-data:www-data "$DEPLOY_DIR"
    sudo chmod -R 755 "$DEPLOY_DIR"
    sudo chmod 644 "$DEPLOY_DIR"/*.php
    sudo chmod 644 "$DEPLOY_DIR/config/"*.php
    
    print_status "Files deployed successfully."
}

# Function to configure database connection
configure_database() {
    print_status "Configuring database connection..."
    
    # Read database credentials
    source /tmp/db_config
    
    # Update database configuration
    sudo tee "$DEPLOY_DIR/config/database.php" > /dev/null <<EOF
<?php
class Database {
    private \$host = "$DB_HOST";
    private \$db_name = "$DB_NAME";
    private \$username = "$DB_USER";
    private \$password = "$DB_PASS";
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$this->conn = new PDO(
                "mysql:host=" . \$this->host . ";dbname=" . \$this->db_name,
                \$this->username,
                \$this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException \$exception) {
            error_log("Connection error: " . \$exception->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
        
        return \$this->conn;
    }
}
?>
EOF
    
    # Clean up temporary file
    rm /tmp/db_config
    
    print_status "Database configuration updated."
}

# Function to initialize database
initialize_database() {
    print_status "Initializing database..."
    
    # Run database setup via web interface
    print_status "Please visit http://your-domain.com/$APP_NAME/setup_database.php to initialize the database."
    print_warning "After initialization, remember to delete the setup_database.php file for security."
}

# Function to configure Apache
configure_apache() {
    print_status "Configuring Apache..."
    
    # Enable required modules
    sudo a2enmod rewrite
    sudo a2enmod headers
    sudo a2enmod expires
    sudo a2enmod deflate
    
    # Create virtual host configuration
    sudo tee "/etc/apache2/sites-available/$APP_NAME.conf" > /dev/null <<EOF
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot $DEPLOY_DIR
    
    <Directory $DEPLOY_DIR>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/$APP_NAME-error.log
    CustomLog \${APACHE_LOG_DIR}/$APP_NAME-access.log combined
</VirtualHost>
EOF
    
    # Enable the site
    sudo a2ensite "$APP_NAME.conf"
    
    # Restart Apache
    sudo systemctl restart apache2
    
    print_status "Apache configuration completed."
}

# Function to setup SSL (optional)
setup_ssl() {
    read -p "Do you want to setup SSL certificate with Let's Encrypt? (y/n): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Setting up SSL certificate..."
        
        # Install Certbot
        sudo apt update
        sudo apt install -y certbot python3-certbot-apache
        
        # Get domain name
        read -p "Enter your domain name: " DOMAIN_NAME
        
        # Get SSL certificate
        sudo certbot --apache -d "$DOMAIN_NAME"
        
        print_status "SSL certificate setup completed."
    fi
}

# Function to cleanup
cleanup() {
    print_status "Cleaning up..."
    
    # Remove development files
    sudo rm -f "$DEPLOY_DIR/deploy.sh"
    sudo rm -f "$DEPLOY_DIR/DEPLOYMENT_GUIDE.md"
    sudo rm -f "$DEPLOY_DIR/config/database.prod.php"
    
    print_status "Cleanup completed."
}

# Function to run post-deployment tests
run_tests() {
    print_status "Running post-deployment tests..."
    
    # Test Apache configuration
    if sudo apache2ctl configtest; then
        print_status "Apache configuration is valid."
    else
        print_error "Apache configuration has errors."
        exit 1
    fi
    
    # Test if application is accessible
    if curl -s -o /dev/null -w "%{http_code}" "http://localhost/$APP_NAME/" | grep -q "200"; then
        print_status "Application is accessible."
    else
        print_warning "Application may not be accessible. Please check manually."
    fi
    
    print_status "Tests completed."
}

# Function to display final instructions
display_final_instructions() {
    print_status "Deployment completed successfully!"
    echo
    print_status "Next steps:"
    echo "1. Visit http://your-domain.com/$APP_NAME/setup_database.php to initialize the database"
    echo "2. Test the application with default credentials:"
    echo "   - Admin: admin / admin123"
    echo "   - Mentor: mentor / mentor123"
    echo "   - Councillor: councillor / councillor123"
    echo "   - RBM: rbm / rbm123"
    echo "3. Delete setup_database.php after successful initialization"
    echo "4. Update domain name in Apache configuration and .htaccess"
    echo "5. Setup SSL certificate if not done already"
    echo "6. Configure regular backups"
    echo
    print_warning "Important security reminders:"
    echo "- Change default passwords immediately"
    echo "- Remove setup files after initialization"
    echo "- Keep the system updated"
    echo "- Monitor logs regularly"
}

# Main deployment function
main() {
    print_status "Starting Research Apps deployment..."
    
    check_root
    check_prerequisites
    create_backup
    setup_database
    deploy_files
    configure_database
    configure_apache
    setup_ssl
    cleanup
    run_tests
    display_final_instructions
    
    print_status "Deployment script completed!"
}

# Run main function
main "$@" 