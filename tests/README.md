# Research Management System - Test Suite

A comprehensive test suite for the Research Management System that includes unit tests, integration tests, and database tests.

## 📁 Test Structure

```
tests/
├── config/
│   └── TestConfig.php          # Test configuration and database settings
├── unit/
│   ├── UserTest.php           # Unit tests for User class
│   ├── StudentTest.php        # Unit tests for Student class
│   ├── ProjectTest.php        # Unit tests for Project class
│   └── ReadyForPublicationTest.php  # Unit tests for ReadyForPublication class
├── integration/
│   └── IntegrationTest.php    # Integration tests for module interactions
├── database/
│   └── DatabaseTest.php       # Database schema and integrity tests
├── uploads/                   # Test file uploads directory
├── logs/                      # Test execution logs
├── TestHelper.php             # Common test utilities and assertions
├── TestRunner.php             # Main test runner (CLI and Web interface)
└── README.md                  # This documentation
```

## 🚀 Quick Start

### Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB database server
- Access to create databases
- XAMPP/LAMP/WAMP environment (recommended)

### Setup Test Environment

1. **Navigate to the tests directory:**
   ```bash
   cd tests
   ```

2. **Setup test environment:**
   ```bash
   php TestRunner.php setup
   ```

3. **Run all tests:**
   ```bash
   php TestRunner.php
   ```

## 🧪 Running Tests

### Command Line Interface

#### Run All Tests
```bash
php TestRunner.php
# or
php TestRunner.php all
```

#### Run Specific Test Suite
```bash
php TestRunner.php "Database Tests"
php TestRunner.php "User Tests"
php TestRunner.php "Student Tests"
php TestRunner.php "Project Tests"
php TestRunner.php "Ready For Publication Tests"  
php TestRunner.php "Integration Tests"
```

#### Test Environment Management
```bash
# Setup test environment
php TestRunner.php setup

# Cleanup test environment
php TestRunner.php cleanup

# Show help
php TestRunner.php help
```

### Web Interface

1. **Access via browser:**
   ```
   http://localhost/htdocs/tests/TestRunner.php
   ```

2. **Available web actions:**
   - Run All Tests
   - Setup Test Environment
   - Cleanup Test Environment
   - Individual Test Suites

## 📋 Test Categories

### 1. Unit Tests

Test individual classes and their methods in isolation.

#### UserTest.php
- ✅ User creation and validation
- ✅ User authentication (login/logout)
- ✅ User updates and data integrity
- ✅ User role management
- ✅ User search functionality
- ✅ Password hashing and security

#### StudentTest.php
- ✅ Student creation with auto-generated IDs
- ✅ Student ID format validation (STU2025XXXX)
- ✅ Student updates and profile management
- ✅ Student-counselor-RBM relationships
- ✅ Student search and filtering
- ✅ Foreign key constraint validation

#### ProjectTest.php
- ✅ Project creation with auto-generated IDs
- ✅ Project ID format validation (PRJ2025XXXX)
- ✅ Project status management
- ✅ Project-mentor assignments
- ✅ Project-student assignments
- ✅ Project updates and lifecycle

#### ReadyForPublicationTest.php
- ✅ Publication creation from projects
- ✅ Publication workflow (pending → approved)
- ✅ Student-publication assignments
- ✅ Document validation and requirements
- ✅ Duplicate project prevention
- ✅ Publication statistics and reporting

### 2. Database Tests

Test database schema, constraints, and data integrity.

#### DatabaseTest.php
- ✅ Database connection and configuration
- ✅ Table structure validation
- ✅ Foreign key constraints verification
- ✅ Unique constraints testing
- ✅ Database triggers functionality
- ✅ Index optimization validation
- ✅ Data integrity and consistency checks

### 3. Integration Tests

Test interactions between different modules and complete workflows.

#### IntegrationTest.php
- ✅ Complete project workflow (creation → publication)
- ✅ Publication approval workflow
- ✅ User role permissions and access control
- ✅ Student-project assignment process
- ✅ Mentor-student relationship management
- ✅ Data consistency across modules
- ✅ Cascading operations and cleanup

## ⚙️ Configuration

### Test Database Settings

Edit `tests/config/TestConfig.php` to modify test settings:

```php
class TestConfig {
    // Test Database Configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'research_apps_test_db';
    const DB_USERNAME = 'root';
    const DB_PASSWORD = '';
    
    // Test Settings
    const VERBOSE_OUTPUT = true;
    const CLEANUP_AFTER_TESTS = true;
    
    // Test Data Settings
    const TEST_USER_EMAIL = 'test@example.com';
    const TEST_USER_PASSWORD = 'test123';
}
```

### Important Notes

- **Separate Test Database:** Tests use `research_apps_test_db` (separate from production)
- **Automatic Cleanup:** Test data is automatically cleaned up after tests
- **Safe Testing:** No impact on production data or database
- **Comprehensive Logging:** All test activities are logged with timestamps

## 📊 Test Results

### Console Output Example

