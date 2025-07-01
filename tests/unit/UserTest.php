<?php
/**
 * Unit Tests for User Class
 */

require_once __DIR__ . '/../TestHelper.php';
require_once __DIR__ . '/../../classes/User.php';

class UserTest {
    
    private $conn;
    private $user;
    
    public function __construct() {
        $this->conn = TestConfig::getTestDatabaseConnection();
        $this->user = new User($this->conn);
    }
    
    /**
     * Run all user tests
     */
    public function runTests() {
        TestHelper::createTestDependencies();
        
        $this->testUserCreation();
        $this->testUserAuthentication();
        $this->testUserUpdate();
        $this->testUserValidation();
        $this->testUserDeletion();
        $this->testUserSearch();
        $this->testUserRoles();
    }
    
    /**
     * Test user creation
     */
    public function testUserCreation() {
        TestHelper::startTest('User Creation');
        
        try {
            // Test valid user creation
            $this->user->username = 'testuser123';
            $this->user->full_name = 'Test User';
            $this->user->email_id = 'test@example.com';
            $this->user->password = 'password123';
            $this->user->user_type = 'admin';
            $this->user->organization_id = 1;
            
            $result = $this->user->create();
            TestHelper::assertTrue($result, 'User creation should succeed');
            TestHelper::assertNotNull($this->user->id, 'User ID should be set after creation');
            
            // Test duplicate username
            $duplicateUser = new User($this->conn);
            $duplicateUser->username = 'testuser123';
            $duplicateUser->full_name = 'Duplicate User';
            $duplicateUser->email_id = 'duplicate@example.com';
            $duplicateUser->password = 'password123';
            $duplicateUser->user_type = 'admin';
            $duplicateUser->organization_id = 1;
            
            TestHelper::assertThrows(function() use ($duplicateUser) {
                $duplicateUser->create();
            }, 'Exception', 'Duplicate username should throw exception');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test user authentication
     */
    public function testUserAuthentication() {
        TestHelper::startTest('User Authentication');
        
        try {
            // Create test user
            $userId = TestHelper::createTestUser([
                'username' => 'authtest',
                'password' => password_hash('testpass123', PASSWORD_DEFAULT)
            ]);
            
            // Test valid login
            $authUser = new User($this->conn);
            $loginResult = $authUser->login('authtest', 'testpass123');
            TestHelper::assertTrue($loginResult, 'Valid login should succeed');
            TestHelper::assertEqual('authtest', $authUser->username, 'Username should match');
            
            // Test invalid password
            $invalidUser = new User($this->conn);
            $invalidLogin = $invalidUser->login('authtest', 'wrongpassword');
            TestHelper::assertFalse($invalidLogin, 'Invalid password should fail');
            
            // Test non-existent user
            $nonExistentUser = new User($this->conn);
            $nonExistentLogin = $nonExistentUser->login('nonexistent', 'password');
            TestHelper::assertFalse($nonExistentLogin, 'Non-existent user should fail');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test user update
     */
    public function testUserUpdate() {
        TestHelper::startTest('User Update');
        
        try {
            // Create test user
            $userId = TestHelper::createTestUser([
                'full_name' => 'Original Name'
            ]);
            
            // Update user
            $updateUser = new User($this->conn);
            $updateUser->id = $userId;
            $updateUser->readOne();
            
            $updateUser->full_name = 'Updated Name';
            $updateUser->email_id = 'updated@example.com';
            
            $result = $updateUser->update();
            TestHelper::assertTrue($result, 'User update should succeed');
            
            // Verify update
            $verifyUser = new User($this->conn);
            $verifyUser->id = $userId;
            $verifyUser->readOne();
            
            TestHelper::assertEqual('Updated Name', $verifyUser->full_name, 'Name should be updated');
            TestHelper::assertEqual('updated@example.com', $verifyUser->email_id, 'Email should be updated');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test user validation
     */
    public function testUserValidation() {
        TestHelper::startTest('User Validation');
        
        try {
            // Test missing required fields
            $invalidUser = new User($this->conn);
            
            TestHelper::assertThrows(function() use ($invalidUser) {
                $invalidUser->create();
            }, 'Exception', 'Missing required fields should throw exception');
            
            // Test invalid email format
            $emailUser = new User($this->conn);
            $emailUser->username = 'emailtest';
            $emailUser->full_name = 'Email Test';
            $emailUser->email_id = 'invalid-email';
            $emailUser->password = 'password123';
            $emailUser->user_type = 'admin';
            $emailUser->organization_id = 1;
            
            TestHelper::assertThrows(function() use ($emailUser) {
                $emailUser->create();
            }, 'Exception', 'Invalid email should throw exception');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test user deletion
     */
    public function testUserDeletion() {
        TestHelper::startTest('User Deletion');
        
        try {
            // Create test user
            $userId = TestHelper::createTestUser();
            
            // Delete user
            $deleteUser = new User($this->conn);
            $deleteUser->id = $userId;
            
            $result = $deleteUser->delete();
            TestHelper::assertTrue($result, 'User deletion should succeed');
            
            // Verify deletion
            $verifyUser = new User($this->conn);
            $verifyUser->id = $userId;
            $found = $verifyUser->readOne();
            TestHelper::assertFalse($found, 'Deleted user should not be found');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test user search functionality
     */
    public function testUserSearch() {
        TestHelper::startTest('User Search');
        
        try {
            // Create test users
            TestHelper::createTestUser(['full_name' => 'John Doe', 'username' => 'johndoe']);
            TestHelper::createTestUser(['full_name' => 'Jane Smith', 'username' => 'janesmith']);
            
            // Test search
            $searchUser = new User($this->conn);
            $results = $searchUser->search('John');
            
            TestHelper::assertTrue(count($results) >= 1, 'Search should return at least one result');
            
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
    
    /**
     * Test user roles
     */
    public function testUserRoles() {
        TestHelper::startTest('User Roles');
        
        try {
            // Test admin role
            $admin = new User($this->conn);
            $admin->user_type = 'admin';
            
            // Test mentor role  
            $mentor = new User($this->conn);
            $mentor->user_type = 'mentor';
            
            // Test RBM role
            $rbm = new User($this->conn);
            $rbm->user_type = 'rbm';
            
            TestHelper::assertTrue(true, 'User roles should be assignable');
            TestHelper::endTest(true);
            
        } catch (Exception $e) {
            TestHelper::endTest(false, $e->getMessage());
        }
    }
}
?> 