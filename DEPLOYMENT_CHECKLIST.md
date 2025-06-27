# Research Apps - Deployment Checklist

## Pre-Deployment Preparation

### 1. Server Requirements ✓
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ installed  
- [ ] Apache/Nginx web server
- [ ] SSH access to server
- [ ] Domain name configured (optional)

### 2. Database Preparation ✓
- [ ] Create production database
- [ ] Create database user with appropriate privileges
- [ ] Note database credentials:
  - Host: ________________
  - Database: ________________
  - Username: ________________
  - Password: ________________

### 3. File Preparation ✓
- [ ] Update `config/database.php` with production credentials
- [ ] Review and update `.htaccess` file
- [ ] Remove development files (test_*.php, etc.)
- [ ] Create backup of current version

## Deployment Steps

### 4. File Upload ✓
- [ ] Upload files to server (via FTP/SCP/Git)
- [ ] Set correct file permissions (755 for directories, 644 for files)
- [ ] Verify file structure is correct

### 5. Database Setup ✓
- [ ] Run `setup_database.php` via browser
- [ ] Verify all tables are created
- [ ] Test database connection
- [ ] **IMPORTANT**: Delete `setup_database.php` after setup

### 6. Security Configuration ✓
- [ ] Verify `.htaccess` rules are working
- [ ] Test that sensitive files are protected
- [ ] Configure SSL certificate (recommended)
- [ ] Update default passwords

### 7. Application Testing ✓
- [ ] Test login with all user types:
  - [ ] Admin (admin/admin123)
  - [ ] Mentor (mentor/mentor123)
  - [ ] Councillor (councillor/councillor123)
  - [ ] RBM (rbm/rbm123)
- [ ] Test user management features
- [ ] Test student management features
- [ ] Verify all navigation links work
- [ ] Test responsive design on mobile

### 8. Performance & Security ✓
- [ ] Enable HTTPS (SSL certificate)
- [ ] Configure server caching
- [ ] Set up error logging
- [ ] Configure automatic backups
- [ ] Monitor server resources

## Post-Deployment

### 9. Final Steps ✓
- [ ] Change all default passwords
- [ ] Create additional admin users if needed
- [ ] Set up monitoring/alerts
- [ ] Document server configuration
- [ ] Test backup and restore procedures

### 10. Go Live ✓
- [ ] Update DNS records (if applicable)
- [ ] Announce to users
- [ ] Monitor for issues
- [ ] Collect user feedback

## Quick Deployment Commands

### For Shared Hosting:
1. Upload files via cPanel File Manager or FTP
2. Create database in cPanel
3. Update `config/database.php`
4. Visit `yourdomain.com/research_apps/setup_database.php`
5. Delete setup file

### For VPS/Dedicated Server:
```bash
# Make deployment script executable (Linux/Mac)
chmod +x deploy.sh

# Run deployment script
./deploy.sh
```

### For Windows Server (IIS):
1. Copy files to `C:\inetpub\wwwroot\research_apps\`
2. Configure IIS site
3. Set up database connection
4. Run setup script

## Default Login Credentials

**⚠️ CHANGE THESE IMMEDIATELY AFTER DEPLOYMENT**

- **Admin**: admin / admin123
- **Mentor**: mentor / mentor123  
- **Councillor**: councillor / councillor123
- **RBM**: rbm / rbm123

## Support URLs

- Application: `https://yourdomain.com/research_apps/`
- Admin Panel: `https://yourdomain.com/research_apps/dashboard.php`
- User Management: `https://yourdomain.com/research_apps/users/list.php`
- Student Management: `https://yourdomain.com/research_apps/students/list.php`

## Troubleshooting

### Common Issues:
1. **Database connection failed**: Check credentials in `config/database.php`
2. **404 errors**: Verify file paths and `.htaccess` configuration
3. **Permission denied**: Set correct file permissions (755/644)
4. **PHP errors**: Check PHP version and required extensions

### Log Files:
- Apache: `/var/log/apache2/error.log`
- PHP: Check `error_log` location in php.ini
- Application: Monitor browser console for JavaScript errors

## Emergency Contacts

- Server Administrator: ________________
- Database Administrator: ________________
- Application Developer: ________________

---

**Last Updated**: $(date)
**Deployed By**: ________________
**Deployment Date**: ________________ 