```
================================================================================
🚀 RESEARCH MANAGEMENT SYSTEM - TEST SUITE
================================================================================
Test Database: research_apps_test_db
Start Time: 2025-01-30 17:30:15
PHP Version: 8.1.0
================================================================================

============================================================
🧪 Running: Database Tests
============================================================
✅ Database Connection - PASS
✅ Table Structure - PASS
✅ Foreign Key Constraints - PASS
✅ Unique Constraints - PASS
✅ Database Triggers - PASS
✅ Database Indexes - PASS
✅ Data Integrity - PASS

============================================================
🧪 Running: User Tests
============================================================
✅ User Creation - PASS
✅ User Authentication - PASS
✅ User Update - PASS
✅ User Validation - PASS
✅ User Deletion - PASS
✅ User Search - PASS
✅ User Roles - PASS

... (additional test results)

================================================================================
📊 TEST RESULTS SUMMARY
================================================================================
Database Connection                                      ✅ PASS
Table Structure                                          ✅ PASS
Foreign Key Constraints                                  ✅ PASS
... (all test results)

--------------------------------------------------------------------------------
Total Tests: 45 | Passed: 43 | Failed: 2
Success Rate: 95.6% | Duration: 12.34 seconds

⚠️  2 tests failed. Please check the errors above.
================================================================================
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Database Connection Failed
```
Error: Test database connection failed: SQLSTATE[HY000] [1045] Access denied
```
**Solution:** Check database credentials in `TestConfig.php`

#### 2. Test Database Already Exists
```
Error: Database 'research_apps_test_db' already exists
```
**Solution:** Run cleanup first: `php TestRunner.php cleanup`

#### 3. Missing Class Files
```
Error: Class 'Student' not found
```
**Solution:** Ensure all class files exist in the `classes/` directory

#### 4. Permission Denied
```
Error: Permission denied for directory 'tests/logs/'
```
**Solution:** Set proper directory permissions: `chmod 755 tests/`

### Debug Mode

Enable verbose logging by setting in `TestConfig.php`:
```php
const VERBOSE_OUTPUT = true;
```

Check test logs in `tests/logs/test_YYYY-MM-DD.log` for detailed debugging information.

## 🔒 Security Considerations

- **Isolated Environment:** Test database is completely separate from production
- **No Production Impact:** Tests never touch production data
- **Secure Credentials:** Use separate test database credentials
- **Data Cleanup:** All test data is automatically cleaned up
- **Safe Execution:** Tests can be run multiple times safely

## 📈 Test Coverage

### Current Coverage Areas

- ✅ **User Management:** Authentication, roles, permissions
- ✅ **Student Management:** Registration, profiles, assignments
- ✅ **Project Management:** Creation, status, assignments
- ✅ **Publication Workflow:** Submission, approval, tracking
- ✅ **Database Schema:** Structure, constraints, triggers
- ✅ **Data Integrity:** Foreign keys, unique constraints
- ✅ **Integration Workflows:** End-to-end processes

### Areas for Future Enhancement

- 🔄 **Performance Tests:** Load testing and benchmarking
- 🔄 **Security Tests:** Penetration testing and vulnerability scanning  
- 🔄 **API Tests:** RESTful API endpoint testing
- 🔄 **UI Tests:** Automated browser testing with Selenium
- 🔄 **Mobile Tests:** Responsive design and mobile compatibility

## 🤝 Contributing

### Adding New Tests

1. **Create test file in appropriate directory:**
   ```bash
   # Unit test
   tests/unit/NewClassTest.php
   
   # Integration test
   tests/integration/NewWorkflowTest.php
   
   # Database test
   tests/database/NewSchemaTest.php
   ```

2. **Follow test structure pattern:**
   ```php
   <?php
   require_once __DIR__ . '/../TestHelper.php';
   
   class NewClassTest {
       public function runTests() {
           $this->testFeatureOne();
           $this->testFeatureTwo();
       }
       
       public function testFeatureOne() {
           TestHelper::startTest('Feature One');
           try {
               // Test logic here
               TestHelper::assertTrue($condition, 'Error message');
               TestHelper::endTest(true);
           } catch (Exception $e) {
               TestHelper::endTest(false, $e->getMessage());
           }
       }
   }
   ?>
   ```

3. **Register in TestRunner.php:**
   ```php
   $this->testSuites = [
       // ... existing tests ...
       'New Class Tests' => new NewClassTest(),
   ];
   ```

### Test Conventions

- Use descriptive test names
- Include both positive and negative test cases
- Test edge cases and error conditions
- Clean up test data after each test
- Use meaningful assertion messages
- Follow the existing code style

## 📚 Documentation

- **API Documentation:** See individual class files for method documentation
- **Database Schema:** Check `database/schema.sql` for table structures
- **Configuration Guide:** Review `config/TestConfig.php` for all settings
- **Troubleshooting:** Check logs in `tests/logs/` directory

## 📞 Support

For issues related to the test suite:

1. **Check logs:** `tests/logs/test_YYYY-MM-DD.log`
2. **Run diagnostics:** `php TestRunner.php setup`
3. **Verify environment:** Ensure PHP, MySQL, and all dependencies are installed
4. **Clean and retry:** `php TestRunner.php cleanup && php TestRunner.php setup`

---

**Last Updated:** January 30, 2025  
**Version:** 1.0.0  
**Compatibility:** PHP 7.4+, MySQL 5.7+ 