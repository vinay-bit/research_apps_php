<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_POST) {
    if (isset($_POST['login'])) {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error_message = "Please fill in all fields.";
        } else {
            if ($user->login($username, $password)) {
                setUserSession($user->id, $user->username, $user->full_name, $user->user_type);
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="Apps/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login - OMOTEC Research Platform</title>
    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="Apps/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="Apps/assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="Apps/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="Apps/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="Apps/assets/css/demo.css" />
    
    <!-- OMOTEC Custom Theme -->
    <link rel="stylesheet" href="Apps/assets/css/omotec-theme.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="Apps/assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="Apps/assets/vendor/js/helpers.js"></script>
    <script src="Apps/assets/js/config.js"></script>
</head>

<body>
    <!-- Content -->
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Login -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-4">
                            <a href="login.php" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <img src="logo/omotec_logo.webp" alt="OMOTEC" height="40" style="max-width: 150px;">
                                </span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        
                        <h4 class="mb-2 text-center">Welcome to OMOTEC! 👋</h4>
                        <p class="mb-4 text-center"><strong>Learn Tech For Future</strong><br>Please sign-in to your account and access our research management platform</p>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <form id="formAuthentication" class="mb-3" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" autofocus required />
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Password</label>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" required />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember-me" />
                                    <label class="form-check-label" for="remember-me"> Remember Me </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit" name="login">Sign in</button>
                            </div>
                        </form>

                        <p class="text-center">
                            <small>Default Admin Login: admin / admin123</small>
                        </p>
                    </div>
                </div>
                <!-- /Login -->
            </div>
        </div>
    </div>
    <!-- / Content -->

    <!-- Core JS -->
    <script src="Apps/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="Apps/assets/vendor/libs/popper/popper.js"></script>
    <script src="Apps/assets/vendor/js/bootstrap.js"></script>
    <script src="Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="Apps/assets/vendor/js/menu.js"></script>
    <script src="Apps/assets/js/main.js"></script>

    <script>
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.input-group-text');
            const password = document.querySelector('#password');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('bx-hide');
                icon.classList.toggle('bx-show');
            });
        });
    </script>
</body>
</html>