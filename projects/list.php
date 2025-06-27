<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Project.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /research_apps/login.php");
    exit();
}

$project = new Project();

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && hasPermission('admin')) {
    $project_id = intval($_GET['delete']);
    if ($project->delete($project_id)) {
        $success_message = "Project deleted successfully!";
    } else {
        $error_message = "Error deleting project. Please try again.";
    }
}

// Handle filters
$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status_id'] = $_GET['status'];
}
if (isset($_GET['mentor']) && !empty($_GET['mentor'])) {
    $filters['lead_mentor_id'] = $_GET['mentor'];
}
if (isset($_GET['rbm']) && !empty($_GET['rbm'])) {
    $filters['rbm_id'] = $_GET['rbm'];
}
if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $filters['subject_id'] = $_GET['subject'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get data
$projects = $project->getAll($filters);
$statuses = $project->getStatuses();
$mentors = $project->getMentors();
$rbms = $project->getRBMs();
$subjects = $project->getSubjects();
$statistics = $project->getStatistics();

// Get current user info
$current_user = $_SESSION;
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Project Management - Research Apps</title>
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

                        <!-- Header -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <span class="text-muted fw-light">Project Management /</span> All Projects
                                        </h5>
                                        <div>
                                            <a href="/research_apps/projects/create.php" class="btn btn-primary">
                                                <i class="bx bx-plus me-1"></i> Create New Project
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-primary rounded">
                                                    <i class="bx bx-folder text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Projects</span>
                                        <h3 class="card-title mb-2"><?php echo $statistics['total_projects']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-success rounded">
                                                    <i class="bx bx-check-circle text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">With Prototypes</span>
                                        <h3 class="card-title mb-2"><?php echo $statistics['with_prototypes']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-info rounded">
                                                    <i class="bx bx-user text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Active Mentors</span>
                                        <h3 class="card-title mb-2"><?php echo count($mentors); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-warning rounded">
                                                    <i class="bx bx-book text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Subjects</span>
                                        <h3 class="card-title mb-2"><?php echo count($subjects); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Filter Projects</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" name="search" placeholder="Project name or ID..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?php echo $status['id']; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == $status['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($status['status_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Lead Mentor</label>
                                        <select class="form-select" name="mentor">
                                            <option value="">All Mentors</option>
                                            <?php foreach ($mentors as $mentor): ?>
                                                <option value="<?php echo $mentor['id']; ?>" <?php echo (isset($_GET['mentor']) && $_GET['mentor'] == $mentor['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($mentor['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">RBM</label>
                                        <select class="form-select" name="rbm">
                                            <option value="">All RBMs</option>
                                            <?php foreach ($rbms as $rbm): ?>
                                                <option value="<?php echo $rbm['id']; ?>" <?php echo (isset($_GET['rbm']) && $_GET['rbm'] == $rbm['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($rbm['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Subject</label>
                                        <select class="form-select" name="subject">
                                            <option value="">All Subjects</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo $subject['id']; ?>" <?php echo (isset($_GET['subject']) && $_GET['subject'] == $subject['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Projects Table -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Projects List</h5>
                                <span class="badge bg-primary"><?php echo count($projects); ?> projects found</span>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Project Info</th>
                                            <th>Status</th>
                                            <th>Lead Mentor</th>
                                            <th>Subject</th>
                                            <th>Prototype</th>
                                            <th>Timeline & Deadline</th>
                                            <th>RBM</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if (empty($projects)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="bx bx-folder-open display-4 text-muted"></i>
                                                        <h5 class="mt-2">No projects found</h5>
                                                        <p class="text-muted">Create your first project to get started.</p>
                                                        <a href="/research_apps/projects/create.php" class="btn btn-primary">
                                                            <i class="bx bx-plus me-1"></i> Create New Project
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($projects as $proj): 
                                                // Get assigned students count
                                                $assigned_students = $project->getAssignedStudents($proj['id']);
                                                $student_count = count($assigned_students);
                                                
                                                // Get assigned tags
                                                $assigned_tags = $project->getAssignedTags($proj['id']);
                                                
                                                // Status badge color
                                                $status_class = 'bg-secondary';
                                                if (strpos($proj['status_name'], 'completed') !== false) {
                                                    $status_class = 'bg-success';
                                                } elseif (strpos($proj['status_name'], 'in progress') !== false) {
                                                    $status_class = 'bg-primary';
                                                } elseif (strpos($proj['status_name'], 'yet to start') !== false) {
                                                    $status_class = 'bg-warning';
                                                }
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar flex-shrink-0 me-3">
                                                                <div class="avatar-initial bg-label-primary rounded">
                                                                    <i class="bx bx-folder"></i>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($proj['project_name']); ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($proj['project_id']); ?></small>
                                                                <?php if ($student_count > 0): ?>
                                                                    <br><small class="text-info"><?php echo $student_count; ?> student(s) assigned</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo htmlspecialchars($proj['status_name']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($proj['mentor_name']): ?>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar avatar-xs flex-shrink-0 me-2">
                                                                    <div class="avatar-initial bg-label-info rounded-circle">
                                                                        <?php echo strtoupper(substr($proj['mentor_name'], 0, 2)); ?>
                                                                    </div>
                                                                </div>
                                                                <span><?php echo htmlspecialchars($proj['mentor_name']); ?></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($proj['subject_name']): ?>
                                                            <span class="badge bg-label-secondary"><?php echo htmlspecialchars($proj['subject_name']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not set</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($proj['has_prototype'] == 'Yes'): ?>
                                                            <span class="badge bg-success">Yes</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($proj['start_date']): ?>
                                                            <small>
                                                                <strong>Start:</strong> <?php echo date('M d, Y', strtotime($proj['start_date'])); ?><br>
                                                                <?php if ($proj['end_date']): ?>
                                                                    <?php 
                                                                    $end_date = strtotime($proj['end_date']);
                                                                    $today = time();
                                                                    $days_remaining = ceil(($end_date - $today) / (60 * 60 * 24));
                                                                    ?>
                                                                    <strong>Deadline:</strong> <?php echo date('M d, Y', $end_date); ?>
                                                                    <?php if ($days_remaining < 0): ?>
                                                                        <br><span class="badge bg-danger">Overdue by <?php echo abs($days_remaining); ?> days</span>
                                                                    <?php elseif ($days_remaining <= 7): ?>
                                                                        <br><span class="badge bg-warning">Due in <?php echo $days_remaining; ?> days</span>
                                                                    <?php elseif ($days_remaining <= 30): ?>
                                                                        <br><span class="badge bg-info"><?php echo $days_remaining; ?> days remaining</span>
                                                                    <?php else: ?>
                                                                        <br><span class="badge bg-success"><?php echo $days_remaining; ?> days remaining</span>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not scheduled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($proj['rbm_name']): ?>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar avatar-xs flex-shrink-0 me-2">
                                                                    <div class="avatar-initial bg-label-success rounded-circle">
                                                                        <?php echo strtoupper(substr($proj['rbm_name'], 0, 2)); ?>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <span><?php echo htmlspecialchars($proj['rbm_name']); ?></span>
                                                                    <?php if ($proj['rbm_branch']): ?>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($proj['rbm_branch']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="view.php?id=<?php echo $proj['id']; ?>">
                                                                    <i class="bx bx-show me-1"></i> View Details
                                                                </a>
                                                                <a class="dropdown-item" href="edit.php?id=<?php echo $proj['id']; ?>">
                                                                    <i class="bx bx-edit me-1"></i> Edit Project
                                                                </a>
                                                                <?php if ($proj['drive_link']): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="<?php echo htmlspecialchars($proj['drive_link']); ?>" target="_blank">
                                                                        <i class="bx bx-link-external me-1"></i> Drive Link
                                                                    </a>
                                                                <?php endif; ?>
                                                                <div class="dropdown-divider"></div>
                                                                <?php if (hasPermission('admin')): ?>
                                                                <a class="dropdown-item text-danger" href="?delete=<?php echo $proj['id']; ?>" onclick="return confirm('Are you sure you want to delete the project <?php echo htmlspecialchars($proj['project_name']); ?>? This action cannot be undone.')">
                                                                    <i class="bx bx-trash me-1"></i> Delete
                                                                </a>
                                                                <?php else: ?>
                                                                <span class="dropdown-item text-muted">
                                                                    <i class="bx bx-info-circle me-1"></i> View Only
                                                                </span>
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

    <!-- Main JS -->
    <script src="../Apps/assets/js/main.js"></script>

    <script>
        // Auto-submit form on filter change
        document.querySelectorAll('select[name="status"], select[name="mentor"], select[name="rbm"], select[name="subject"]').forEach(function(select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>

</html>