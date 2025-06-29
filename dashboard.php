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

// Get project statistics
// Total active projects (not completed)
$query = "SELECT COUNT(*) as count FROM projects p 
          JOIN project_statuses ps ON p.status_id = ps.id 
          WHERE ps.status_name NOT LIKE '%completed%'";
$stmt = $db->prepare($query);
$stmt->execute();
$active_projects = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Projects with missed deadlines (end_date < today and not completed)
$query = "SELECT COUNT(*) as count FROM projects p 
          JOIN project_statuses ps ON p.status_id = ps.id 
          WHERE p.end_date < CURDATE() 
          AND ps.status_name NOT LIKE '%completed%'
          AND p.end_date IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->execute();
$missed_deadlines = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Projects approaching deadline (end_date within 7 days and not completed)
$query = "SELECT COUNT(*) as count FROM projects p 
          JOIN project_statuses ps ON p.status_id = ps.id 
          WHERE p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          AND ps.status_name NOT LIKE '%completed%'
          AND p.end_date IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->execute();
$approaching_deadlines = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Ready for Publication statistics (with error handling)
try {
    // Total ready for publication entries
    $query = "SELECT COUNT(*) as count FROM ready_for_publication";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_ready_publications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Under review publications (in_review status)
    $query = "SELECT COUNT(*) as count FROM ready_for_publication WHERE status = 'in_review'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $under_review_publications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Approved publications ready for submission
    $query = "SELECT COUNT(*) as count FROM ready_for_publication WHERE status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $approved_publications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Published count
    $query = "SELECT COUNT(*) as count FROM ready_for_publication WHERE status = 'published'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $published_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    // If table doesn't exist yet, set defaults
    $total_ready_publications = 0;
    $under_review_publications = 0;
    $approved_publications = 0;
    $published_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="Apps/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Dashboard - OMOTEC Research Platform</title>
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
                                                <h5 class="card-title text-primary">Welcome to OMOTEC Research, <?php echo htmlspecialchars($current_user['full_name']); ?>! 🎉</h5>
                                                <p class="mb-4">
                                                    <strong>Learn Tech For Future</strong> - You are logged in as <span class="fw-bold"><?php echo ucfirst($current_user['user_type']); ?></span>. 
                                                    Use the navigation menu to manage users, students, projects, and access our research management platform.
                                                </p>
                                                <a href="/projects/list.php" class="btn btn-sm btn-primary me-2">View Projects</a>
                                                <a href="/publications/ready_for_publication.php" class="btn btn-sm btn-outline-primary me-2">Publications</a>
                                                <a href="/users/list.php" class="btn btn-sm btn-outline-secondary me-2">View Users</a>
                                                <a href="/students/list.php" class="btn btn-sm btn-outline-secondary">View Students</a>
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

                        <!-- Project Statistics Cards -->
                        <div class="row">
                            <div class="col-md-4 col-lg-4 col-xl-4 order-0 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">Active Projects</h5>
                                                    <span class="badge bg-label-success rounded-pill">In Progress</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0"><?php echo $active_projects; ?></h3>
                                                    <small class="text-success fw-semibold">
                                                        <i class="bx bx-trending-up"></i> 
                                                        <a href="/projects/list.php" class="text-success">View All</a>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-label-success">
                                                    <i class="bx bx-briefcase"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-4 col-xl-4 order-1 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">Missed Deadlines</h5>
                                                    <span class="badge bg-danger rounded-pill">Overdue</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0 text-danger"><?php echo $missed_deadlines; ?></h3>
                                                    <small class="text-danger fw-semibold">
                                                        <i class="bx bx-error-circle"></i> 
                                                        <?php if ($missed_deadlines > 0): ?>
                                                            <a href="/projects/list.php?filter=overdue" class="text-danger">View Overdue</a>
                                                        <?php else: ?>
                                                            All on track
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-danger">
                                                    <i class="bx bx-time"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-4 col-xl-4 order-2 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">Approaching Deadline</h5>
                                                    <span class="badge bg-warning rounded-pill">Due Soon</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0 text-warning"><?php echo $approaching_deadlines; ?></h3>
                                                    <small class="text-warning fw-semibold">
                                                        <i class="bx bx-alarm"></i> 
                                                        <?php if ($approaching_deadlines > 0): ?>
                                                            <a href="/projects/list.php?filter=due_soon" class="text-warning">View Due Soon</a>
                                                        <?php else: ?>
                                                            No urgent items
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-warning">
                                                    <i class="bx bx-calendar-exclamation"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Publication Statistics Cards -->
                        <div class="row">
                            <div class="col-md-3 col-lg-3 col-xl-3 order-0 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                                <div class="card-title">
                                                    <h5 class="text-nowrap mb-2">Ready for Publication</h5>
                                                    <span class="badge bg-label-primary rounded-pill">Total Entries</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0"><?php echo $total_ready_publications; ?></h3>
                                                    <small class="text-primary fw-semibold">
                                                        <i class="bx bx-book-open"></i> 
                                                        <a href="/publications/ready_for_publication.php" class="text-primary">View All</a>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="bx bx-file-blank"></i>
                                                </span>
                                            </div>
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
                                                    <h5 class="text-nowrap mb-2">Under Review</h5>
                                                    <span class="badge bg-label-info rounded-pill">In Progress</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0"><?php echo $under_review_publications; ?></h3>
                                                    <small class="text-info fw-semibold">
                                                        <i class="bx bx-search"></i> 
                                                        <?php if ($under_review_publications > 0): ?>
                                                            <a href="/publications/ready_for_publication.php?status=in_review" class="text-info">View Reviews</a>
                                                        <?php else: ?>
                                                            None in review
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-info">
                                                    <i class="bx bx-search-alt-2"></i>
                                                </span>
                                            </div>
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
                                                    <h5 class="text-nowrap mb-2">Approved</h5>
                                                    <span class="badge bg-label-success rounded-pill">Ready to Submit</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0 text-success"><?php echo $approved_publications; ?></h3>
                                                    <small class="text-success fw-semibold">
                                                        <i class="bx bx-check-circle"></i> 
                                                        <?php if ($approved_publications > 0): ?>
                                                            <a href="/publications/ready_for_publication.php?status=approved" class="text-success">View Approved</a>
                                                        <?php else: ?>
                                                            None approved
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-success">
                                                    <i class="bx bx-check-circle"></i>
                                                </span>
                                            </div>
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
                                                    <h5 class="text-nowrap mb-2">Published</h5>
                                                    <span class="badge bg-label-warning rounded-pill">Completed</span>
                                                </div>
                                                <div class="mt-sm-auto">
                                                    <h3 class="mb-0 text-warning"><?php echo $published_count; ?></h3>
                                                    <small class="text-warning fw-semibold">
                                                        <i class="bx bx-trophy"></i> 
                                                        <?php if ($published_count > 0): ?>
                                                            <a href="/publications/ready_for_publication.php?status=published" class="text-warning">View Published</a>
                                                        <?php else: ?>
                                                            None published yet
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <span class="avatar-initial rounded bg-warning">
                                                    <i class="bx bx-trophy"></i>
                                                </span>
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
                                                <span class="avatar-initial rounded bg-label-success"><i class="bx bx-book-open"></i></span>
                                            </div>
                                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="me-2">
                                                    <h6 class="mb-0">Ready for Publication System</h6>
                                                    <small class="text-muted">Publication tracking with draft links and plagiarism reports implemented</small>
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