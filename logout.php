<?php
require_once 'includes/auth.php';

// Clear session and redirect
clearUserSession();
header("Location: login.php?message=logged_out");
exit();
?>