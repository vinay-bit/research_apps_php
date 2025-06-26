# Student Progress Monitor

A comprehensive PHP-based web application built with MySQL database to monitor students and their project progress. This application uses a modern Bootstrap-based admin template for an intuitive user interface.

## Features

### User Management System
- **Three User Types:**
  - **Admin**: System administrators with full access
    - Fields: Full Name, Username, Password, Department
  - **Mentor**: Project mentors and guides
    - Fields: Full Name, Username, Password, Specialization, Organization (dropdown), Department
  - **Councillor**: External advisors and counselors  
    - Fields: Full Name, Organization, MOU Signed (Yes/No), MOU Drive Link (if signed)

### Current Functionality
- ✅ User Authentication (Login/Logout)
- ✅ Role-based Access Control
- ✅ Dynamic User Creation Forms
- ✅ User Management (Create, Read, Update, Delete)
- ✅ User Filtering by Type
- ✅ Responsive Dashboard
- ✅ Modern UI with Bootstrap Template

### Coming Soon
- 🔄 Student Management Module
- 🔄 Project Management System
- 🔄 Progress Tracking
- 🔄 Reports and Analytics

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5
- **Template**: Sneat Admin Template
- **Icons**: Boxicons
- **Authentication**: Session-based

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional)

### Setup Instructions

1. **Clone or Download**
   ```bash
   git clone [repository-url]
   cd student-progress-monitor
   ```

2. **Database Setup**
   - Create a MySQL database named `student_progress_db`
   - Import the database schema:
   ```bash
   mysql -u root -p student_progress_db < database/schema.sql
   ```

3. **Configure Database Connection**
   - Edit `config/database.php`
   - Update database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'student_progress_db';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

4. **Set Permissions**
   ```bash
   chmod 755 -R .
   chmod 777 -R uploads/ # If upload directory exists
   ```

5. **Access Application**
   - Open your web browser
   - Navigate to: `http://localhost/student-progress-monitor/login.php`

## Default Login Credentials

- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Important**: Change the default admin password after first login!

## File Structure

```
student-progress-monitor/
├── Apps/                   # Template assets
│   ├── assets/            # CSS, JS, images
│   └── html/             # Original template files
├── classes/               # PHP classes
│   └── User.php          # User management class
├── config/                # Configuration files
│   └── database.php      # Database connection
├── database/              # Database files
│   └── schema.sql        # Database structure
├── includes/              # PHP includes
│   ├── auth.php          # Authentication functions
│   ├── sidebar.php       # Navigation sidebar
│   ├── navbar.php        # Top navigation
│   └── footer.php        # Footer component
├── users/                 # User management pages
│   ├── list.php          # User listing
│   ├── create.php        # Create user form
│   ├── edit.php          # Edit user form
│   └── view.php          # View user details
├── dashboard.php          # Main dashboard
├── login.php             # Login page
├── logout.php            # Logout handler
└── README.md             # This file
```

## User Types & Fields

### 1. Admin
- **Full Name**: Administrator's complete name
- **Username**: Unique login identifier
- **Password**: Secure password (minimum 6 characters)
- **Department**: Selected from dropdown (Computer Science, IT, etc.)

### 2. Mentor  
- **Full Name**: Mentor's complete name
- **Username**: Unique login identifier
- **Password**: Secure password (minimum 6 characters)
- **Specialization**: Area of expertise (e.g., Machine Learning, Web Development)
- **Organization**: Selected from dropdown (Tech Corp, Innovation Hub, etc.)
- **Department**: Selected from dropdown

### 3. Councillor
- **Full Name**: Councillor's complete name
- **Organization**: Organization name (free text)
- **MOU Signed**: Yes/No checkbox
- **MOU Drive Link**: Google Drive link to signed MOU document (if MOU is signed)

## Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- CSRF protection
- Input validation and sanitization
- Role-based access control
- SQL injection prevention with prepared statements

## Database Schema

### Tables
- `users`: Main user table with all user types
- `departments`: Department master data
- `organizations`: Organization master data  
- `user_sessions`: User session management

### Key Relationships
- Users can belong to departments (Admin, Mentor)
- Mentors can be associated with organizations
- Councillors have their own organization field

## Usage

### Admin Functions
- Create, edit, delete any user
- View all users with filtering
- Manage system settings
- Access all modules

### Mentor Functions  
- View user listings
- Access assigned projects (coming soon)
- Update project progress (coming soon)

### Councillor Functions
- View assigned students (coming soon)
- Provide counseling updates (coming soon)

## Development

### Adding New Features
1. Create necessary database tables in `database/schema.sql`
2. Add PHP classes in `classes/` directory
3. Create UI pages following the template structure
4. Update navigation in `includes/sidebar.php`

### Template Customization
- Modify `Apps/assets/css/demo.css` for custom styles
- Update `includes/` files for layout changes
- Add new pages following existing structure

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Login Issues**
   - Use default credentials: admin/admin123
   - Check if user exists in database
   - Verify session settings

3. **Permission Errors**
   - Check file permissions
   - Ensure web server has read access

4. **Template Not Loading**
   - Verify `Apps/assets/` path is correct
   - Check for missing CSS/JS files

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Check the troubleshooting section
- Review the code documentation
- Open an issue on GitHub

---

**Version**: 1.0.0  
**Last Updated**: <?php echo date('Y-m-d'); ?>  
**Status**: Active Development 