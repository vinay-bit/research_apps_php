<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Project.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$project = new Project();

// Handle move back to active request (admin only)
if (isset($_GET['move_back']) && is_numeric($_GET['move_back']) && hasPermission('admin')) {
    $project_id = intval($_GET['move_back']);
    if ($project->moveBackToActive($project_id)) {
        $success_message = "Project moved back to active list successfully!";
    } else {
        $error_message = "Error moving project back to active list. Please try again.";
    }
}

// Handle filters
$filters = [];
if (isset($_GET['mentor']) && !empty($_GET['mentor'])) {
    $filters['lead_mentor_id'] = $_GET['mentor'];
}
if (isset($_GET['rbm']) && !empty($_GET['rbm'])) {
    $filters['rbm_id'] = $_GET['rbm'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['sort_by']) && !empty($_GET['sort_by'])) {
    $filters['sort_by'] = $_GET['sort_by'];
}

// Get data
$completed_projects = $project->getCompletedProjects($filters);
$mentors = $project->getMentors();
$rbms = $project->getRBMs();

// Get statistics
$total_completed = count($completed_projects);
$with_prototypes = count(array_filter($completed_projects, function($p) { return $p['has_prototype'] == 'Yes'; }));

// Get current user info
$current_user = $_SESSION;
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Completed Projects - Research Apps</title>
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
                                            <span class="text-muted fw-light">Project Management /</span> Completed Projects
                                        </h5>
                                        <div>
                                            <a href="/projects/list.php" class="btn btn-outline-primary">
                                                <i class="bx bx-arrow-back me-1"></i> Back to All Projects
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-6 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-success rounded">
                                                    <i class="bx bx-check-circle text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Completed</span>
                                        <h3 class="card-title mb-2"><?php echo $total_completed; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-info rounded">
                                                    <i class="bx bx-cube text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">With Prototypes</span>
                                        <h3 class="card-title mb-2"><?php echo $with_prototypes; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Filters & Search</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Search</label>
                                            <input type="text" class="form-control" name="search" placeholder="Project name, ID, or description..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-3">
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
                                        <div class="col-md-3">
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
                                        <div class="col-md-3">
                                            <label class="form-label">Sort By</label>
                                            <select class="form-select" name="sort_by">
                                                <option value="completion_date" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'completion_date') ? 'selected' : ''; ?>>Completion Date</option>
                                                <option value="project_name" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'project_name') ? 'selected' : ''; ?>>Project Name</option>
                                                <option value="project_id" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'project_id') ? 'selected' : ''; ?>>Project ID</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Filter
                                        </button>
                                        <a href="completed.php" class="btn btn-outline-secondary">
                                            <i class="bx bx-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Projects Table -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Completed Projects</h5>
                                <small class="text-muted">Total: <?php echo $total_completed; ?> projects</small>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <?php if (empty($completed_projects)): ?>
                                    <div class="card-body text-center py-5">
                                        <div class="mb-3">
                                            <i class="bx bx-search-alt-2 bx-lg text-muted"></i>
                                        </div>
                                        <h6 class="mb-1">No completed projects found</h6>
                                        <p class="text-muted mb-0">Try adjusting your search criteria or check back later.</p>
                                    </div>
                                <?php else: ?>
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Project Details</th>
                                                <th>Mentor & RBM</th>
                                                <th>Completion Info</th>
                                                <th>Prototype</th>
                                                <?php if (hasPermission('admin')): ?>
                                                <th>Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0">
                                            <?php foreach ($completed_projects as $proj): 
                                                // Get assigned students count
                                                $assigned_students = $project->getAssignedStudents($proj['id']);
                                                $student_count = count($assigned_students);
                                                
                                                // Get assigned tags
                                                $assigned_tags = $project->getAssignedTags($proj['id']);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-start">
                                                            <div class="avatar flex-shrink-0 me-3">
                                                                <div class="avatar-initial bg-success rounded">
                                                                    <i class="bx bx-check-circle text-white"></i>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0">
                                                                    <a href="/projects/view.php?id=<?php echo $proj['id']; ?>" class="text-body">
                                                                        <?php echo htmlspecialchars($proj['project_name']); ?>
                                                                    </a>
                                                                </h6>
                                                                <small class="text-muted">ID: <?php echo htmlspecialchars($proj['project_id']); ?></small>
                                                                <?php if (!empty($proj['subject_name'])): ?>
                                                                    <br><small class="text-info"><?php echo htmlspecialchars($proj['subject_name']); ?></small>
                                                                <?php endif; ?>
                                                                <?php if ($student_count > 0): ?>
                                                                    <br><small class="text-primary"><?php echo $student_count; ?> student(s) assigned</small>
                                                                <?php endif; ?>
                                                                <?php if (!empty($assigned_tags)): ?>
                                                                    <div class="mt-1">
                                                                        <?php foreach ($assigned_tags as $tag): ?>
                                                                            <span class="badge bg-<?php echo $tag['color']; ?> me-1"><?php echo htmlspecialchars($tag['tag_name']); ?></span>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($proj['mentor_name'])): ?>
                                                            <div class="mb-1">
                                                                <strong>Mentor:</strong><br>
                                                                <small><?php echo htmlspecialchars($proj['mentor_name']); ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($proj['rbm_name'])): ?>
                                                            <div>
                                                                <strong>RBM:</strong><br>
                                                                <small><?php echo htmlspecialchars($proj['rbm_name']); ?></small>
                                                                <?php if (!empty($proj['rbm_branch'])): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($proj['rbm_branch']); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Completed</span>
                                                        <?php if (!empty($proj['completion_date'])): ?>
                                                            <br><small class="text-muted">Completed: <?php echo date('M j, Y', strtotime($proj['completion_date'])); ?></small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($proj['start_date'])): ?>
                                                            <br><small class="text-muted">Started: <?php echo date('M j, Y', strtotime($proj['start_date'])); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($proj['has_prototype'] == 'Yes') ? 'success' : 'secondary'; ?>">
                                                            <?php echo $proj['has_prototype']; ?>
                                                        </span>
                                                    </td>
                                                    <?php if (hasPermission('admin')): ?>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="/projects/view.php?id=<?php echo $proj['id']; ?>">
                                                                    <i class="bx bx-show me-1"></i> View Details
                                                                </a>
                                                                <a class="dropdown-item" href="/projects/edit.php?id=<?php echo $proj['id']; ?>">
                                                                    <i class="bx bx-edit-alt me-1"></i> Edit
                                                                </a>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item text-warning" href="javascript:void(0);" onclick="confirmMoveBack(<?php echo $proj['id']; ?>)">
                                                                    <i class="bx bx-undo me-1"></i> Move Back to Active
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>
        <!-- / Layout container -->
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
        function confirmMoveBack(projectId) {
            if (confirm('Are you sure you want to move this project back to the active list? This will change its status to "Project Execution - in progress".')) {
                window.location.href = 'completed.php?move_back=' + projectId;
            }
        }
    </script>
</body>
</html> 