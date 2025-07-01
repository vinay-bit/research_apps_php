<?php
/**
 * Test Helper Class
 * Provides common utilities and helper methods for all tests
 */

require_once 'config/TestConfig.php';

class TestHelper {
    
    private static $testResults = [];
    private static $currentTest = '';
    private static $testStartTime;
    
    /**
     * Initialize test environment
     */
    public static function initialize() {
        TestConfig::log("Initializing test environment...");
        
        // Create test database
        TestConfig::createTestDatabase();
        
        // Create necessary directories
        TestConfig::getTestUploadsDir();
        TestConfig::getTestLogsDir();
        
        // Reset test results
        self::$testResults = [];
        self::$testStartTime = microtime(true);
        
        TestConfig::log("Test environment initialized successfully");
    }
    
    /**
     * Cleanup test environment
     */
    public static function cleanup() {
        TestConfig::log("Cleaning up test environment...");
        
        if (TestConfig::CLEANUP_AFTER_TESTS) {
            TestConfig::cleanTestDatabase();
        }
        
        TestConfig::log("Test environment cleaned up");
    }
    
    /**
     * Start a test
     */
    public static function startTest($testName) {
        self::$currentTest = $testName;
        TestConfig::log("Starting test: $testName", 'TEST');
    }
    
    /**
     * End a test
     */
    public static function endTest($passed, $message = '') {
        $result = $passed ? 'PASS' : 'FAIL';
        $status = $passed ? '✅' : '❌';
        
        self::$testResults[self::$currentTest] = [
            'result' => $result,
            'message' => $message,
            'passed' => $passed
        ];
        
        TestConfig::log("$status " . self::$currentTest . " - $result" . ($message ? " ($message)" : ''), 'TEST');
    }
    
    /**
     * Assert that a condition is true
     */
    public static function assertTrue($condition, $message = 'Assertion failed') {
        if (!$condition) {
            throw new Exception($message);
        }
        return true;
    }
    
    /**
     * Assert that a condition is false
     */
    public static function assertFalse($condition, $message = 'Assertion failed') {
        if ($condition) {
            throw new Exception($message);
        }
        return true;
    }
    
    /**
     * Assert that two values are equal
     */
    public static function assertEqual($expected, $actual, $message = 'Values are not equal') {
        if ($expected !== $actual) {
            $expectedType = gettype($expected);
            $actualType = gettype($actual);
            $expectedLen = is_string($expected) ? strlen($expected) : 'N/A';
            $actualLen = is_string($actual) ? strlen($actual) : 'N/A';
            throw new Exception("$message. Expected: '$expected' (type: $expectedType, len: $expectedLen), Actual: '$actual' (type: $actualType, len: $actualLen)");
        }
        return true;
    }
    
    /**
     * Assert that two values are not equal
     */
    public static function assertNotEqual($expected, $actual, $message = 'Values should not be equal') {
        if ($expected === $actual) {
            throw new Exception("$message. Both values are: '$expected'");
        }
        return true;
    }
    
    /**
     * Assert that a value is null
     */
    public static function assertNull($value, $message = 'Value is not null') {
        if ($value !== null) {
            throw new Exception("$message. Value: '$value'");
        }
        return true;
    }
    
    /**
     * Assert that a value is not null
     */
    public static function assertNotNull($value, $message = 'Value is null') {
        if ($value === null) {
            throw new Exception($message);
        }
        return true;
    }
    
    /**
     * Assert that an exception is thrown
     */
    public static function assertThrows($callback, $expectedExceptionClass = 'Exception', $message = 'Expected exception was not thrown') {
        try {
            $callback();
            throw new Exception($message);
        } catch (Exception $e) {
            if (!($e instanceof $expectedExceptionClass)) {
                throw new Exception("Expected $expectedExceptionClass but got " . get_class($e));
            }
        }
        return true;
    }
    
    /**
     * Create test user
     */
    public static function createTestUser($userData = []) {
        $defaultData = [
            'username' => 'testuser_' . uniqid(),
            'full_name' => TestConfig::TEST_STUDENT_NAME,
            'email_id' => TestConfig::TEST_USER_EMAIL,
            'password' => password_hash(TestConfig::TEST_USER_PASSWORD, PASSWORD_DEFAULT),
            'user_type' => 'admin',
            'organization_id' => 1,
            'primary_contact_id' => null
        ];
        
        $userData = array_merge($defaultData, $userData);
        
        $conn = TestConfig::getTestDatabaseConnection();
        $query = "INSERT INTO users (username, full_name, email_id, password, user_type, organization_id, primary_contact_id) 
                  VALUES (:username, :full_name, :email_id, :password, :user_type, :organization_id, :primary_contact_id)";
        $stmt = $conn->prepare($query);
        $stmt->execute($userData);
        
        return $conn->lastInsertId();
    }
    
