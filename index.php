<?php
// Include auth (which now has robust session handling)
require_once 'includes/auth.php';

// Check if user is logged in (session is started automatically by auth.php)
if (isLoggedIn()) {
    // User is logged in, redirect to dashboard
    header("Location: dashboard.php");
} else {
    // User is not logged in, redirect to login
    header("Location: login.php");
}
exit();
?> 