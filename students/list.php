<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Student.php';
require_once '../classes/User.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /research_apps/login.php');
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize classes
$student = new Student($db);
$user = new User($db);

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (hasPermission('admin')) {
        $student->id = intval($_GET['delete']);
        if ($student->delete()) {
            $success_message = "Student deleted successfully!";
        } else {
            $error_message = "Error deleting student. Please try again.";
        }
    } else {
        $error_message = "Access denied. You don't have permission to delete students.";
    }
}

// Handle search
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_rbm = isset($_GET['rbm']) ? intval($_GET['rbm']) : 0;
$filter_counselor = isset($_GET['counselor']) ? intval($_GET['counselor']) : 0;

// Get students based on search and filters
if (!empty($search_term)) {
    $stmt = $student->search($search_term);
} elseif ($filter_rbm > 0) {
    $stmt = $student->getByRBM($filter_rbm);
} elseif ($filter_counselor > 0) {
    $stmt = $student->getByCounselor($filter_counselor);
} else {
    $stmt = $student->read();
}

$students = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $students[] = $row;
}

// Get RBM users for filter dropdown
$rbm_stmt = $user->getRBMUsers();
$rbm_users = [];
while ($row = $rbm_stmt->fetch(PDO::FETCH_ASSOC)) {
    $rbm_users[] = $row;
}

// Get counselors for filter dropdown
$counselor_stmt = $student->getCounselors();
$counselors = [];
while ($row = $counselor_stmt->fetch(PDO::FETCH_ASSOC)) {
    $counselors[] = $row;
}

$current_user = getCurrentUser();
$page_title = "Student Management";
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
                            <span class="text-muted fw-light">Student Management /</span> All Students
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

                        <!-- Search and Filter -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Search & Filter</h5>
                                <a href="/research_apps/students/create.php" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Add New Student
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               value="<?php echo htmlspecialchars($search_term); ?>" 
                                               placeholder="Search by name, student ID, email...">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="rbm" class="form-label">Filter by RBM</label>
                                        <select class="form-select" id="rbm" name="rbm">
                                            <option value="0">All RBMs</option>
                                            <?php foreach ($rbm_users as $rbm): ?>
                                                <option value="<?php echo $rbm['id']; ?>" <?php echo ($filter_rbm == $rbm['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($rbm['full_name']); ?> (<?php echo htmlspecialchars($rbm['branch']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="counselor" class="form-label">Filter by Counselor</label>
                                        <select class="form-select" id="counselor" name="counselor">
                                            <option value="0">All Counselors</option>
                                            <?php foreach ($counselors as $counselor): ?>
                                                <option value="<?php echo $counselor['id']; ?>" <?php echo ($filter_counselor == $counselor['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($counselor['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">Search</button>
                                        <a href="/research_apps/students/list.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Students Table -->
                        <div class="card">
                            <h5 class="card-header">Students List (<?php echo count($students); ?> total)</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Full Name</th>
                                            <th>Contact</th>
                                            <th>Grade</th>
                                            <th>Board</th>
                                            <th>Counselor</th>
                                            <th>RBM</th>
                                            <th>Year</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No students found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $student_row): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($student_row['student_id']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-wrapper">
                                                                <div class="avatar avatar-sm me-3">
                                                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                                                        <?php 
                                                                        $names = explode(' ', $student_row['full_name']);
                                                                        $initials = '';
                                                                        $initials .= isset($names[0]) ? strtoupper(substr($names[0], 0, 1)) : '';
                                                                        $initials .= isset($names[1]) ? strtoupper(substr($names[1], 0, 1)) : '';
                                                                        echo $initials;
                                                                        ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($student_row['full_name']); ?></strong>
                                                                <?php if (!empty($student_row['affiliation'])): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($student_row['affiliation']); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <?php if (!empty($student_row['email_address'])): ?>
                                                                <a href="mailto:<?php echo htmlspecialchars($student_row['email_address']); ?>" class="text-decoration-none">
                                                                    <i class="bx bx-envelope me-1"></i><?php echo htmlspecialchars($student_row['email_address']); ?>
                                                                </a>
                                                                <br>
                                                            <?php endif; ?>
                                                            <?php if (!empty($student_row['contact_no'])): ?>
                                                                <small class="text-muted">
                                                                    <i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($student_row['contact_no']); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                            <?php if (empty($student_row['email_address']) && empty($student_row['contact_no'])): ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($student_row['grade'])): ?>
                                                            <span class="badge bg-label-info"><?php echo htmlspecialchars(ucfirst($student_row['grade'])); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($student_row['board_name'])): ?>
                                                            <span class="badge bg-label-warning"><?php echo htmlspecialchars($student_row['board_name']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($student_row['counselor_name'])): ?>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($student_row['counselor_name']); ?></strong>
                                                                <br><small class="text-muted">Counselor</small>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not Assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($student_row['rbm_name'])): ?>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($student_row['rbm_name']); ?></strong>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($student_row['rbm_branch']); ?></small>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not Assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($student_row['application_year'])): ?>
                                                            <span class="badge bg-label-secondary"><?php echo htmlspecialchars($student_row['application_year']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="/research_apps/students/view.php?id=<?php echo $student_row['id']; ?>">
                                                                    <i class="bx bx-show me-1"></i> View
                                                                </a>
                                                                <a class="dropdown-item" href="/research_apps/students/edit.php?id=<?php echo $student_row['id']; ?>">
                                                                    <i class="bx bx-edit-alt me-1"></i> Edit
                                                                </a>
                                                                <?php if (hasPermission('admin')): ?>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="confirmDelete(<?php echo $student_row['id']; ?>)">
                                                                    <i class="bx bx-trash me-1"></i> Delete
                                                                </a>
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
        function confirmDelete(studentId) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                window.location.href = 'list.php?delete=' + studentId;
            }
        }
    </script>
</body>
</html> 