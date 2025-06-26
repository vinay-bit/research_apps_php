<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../includes/auth.php';

requireLogin();

$current_user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Handle delete request
if (isset($_GET['delete']) && hasPermission('admin')) {
    $user->id = $_GET['delete'];
    if ($user->delete()) {
        $success_message = "User deleted successfully.";
    } else {
        $error_message = "Error deleting user.";
    }
}

// Filter by user type
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$page_title = $filter_type ? ucfirst($filter_type) . 's' : 'All Users';

// Get users with optional filtering
if ($filter_type) {
    $query = "SELECT u.*, d.name as department_name, o.name as organization_name_ref
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.id
              LEFT JOIN organizations o ON u.organization_id = o.id
              WHERE u.user_type = :user_type
              ORDER BY u.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_type', $filter_type);
    $stmt->execute();
} else {
    $stmt = $user->read();
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo $page_title; ?> - Research Apps</title>
    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../Apps/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="../Apps/assets/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../Apps/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../Apps/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../Apps/assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Helpers -->
    <script src="../Apps/assets/vendor/js/helpers.js"></script>
    <script src="../Apps/assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php include '../includes/sidebar.php'; ?>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <?php include '../includes/navbar.php'; ?>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">User Management /</span> <?php echo $page_title; ?>
                        </h4>

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Filter and Actions -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Filter Users</h5>
                                <?php if (hasPermission('admin')): ?>
                                <a href="/research_apps/users/create.php" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Add New User
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <a href="/research_apps/users/list.php" class="btn btn-outline-secondary w-100 <?php echo !$filter_type ? 'active' : ''; ?>">
                                            All Users (<?php echo count($users); ?>)
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/research_apps/users/list.php?type=admin" class="btn btn-outline-warning w-100 <?php echo $filter_type == 'admin' ? 'active' : ''; ?>">
                                            Admins
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/research_apps/users/list.php?type=mentor" class="btn btn-outline-primary w-100 <?php echo $filter_type == 'mentor' ? 'active' : ''; ?>">
                                            Mentors
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/research_apps/users/list.php?type=councillor" class="btn btn-outline-info w-100 <?php echo $filter_type == 'councillor' ? 'active' : ''; ?>">
                                            Councillors
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="card">
                            <h5 class="card-header"><?php echo $page_title; ?> List</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Full Name</th>
                                            <th>Username</th>
                                            <th>User Type</th>
                                            <th>Details</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No users found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $user_row): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="../Apps/assets/img/avatars/<?php echo ($user_row['id'] % 7) + 1; ?>.png" alt="Avatar" class="w-px-40 h-auto rounded-circle me-3" />
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($user_row['full_name']); ?></strong>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user_row['username']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $badge_class = '';
                                                        switch($user_row['user_type']) {
                                                            case 'admin': $badge_class = 'bg-label-warning'; break;
                                                            case 'mentor': $badge_class = 'bg-label-primary'; break;
                                                            case 'councillor': $badge_class = 'bg-label-info'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?> me-1"><?php echo ucfirst($user_row['user_type']); ?></span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php if ($user_row['user_type'] == 'admin' || $user_row['user_type'] == 'mentor'): ?>
                                                                Dept: <?php echo htmlspecialchars($user_row['department_name'] ?? 'N/A'); ?>
                                                                <?php if ($user_row['user_type'] == 'mentor'): ?>
                                                                    <br>Spec: <?php echo htmlspecialchars($user_row['specialization'] ?? 'N/A'); ?>
                                                                    <br>Org: <?php echo htmlspecialchars($user_row['organization_name_ref'] ?? 'N/A'); ?>
                                                                <?php endif; ?>
                                                            <?php elseif ($user_row['user_type'] == 'councillor'): ?>
                                                                Org: <?php echo htmlspecialchars($user_row['organization_name'] ?? 'N/A'); ?>
                                                                <br>MOU: <?php echo $user_row['mou_signed'] ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $user_row['status'] == 'active' ? 'bg-label-success' : 'bg-label-secondary'; ?> me-1">
                                                            <?php echo ucfirst($user_row['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($user_row['created_at'])); ?></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                                                                    <a class="dropdown-item" href="/research_apps/users/view.php?id=<?php echo $user_row['id']; ?>">
                                                        <i class="bx bx-show me-1"></i> View
                                                    </a>
                                                    <?php if (hasPermission('admin')): ?>
                                                    <a class="dropdown-item" href="/research_apps/users/edit.php?id=<?php echo $user_row['id']; ?>">
                                                                    <i class="bx bx-edit-alt me-1"></i> Edit
                                                                </a>
                                                                <?php if ($user_row['id'] != $current_user['id']): ?>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="confirmDelete(<?php echo $user_row['id']; ?>)">
                                                                    <i class="bx bx-trash me-1"></i> Delete
                                                                </a>
                                                                <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php include '../includes/footer.php'; ?>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="../Apps/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../Apps/assets/vendor/libs/popper/popper.js"></script>
    <script src="../Apps/assets/vendor/js/bootstrap.js"></script>
    <script src="../Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../Apps/assets/vendor/js/menu.js"></script>
    <script src="../Apps/assets/js/main.js"></script>

    <script>
        function confirmDelete(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                window.location.href = 'list.php?delete=' + userId;
            }
        }
    </script>
</body>
</html> 