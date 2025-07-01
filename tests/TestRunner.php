<?php
/**
 * Main Test Runner
 * Executes all test suites and provides comprehensive results
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set execution time limit
set_time_limit(300); // 5 minutes

require_once 'TestHelper.php';
require_once 'unit/UserTest.php';
require_once 'unit/StudentTest.php';
require_once 'unit/ProjectTest.php';
require_once 'unit/ReadyForPublicationTest.php';
require_once 'database/DatabaseTest.php';
require_once 'integration/IntegrationTest.php';

class TestRunner {
    
    private $testSuites = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
        
        // Register test suite class names (instantiate later)
        $this->testSuites = [
            'Database Tests' => 'DatabaseTest',
            'User Tests' => 'UserTest',
            'Student Tests' => 'StudentTest',
            'Project Tests' => 'ProjectTest',
            'Ready For Publication Tests' => 'ReadyForPublicationTest',
            'Integration Tests' => 'IntegrationTest'
        ];
    }
    
    /**
     * Run all test suites
     */
    public function runAllTests() {
        $this->printHeader();
        
        try {
            // Initialize test environment
            TestHelper::initialize();
            
            // Run each test suite
            foreach ($this->testSuites as $suiteName => $testSuiteClass) {
                $this->runTestSuite($suiteName, $testSuiteClass);
            }
            
            // Print final results
            $this->printResults();
            
        } catch (Exception $e) {
            TestConfig::log("Fatal error during test execution: " . $e->getMessage(), 'ERROR');
            echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
        } finally {
            // Cleanup
            TestHelper::cleanup();
        }
    }
    
    /**
     * Run specific test suite
     */
    public function runTestSuite($suiteName, $testSuiteClass) {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🧪 Running: $suiteName\n";
        echo str_repeat("=", 60) . "\n";
        
        try {
            // Instantiate test suite class
            $testSuite = new $testSuiteClass();
            $testSuite->runTests();
        } catch (Exception $e) {
            TestConfig::log("Error in test suite '$suiteName': " . $e->getMessage(), 'ERROR');
            TestHelper::endTest(false, "Test suite failed: " . $e->getMessage());
        }
        
        echo str_repeat("-", 60) . "\n";
        echo "✅ Completed: $suiteName\n";
    }
    
    /**
     * Run specific test or test suite
     */
    public function runSpecificTest($testName) {
        $this->printHeader();
        
        try {
            TestHelper::initialize();
            
            if (isset($this->testSuites[$testName])) {
                $this->runTestSuite($testName, $this->testSuites[$testName]);
            } else {
                echo "❌ Test suite '$testName' not found!\n";
                echo "Available test suites:\n";
                foreach (array_keys($this->testSuites) as $suite) {
                    echo "  - $suite\n";
                }
                return false;
            }
            
            $this->printResults();
            
        } catch (Exception $e) {
            TestConfig::log("Fatal error during specific test execution: " . $e->getMessage(), 'ERROR');
            echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
        } finally {
            TestHelper::cleanup();
        }
        
        return true;
    }
    
    /**
     * Print test header
     */
    private function printHeader() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🚀 RESEARCH MANAGEMENT SYSTEM - TEST SUITE\n";
        echo str_repeat("=", 80) . "\n";
        echo "Test Database: " . TestConfig::DB_NAME . "\n";
        echo "Start Time: " . date('Y-m-d H:i:s') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo str_repeat("=", 80) . "\n";
    }
    
    /**
     * Print final results
     */
    private function printResults() {
        TestHelper::printResults();
        
        $duration = microtime(true) - $this->startTime;
        echo "\n⏱️  Total Execution Time: " . number_format($duration, 2) . " seconds\n";
        echo "📊 Test Database: " . TestConfig::DB_NAME . "\n";
        echo "📅 Completed at: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n";
    }
    
    /**
     * Create test database and run setup
     */
    public function setupTestEnvironment() {
        echo "🔧 Setting up test environment...\n";
        
        try {
            // Create test database
            TestConfig::createTestDatabase();
            echo "✅ Test database created successfully\n";
            
            // Create test directories
            TestConfig::getTestUploadsDir();
            TestConfig::getTestLogsDir();
            echo "✅ Test directories created successfully\n";
            
            // Test database connection
            $conn = TestConfig::getTestDatabaseConnection();
            echo "✅ Database connection successful\n";
            
            // Run any additional setup if needed
            TestHelper::createTestDependencies();
            echo "✅ Test dependencies created successfully\n";
            
            echo "\n🎉 Test environment setup completed!\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Test environment setup failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Clean up test environment
     */
    public function cleanupTestEnvironment() {
        echo "🧹 Cleaning up test environment...\n";
        
        try {
            // Clean test database
            TestConfig::cleanTestDatabase();
            echo "✅ Test database cleaned successfully\n";
            
            // Optionally drop test database
            if (TestConfig::CLEANUP_AFTER_TESTS) {
                TestConfig::dropTestDatabase();
                echo "✅ Test database dropped successfully\n";
            }
            
            echo "\n🎉 Test environment cleanup completed!\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Test environment cleanup failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Print usage information
     */
    public function printUsage() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📖 USAGE INSTRUCTIONS\n";
        echo str_repeat("=", 60) . "\n";
        echo "Run all tests:\n";
        echo "  php tests/TestRunner.php\n";
        echo "  php tests/TestRunner.php all\n\n";
        
        echo "Run specific test suite:\n";
        foreach (array_keys($this->testSuites) as $suite) {
            echo "  php tests/TestRunner.php \"$suite\"\n";
        }
        
        echo "\nSetup test environment:\n";
        echo "  php tests/TestRunner.php setup\n\n";
        
        echo "Cleanup test environment:\n";
        echo "  php tests/TestRunner.php cleanup\n\n";
        
        echo "Show this help:\n";
        echo "  php tests/TestRunner.php help\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// Handle command line arguments
if (php_sapi_name() === 'cli') {
    $runner = new TestRunner();
    
    if (isset($argv[1])) {
        $command = $argv[1];
        
        switch (strtolower($command)) {
            case 'all':
                $runner->runAllTests();
                break;
                
            case 'setup':
                $runner->setupTestEnvironment();
                break;
                
            case 'cleanup':
                $runner->cleanupTestEnvironment();
                break;
                
            case 'help':
            case '-h':
            case '--help':
                $runner->printUsage();
                break;
                
            default:
                // Try to run specific test suite
                if (!$runner->runSpecificTest($command)) {
                    $runner->printUsage();
                }
                break;
        }
    } else {
        // No arguments, run all tests
        $runner->runAllTests();
    }
} else {
    // Web interface
    echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Test Runner</title>\n</head>\n<body>\n";
    echo "<h1>Research Management System - Test Suite</h1>\n";
    
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $runner = new TestRunner();
        
        echo "<pre>\n";
        switch ($action) {
            case 'all':
                $runner->runAllTests();
                break;
            case 'setup':
                $runner->setupTestEnvironment();
                break;
            case 'cleanup':
                $runner->cleanupTestEnvironment();
                break;
            default:
                if (!$runner->runSpecificTest($action)) {
                    echo "Invalid test suite: $action\n";
                }
                break;
        }
        echo "</pre>\n";
    } else {
        $runner = new TestRunner();
        echo "<h2>Available Actions:</h2>\n";
        echo "<ul>\n";
        echo "<li><a href='?action=all'>Run All Tests</a></li>\n";
        echo "<li><a href='?action=setup'>Setup Test Environment</a></li>\n";
        echo "<li><a href='?action=cleanup'>Cleanup Test Environment</a></li>\n";
        echo "</ul>\n";
        
        echo "<h2>Individual Test Suites:</h2>\n";
        echo "<ul>\n";
        foreach (array_keys($runner->testSuites) as $suite) {
            $urlSuite = urlencode($suite);
            echo "<li><a href='?action=$urlSuite'>$suite</a></li>\n";
        }
        echo "</ul>\n";
    }
    
    echo "</body>\n</html>\n";
}
?> 