<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'includes/auth.php';

requireLogin();

$current_user = getCurrentUser();

// Get statistics
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Count users by type
$query = "SELECT user_type, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY user_type";
$stmt = $db->prepare($query);
$stmt->execute();
$user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = ['admin' => 0, 'mentor' => 0, 'councillor' => 0, 'rbm' => 0];
foreach ($user_stats as $stat) {
    $stats[$stat['user_type']] = $stat['count'];
}

// Get student count
$query = "SELECT COUNT(*) as count FROM students";
$stmt = $db->prepare($query);
$stmt->execute();
$student_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="Apps/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Dashboard - Research Apps</title>
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

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="Apps/assets/vendor/libs/apex-charts/apex-charts.css" />

    <!-- Helpers -->
    <script src="Apps/assets/vendor/js/helpers.js"></script>
    <script src="Apps/assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php include 'includes/sidebar.php'; ?>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <?php include 'includes/navbar.php'; ?>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row">
                            <div class="col-lg-8 mb-4 order-0">
                                <div class="card">
                                    <div class="d-flex align-items-end row">
                                        <div class="col-sm-7">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary">Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>! 🎉</h5>
                                                <p class="mb-4">
                                                    You are logged in as <span class="fw-bold"><?php echo ucfirst($current_user['user_type']); ?></span>. 
                                                    Use the navigation menu to manage users, students, and access research applications.
                                                </p>
                                                <a href="/research_apps/users/list.php" class="btn btn-sm btn-outline-primary me-2">View Users</a>
                                                <a href="/research_apps/students/list.php" class="btn btn-sm btn-primary">View Students</a>
                                            </div>
                                        </div>
                                        <div class="col-sm-5 text-center text-sm-left">
                                            <div class="card-body pb-0 px-0 px-md-4">
                                                <img src="Apps/assets/img/illustrations/man-with-laptop-light.png" height="140" alt="View Badge User" data-app-dark-img="Apps/assets/img/illustrations/man-with-laptop-dark.png" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 order-1">
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="Apps/assets/img/icons/unicons/chart-success.png" alt="chart success" class="rounded" />
                                                    </div>
                                                </div>
                                                <span class="fw-semibold d-block mb-1">Total Users</span>
                                                <h3 class="card-title mb-2"><?php echo array_sum($stats); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="Apps/assets/img/icons/unicons/wallet-info.png" alt="Credit Card" class="rounded" />
                                                    </div>
                                                </div>
                                                <span>Total Students</span>
                                                <h3 class="card-title text-nowrap mb-1"><?php echo $student_count; ?></h3>
                                                <small class="text-success fw-semibold"><i class="bx bx-user-circle"></i> Active</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Statistics Cards -->
                        <div class="row">
                            <div class="col-md-3 col-lg-3 col-xl-3 order-0 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">Admins</h5>
                                                    <span class="badge bg-label-warning rounded-pill">System Users</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0"><?php echo $stats['admin']; ?></h3>
                                                </div>
                                            </div>
                                            <div id="adminChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-lg-3 col-xl-3 order-1 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">Mentors</h5>
                                                    <span class="badge bg-label-primary rounded-pill">Guidance</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0"><?php echo $stats['mentor']; ?></h3>
                                                </div>
                                            </div>
                                            <div id="mentorChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-lg-3 col-xl-3 order-2 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">Councillors</h5>
                                                    <span class="badge bg-label-info rounded-pill">Support</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0"><?php echo $stats['councillor']; ?></h3>
                                                </div>
                                            </div>
                                            <div id="councillorChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-lg-3 col-xl-3 order-3 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">RBMs</h5>
                                                    <span class="badge bg-label-success rounded-pill">Branch Mgrs</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0"><?php echo $stats['rbm']; ?></h3>
                                                </div>
                                            </div>
                                            <div id="rbmChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activities -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <h5 class="card-header">Recent Activities</h5>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar me-3">
                                                <span class="avatar-initial rounded bg-label-success"><i class="bx bx-user"></i></span>
                                            </div>
                                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="me-2">
                                                    <h6 class="mb-0">User Management System</h6>
                                                    <small class="text-muted">User module has been implemented successfully</small>
                                                </div>
                                                <div class="user-progress d-flex align-items-center gap-1">
                                                    <span class="badge bg-label-success">Completed</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar me-3">
                                                <span class="avatar-initial rounded bg-label-success"><i class="bx bx-user-circle"></i></span>
                                            </div>
                                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="me-2">
                                                    <h6 class="mb-0">Student Management System</h6>
                                                    <small class="text-muted">Full student management with RBM assignment implemented</small>
                                                </div>
                                                <div class="user-progress d-flex align-items-center gap-1">
                                                    <span class="badge bg-label-success">Completed</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar me-3">
                                                <span class="avatar-initial rounded bg-label-success"><i class="bx bx-briefcase"></i></span>
                                            </div>
                                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="me-2">
                                                    <h6 class="mb-0">RBM User Type</h6>
                                                    <small class="text-muted">Research Branch Manager user type added successfully</small>
                                                </div>
                                                <div class="user-progress d-flex align-items-center gap-1">
                                                    <span class="badge bg-label-success">Completed</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php include 'includes/footer.php'; ?>
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
    <script src="Apps/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="Apps/assets/vendor/libs/popper/popper.js"></script>
    <script src="Apps/assets/vendor/js/bootstrap.js"></script>
    <script src="Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="Apps/assets/vendor/js/menu.js"></script>
    <script src="Apps/assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="Apps/assets/js/main.js"></script>
    <script src="Apps/assets/js/dashboards-analytics.js"></script>
</body>
</html> 