    /**
     * Create test student
     */
    public static function createTestStudent($studentData = []) {
        $defaultData = [
            'full_name' => TestConfig::TEST_STUDENT_NAME,
            'email_address' => TestConfig::TEST_USER_EMAIL,
            'grade' => '12th',
            'board_id' => 1,
            'counselor_id' => 1,
            'rbm_id' => 1,
            'application_year' => date('Y')
        ];
        
        $studentData = array_merge($defaultData, $studentData);
        
        $conn = TestConfig::getTestDatabaseConnection();
        $query = "INSERT INTO students (student_id, full_name, email_address, grade, board_id, counselor_id, rbm_id, application_year) 
                  VALUES (:student_id, :full_name, :email_address, :grade, :board_id, :counselor_id, :rbm_id, :application_year)";
        
        // Generate student ID if not provided
        if (!isset($studentData['student_id'])) {
            $studentData['student_id'] = 'STU' . date('Y') . sprintf('%04d', rand(1000, 9999));
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute($studentData);
        
        return $conn->lastInsertId();
    }
    
    /**
     * Create test project
     */
    public static function createTestProject($projectData = []) {
        $defaultData = [
            'project_name' => TestConfig::TEST_PROJECT_NAME,
            'description' => 'Test project description',
            'status_id' => 1,
            'lead_mentor_id' => 1,
            'subject_id' => 1,
            'rbm_id' => 1,
            'created_by' => 1
        ];
        
        $projectData = array_merge($defaultData, $projectData);
        
        $conn = TestConfig::getTestDatabaseConnection();
        $query = "INSERT INTO projects (project_id, project_name, description, status_id, lead_mentor_id, subject_id, rbm_id, created_by) 
                  VALUES (:project_id, :project_name, :description, :status_id, :lead_mentor_id, :subject_id, :rbm_id, :created_by)";
        
        // Generate project ID if not provided
        if (!isset($projectData['project_id'])) {
            $projectData['project_id'] = 'PRJ' . date('Y') . sprintf('%04d', rand(1000, 9999));
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute($projectData);
        
        return $conn->lastInsertId();
    }
    
    /**
     * Create test data for dependencies
     */
    public static function createTestDependencies() {
        $conn = TestConfig::getTestDatabaseConnection();
        
        // Create test organization
        $conn->exec("INSERT IGNORE INTO organizations (id, name) VALUES (1, 'Test Organization')");
        
        // Create test board
        $conn->exec("INSERT IGNORE INTO boards (id, name) VALUES (1, 'Test Board')");
        
        // Create test subject
        $conn->exec("INSERT IGNORE INTO subjects (id, subject_name) VALUES (1, 'Test Subject')");
        
        // Create test project status
        $conn->exec("INSERT IGNORE INTO project_statuses (id, status_name) VALUES (1, 'Test Status')");
        
        // Create test department
        $conn->exec("INSERT IGNORE INTO departments (id, name) VALUES (1, 'Test Department')");
    }
    
    /**
     * Get test results summary
     */
    public static function getResults() {
        $totalTests = count(self::$testResults);
        $passedTests = count(array_filter(self::$testResults, function($result) {
            return $result['passed'];
        }));
        $failedTests = $totalTests - $passedTests;
        $successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
        $duration = microtime(true) - self::$testStartTime;
        
        return [
            'total' => $totalTests,
            'passed' => $passedTests,
            'failed' => $failedTests,
            'success_rate' => $successRate,
            'duration' => $duration,
            'results' => self::$testResults
        ];
    }
    
    /**
     * Print test results summary
     */
    public static function printResults() {
        $results = self::getResults();
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        
        foreach ($results['results'] as $testName => $result) {
            $status = $result['passed'] ? '✅' : '❌';
            echo sprintf("%-50s %s %s\n", $testName, $status, $result['result']);
            if (!$result['passed'] && $result['message']) {
                echo "   Error: " . $result['message'] . "\n";
            }
        }
        
        echo str_repeat("-", 80) . "\n";
        echo sprintf("Total Tests: %d | Passed: %d | Failed: %d\n", 
            $results['total'], $results['passed'], $results['failed']);
        echo sprintf("Success Rate: %.1f%% | Duration: %.2f seconds\n", 
            $results['success_rate'], $results['duration']);
        
        if ($results['failed'] === 0) {
            echo "\n🎉 All tests passed successfully!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the errors above.\n";
        }
        
        echo str_repeat("=", 80) . "\n";
    }
}
?> 