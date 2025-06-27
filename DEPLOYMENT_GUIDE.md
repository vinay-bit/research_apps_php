# Research Apps - Deployment Guide

## Overview
This guide will help you deploy the Research Apps application to a live server. The application requires PHP, MySQL, and a web server (Apache/Nginx).

## Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- SSH access to your server
- Domain name (optional but recommended)

## Deployment Options

### Option 1: Shared Hosting (cPanel/Plesk)
### Option 2: VPS/Dedicated Server
### Option 3: Cloud Platforms (AWS, DigitalOcean, etc.)

---

## Option 1: Shared Hosting Deployment

### Step 1: Prepare Files for Upload
1. **Download/Export your project files**
2. **Create a zip file** of the entire `Research_apps` folder
3. **Exclude development files** (see exclusion list below)

### Step 2: Upload Files
1. **Access cPanel File Manager** or use FTP client
2. **Navigate to public_html** (or your domain's folder)
3. **Upload and extract** the project files
4. **Set correct folder structure**:
   ```
   public_html/
   ├── research_apps/
   │   ├── config/
   │   ├── classes/
   │   ├── includes/
   │   ├── users/
   │   ├── students/
   │   ├── Apps/
   │   └── ...
   ```

### Step 3: Database Setup
1. **Create MySQL Database** in cPanel
2. **Create Database User** with full privileges
3. **Note down**:
   - Database name
   - Database username
   - Database password
   - Database host (usually localhost)

### Step 4: Configure Database Connection
1. **Edit `config/database.php`**
2. **Update database credentials**
3. **Test connection**

### Step 5: Initialize Database
1. **Run setup script** via browser: `yourdomain.com/research_apps/setup_database.php`
2. **Verify tables created**
3. **Test login** with default credentials

---

## Option 2: VPS/Dedicated Server Deployment

### Step 1: Server Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP stack
sudo apt install apache2 mysql-server php php-mysql php-mbstring php-xml php-curl -y

# Enable Apache modules
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 2: Database Setup
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE research_apps_db;
CREATE USER 'research_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON research_apps_db.* TO 'research_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Deploy Application
```bash
# Navigate to web directory
cd /var/www/html

# Clone or upload your application
sudo git clone <your-repo-url> research_apps
# OR upload via SCP/SFTP

# Set permissions
sudo chown -R www-data:www-data research_apps/
sudo chmod -R 755 research_apps/
```

### Step 4: Configure Virtual Host (Optional)
```bash
# Create virtual host file
sudo nano /etc/apache2/sites-available/research-apps.conf
```

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/research_apps
    
    <Directory /var/www/html/research_apps>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/research-apps_error.log
    CustomLog ${APACHE_LOG_DIR}/research-apps_access.log combined
</VirtualHost>
```

```bash
# Enable site and restart Apache
sudo a2ensite research-apps.conf
sudo systemctl restart apache2
```

---

## Configuration Files to Update

### 1. Database Configuration (`config/database.php`)
```php
<?php
class Database {
    private $host = "localhost";           // Your database host
    private $db_name = "research_apps_db"; // Your database name
    private $username = "your_db_user";    // Your database username
    private $password = "your_db_password"; // Your database password
    // ... rest of the class
}
?>
```

### 2. Create Production Environment File
Create `.env` file (optional):
```
DB_HOST=localhost
DB_NAME=research_apps_db
DB_USER=your_db_user
DB_PASS=your_db_password
APP_URL=https://yourdomain.com/research_apps
```

### 3. Update File Paths (if needed)
Check and update any absolute paths in:
- `includes/sidebar.php`
- `includes/navbar.php`
- All navigation links

---

## Security Considerations

### 1. Remove Development Files
Before deployment, remove these files:
```
setup_database.php (after initial setup)
DEPLOYMENT_GUIDE.md
README.md (if contains sensitive info)
.git/ (if using Git)
Any test_*.php files
```

### 2. Secure Database
- Use strong passwords
- Limit database user privileges
- Enable SSL for database connections (if available)

### 3. File Permissions
```bash
# Set proper permissions
chmod 644 config/database.php
chmod 755 directories/
chmod 644 *.php files
```

### 4. Hide Sensitive Files
Create `.htaccess` file in root:
```apache
# Deny access to sensitive files
<Files "database.php">
    Deny from all
</Files>

<Files ".env">
    Deny from all
</Files>

# Enable clean URLs (optional)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## SSL Certificate Setup

### For cPanel/Shared Hosting:
1. **Let's Encrypt** (usually free and auto-renewable)
2. **Upload custom certificate** if you have one

### For VPS/Dedicated Server:
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get SSL certificate
sudo certbot --apache -d yourdomain.com

# Auto-renewal (add to crontab)
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## Post-Deployment Checklist

### 1. Database Initialization
- [ ] Run `setup_database.php`
- [ ] Verify all tables created
- [ ] Test default login credentials
- [ ] Create additional users if needed

### 2. Functionality Testing
- [ ] Login/logout works
- [ ] User management (create/edit/delete)
- [ ] Student management (create/edit/list)
- [ ] All navigation links work
- [ ] File uploads work (if any)

### 3. Security Verification
- [ ] Remove setup files
- [ ] Database credentials secure
- [ ] File permissions correct
- [ ] SSL certificate active
- [ ] Backup strategy in place

### 4. Performance Optimization
- [ ] Enable PHP OPcache
- [ ] Configure Apache/Nginx caching
- [ ] Optimize database queries
- [ ] Monitor server resources

---

## Backup Strategy

### 1. Database Backup
```bash
# Create backup script
#!/bin/bash
mysqldump -u username -p research_apps_db > backup_$(date +%Y%m%d).sql
```

### 2. File Backup
```bash
# Backup application files
tar -czf research_apps_backup_$(date +%Y%m%d).tar.gz /var/www/html/research_apps/
```

### 3. Automated Backups
Set up cron jobs for regular backups:
```bash
# Daily database backup at 2 AM
0 2 * * * /path/to/backup_script.sh

# Weekly file backup
0 3 * * 0 /path/to/file_backup_script.sh
```

---

## Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Check credentials in `config/database.php`
   - Verify database server is running
   - Check firewall settings

2. **Permission Denied Errors**
   - Set correct file permissions
   - Check Apache/Nginx user ownership

3. **404 Errors**
   - Verify file paths
   - Check .htaccess rules
   - Ensure mod_rewrite is enabled

4. **PHP Errors**
   - Check PHP version compatibility
   - Enable error logging
   - Verify required PHP extensions

### Log Files to Check:
- Apache: `/var/log/apache2/error.log`
- PHP: `/var/log/php_errors.log`
- MySQL: `/var/log/mysql/error.log`

---

## Maintenance

### Regular Tasks:
1. **Update dependencies** regularly
2. **Monitor disk space** and logs
3. **Review security logs**
4. **Test backups** periodically
5. **Update SSL certificates**
6. **Monitor application performance**

---

## Support

For deployment assistance:
1. Check server logs for specific errors
2. Verify PHP and MySQL versions
3. Test database connectivity
4. Review file permissions

---

**Note**: Always test the deployment on a staging environment before going live with production data. 