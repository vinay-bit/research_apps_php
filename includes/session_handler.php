<?php
// Robust session handler for production servers

function startSecureSession() {
    // Check if session is already started
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    
    try {
        // Set session configuration for better compatibility
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        
        // Try to set a writable session save path
        $current_save_path = session_save_path();
        if (empty($current_save_path) || !is_writable($current_save_path)) {
            $temp_dir = sys_get_temp_dir();
            if (is_writable($temp_dir)) {
                session_save_path($temp_dir);
            }
        }
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => 3600, // 1 hour
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        // Start the session
        if (!session_start()) {
            throw new Exception("Failed to start session");
        }
        
        return true;
        
    } catch (Exception $e) {
        // Log error or handle it gracefully
        error_log("Session start error: " . $e->getMessage());
        return false;
    }
}

// Function to safely destroy session
function destroySecureSession() {
    try {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear all session variables
            $_SESSION = array();
            
            // Delete session cookie if it exists
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destroy the session
            session_destroy();
        }
        return true;
    } catch (Exception $e) {
        error_log("Session destroy error: " . $e->getMessage());
        return false;
    }
}
?> 