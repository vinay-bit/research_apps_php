<?php
require_once '../includes/auth.php';
require_once '../classes/Publication.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$publication = new Publication();

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && hasPermission('admin')) {
    $publication->id = intval($_GET['delete']);
    if ($publication->delete()) {
        $success_message = "Publication deleted successfully!";
    } else {
        $error_message = "Error deleting publication. Please try again.";
    }
}

// Handle filters
$filters = [];
if (isset($_GET['venue_type']) && !empty($_GET['venue_type'])) {
    $filters['venue_type'] = $_GET['venue_type'];
}
if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    $filters['project_id'] = $_GET['project_id'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get data
$publications = $publication->readAll($filters);
$projects = $publication->getProjects();
$statistics = $publication->getStatistics();

// Get current user info
$current_user = $_SESSION;
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Publication Management - Research Apps</title>
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
                                            <span class="text-muted fw-light">Publication Management /</span> All Publications
                                        </h5>
                                        <div>
                                            <a href="/publications/create.php" class="btn btn-primary">
                                                <i class="bx bx-plus me-1"></i> Create New Publication
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
                                                    <i class="bx bx-book text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Publications</span>
                                        <h3 class="card-title mb-2"><?php echo $statistics['total']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-success rounded">
                                                    <i class="bx bx-trophy text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Conferences</span>
                                        <h3 class="card-title mb-2"><?php echo isset($statistics['by_venue']['Conference']) ? $statistics['by_venue']['Conference'] : 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-info rounded">
                                                    <i class="bx bx-file text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Journals</span>
                                        <h3 class="card-title mb-2"><?php echo isset($statistics['by_venue']['Journal']) ? $statistics['by_venue']['Journal'] : 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-warning rounded">
                                                    <i class="bx bx-time text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Recent (30 days)</span>
                                        <h3 class="card-title mb-2"><?php echo $statistics['recent']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search and Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Search & Filter Publications</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                               placeholder="Search by title, publication ID, project...">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="venue_type" class="form-label">Venue Type</label>
                                        <select class="form-select" id="venue_type" name="venue_type">
                                            <option value="">All Types</option>
                                            <option value="Conference" <?php echo (isset($_GET['venue_type']) && $_GET['venue_type'] == 'Conference') ? 'selected' : ''; ?>>Conference</option>
                                            <option value="Journal" <?php echo (isset($_GET['venue_type']) && $_GET['venue_type'] == 'Journal') ? 'selected' : ''; ?>>Journal</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="project_id" class="form-label">Project</label>
                                        <select class="form-select" id="project_id" name="project_id">
                                            <option value="">All Projects</option>
                                            <?php while ($project = $projects->fetch(PDO::FETCH_ASSOC)): ?>
                                                <option value="<?php echo $project['id']; ?>" <?php echo (isset($_GET['project_id']) && $_GET['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">Search</button>
                                        <a href="/publications/list.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Publications Table -->
                        <div class="card">
                            <h5 class="card-header">Publications List</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Publication</th>
                                            <th>Project</th>
                                            <th>Venue Type</th>
                                            <th>Students</th>
                                            <th>Lead Mentor</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php 
                                        $publications_data = $publications->fetchAll(PDO::FETCH_ASSOC);
                                        if (empty($publications_data)): 
                                        ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="bx bx-book-open display-4 text-muted"></i>
                                                        <h5 class="mt-2">No publications found</h5>
                                                        <p class="text-muted">Create your first publication to get started.</p>
                                                        <a href="/publications/create.php" class="btn btn-primary">
                                                            <i class="bx bx-plus me-1"></i> Create New Publication
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($publications_data as $pub): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar flex-shrink-0 me-3">
                                                                <div class="avatar-initial <?php echo ($pub['venue_type'] == 'Conference') ? 'bg-label-success' : 'bg-label-info'; ?> rounded">
                                                                    <i class="bx <?php echo ($pub['venue_type'] == 'Conference') ? 'bx-trophy' : 'bx-file'; ?>"></i>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($pub['paper_title']); ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($pub['publication_id']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($pub['project_name']): ?>
                                                            <div>
                                                                <span><?php echo htmlspecialchars($pub['project_name']); ?></span>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($pub['project_code']); ?></small>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No project assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo ($pub['venue_type'] == 'Conference') ? 'bg-success' : 'bg-info'; ?>">
                                                            <?php echo htmlspecialchars($pub['venue_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($pub['students']): ?>
                                                            <span class="badge bg-label-primary"><?php echo $pub['student_count']; ?> students</span>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($pub['students']); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">No students</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($pub['lead_mentor_name'] ?? 'Not assigned'); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y', strtotime($pub['created_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="view.php?id=<?php echo $pub['id']; ?>">
                                                                    <i class="bx bx-show me-1"></i> View Details
                                                                </a>
                                                                <a class="dropdown-item" href="edit.php?id=<?php echo $pub['id']; ?>">
                                                                    <i class="bx bx-edit me-1"></i> Edit Publication
                                                                </a>
                                                                <div class="dropdown-divider"></div>
                                                                <?php if (hasPermission('admin')): ?>
                                                                <a class="dropdown-item text-danger" href="?delete=<?php echo $pub['id']; ?>" onclick="return confirm('Are you sure you want to delete this publication? This action cannot be undone.')">
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
        document.querySelectorAll('select[name="venue_type"], select[name="project_id"]').forEach(function(select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>

</html> 