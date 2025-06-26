<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'user_type' => $_SESSION['user_type']
        ];
    }
    return null;
}

// Set user session
function setUserSession($user_id, $username, $full_name, $user_type) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['user_type'] = $user_type;
}

// Clear user session
function clearUserSession() {
    session_unset();
    session_destroy();
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_type'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}

// Check user permission
function hasPermission($required_type) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_type = $_SESSION['user_type'];
    
    switch ($required_type) {
        case 'admin':
            return $user_type === 'admin';
        case 'mentor':
            return in_array($user_type, ['admin', 'mentor']);
        case 'councillor':
            return in_array($user_type, ['admin', 'councillor']);
        default:
            return false;
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?> 