<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/ReadyForPublication.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$readyForPublication = new ReadyForPublication();
$message = '';
$error = '';

// Handle adding project to ready for publication list (auto from status)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_project'])) {
    try {
        $project_id = intval($_POST['project_id']);
        $publication_id = $readyForPublication->createFromProject($project_id);
        $message = "Project successfully added to ready for publication list!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle manual add publication
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_manual'])) {
    try {
        $data = [
            'project_id' => intval($_POST['manual_project_id']),
            'paper_title' => trim($_POST['paper_title']),
            'mentor_affiliation' => trim($_POST['mentor_affiliation']),
            'first_draft_link' => trim($_POST['first_draft_link']),
            'plagiarism_report_link' => trim($_POST['plagiarism_report_link']),
            'status' => $_POST['publication_status'],
            'notes' => trim($_POST['notes']),
            'students' => []
        ];
        
        $publication_id = $readyForPublication->createManual($data);
        $message = "Publication entry created successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && hasPermission('admin')) {
    $publication_id = intval($_GET['delete']);
    if ($readyForPublication->delete($publication_id)) {
        $message = "Entry removed from ready for publication list!";
    } else {
        $error = "Error removing entry. Please try again.";
    }
}

// Handle filters
$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get data
$publications = $readyForPublication->getAll($filters);
$ready_projects = $readyForPublication->getReadyProjects();
$all_projects = $readyForPublication->getAllProjects();
$statistics = $readyForPublication->getStatistics();

// Get current user info
$current_user = $_SESSION;
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Ready for Publication - Research Apps</title>
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
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Header -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <span class="text-muted fw-light">Research & Publications /</span> Ready for Publication
                                        </h5>
                                                                <div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addManualModal">
                                <i class="bx bx-plus me-1"></i> Add Publication
                            </button>
                            <?php if (!empty($ready_projects)): ?>
                                <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                                    <i class="bx bx-import me-1"></i> From Ready Projects
                                </button>
                            <?php endif; ?>
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
                                                    <i class="bx bx-file text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Ready</span>
                                        <h3 class="card-title mb-2"><?php echo $statistics['total']; ?></h3>
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
                                        <span class="fw-semibold d-block mb-1">Pending Review</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $pending = array_filter($statistics['by_status'], function($item) { 
                                                return $item['status'] == 'pending'; 
                                            });
                                            echo !empty($pending) ? reset($pending)['count'] : 0;
                                            ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-info rounded">
                                                    <i class="bx bx-search text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">In Review</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $in_review = array_filter($statistics['by_status'], function($item) { 
                                                return $item['status'] == 'in_review'; 
                                            });
                                            echo !empty($in_review) ? reset($in_review)['count'] : 0;
                                            ?>
                                        </h3>
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
                                        <span class="fw-semibold d-block mb-1">Approved</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $approved = array_filter($statistics['by_status'], function($item) { 
                                                return $item['status'] == 'approved'; 
                                            });
                                            echo !empty($approved) ? reset($approved)['count'] : 0;
                                            ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Filter Publications</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" name="search" placeholder="Paper title or project name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in_review" <?php echo (isset($_GET['status']) && $_GET['status'] == 'in_review') ? 'selected' : ''; ?>>In Review</option>
                                            <option value="approved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                            <option value="published" <?php echo (isset($_GET['status']) && $_GET['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Ready for Publication List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Ready for Publication List</h5>
                                <span class="badge bg-primary"><?php echo count($publications); ?> entries found</span>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Paper Title & Links</th>
                                            <th>Project</th>
                                            <th>Student Details</th>
                                            <th>Mentor Details</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if (empty($publications)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="bx bx-file-blank display-4 text-muted"></i>
                                                        <h5 class="mt-2">No publications ready</h5>
                                                        <?php if (empty($ready_projects)): ?>
                                                            <p class="text-muted">No projects are marked as "ready for publication".</p>
                                                        <?php else: ?>
                                                            <p class="text-muted">Create a new publication or add projects marked as ready for publication.</p>
                                                            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addManualModal">
                                                                <i class="bx bx-plus me-1"></i> Add Publication
                                                            </button>
                                                            <?php if (!empty($ready_projects)): ?>
                                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                                                                    <i class="bx bx-import me-1"></i> From Ready Projects
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($publications as $pub): 
                                                // Get students for this publication
                                                $students = $readyForPublication->getStudentsByPublicationId($pub['id']);
                                                
                                                // Status badge class
                                                $status_class = 'bg-secondary';
                                                if ($pub['status'] == 'pending') $status_class = 'bg-warning';
                                                elseif ($pub['status'] == 'in_review') $status_class = 'bg-info';
                                                elseif ($pub['status'] == 'approved') $status_class = 'bg-success';
                                                elseif ($pub['status'] == 'published') $status_class = 'bg-primary';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($pub['paper_title']); ?></strong>
                                                        <br>
                                                        <?php if ($pub['first_draft_link']): ?>
                                                            <a href="<?php echo htmlspecialchars($pub['first_draft_link']); ?>" target="_blank" class="btn btn-xs btn-outline-primary me-1">
                                                                <i class="bx bx-file-blank me-1"></i>First Draft
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($pub['plagiarism_report_link']): ?>
                                                            <a href="<?php echo htmlspecialchars($pub['plagiarism_report_link']); ?>" target="_blank" class="btn btn-xs btn-outline-warning">
                                                                <i class="bx bx-check-shield me-1"></i>Plagiarism Report
                                                            </a>
                                                        <?php endif; ?>
                                                        <br>
                                                        <small class="text-muted">Added on <?php echo date('M d, Y', strtotime($pub['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($pub['project_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($pub['project_code']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($students)): ?>
                                                            <?php foreach ($students as $index => $student): ?>
                                                                <div class="mb-2">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="avatar avatar-xs flex-shrink-0 me-2">
                                                                            <div class="avatar-initial bg-label-info rounded-circle">
                                                                                <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                                            <br>
                                                                            <small class="text-muted">
                                                                                ID: <?php echo htmlspecialchars($student['student_id']); ?> | 
                                                                                Grade: <?php echo htmlspecialchars($student['grade']); ?>
                                                                            </small>
                                                                            <?php if ($student['student_affiliation']): ?>
                                                                                <br><small class="text-info">📍 <?php echo htmlspecialchars($student['student_affiliation']); ?></small>
                                                                            <?php endif; ?>
                                                                            <?php if ($student['student_address']): ?>
                                                                                <br><small class="text-muted">🏠 <?php echo htmlspecialchars($student['student_address']); ?></small>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php if ($index < count($students) - 1): ?>
                                                                    <hr class="my-2">
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">No students assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($pub['mentor_name']): ?>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar avatar-xs flex-shrink-0 me-2">
                                                                    <div class="avatar-initial bg-label-success rounded-circle">
                                                                        <?php echo strtoupper(substr($pub['mentor_name'], 0, 2)); ?>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($pub['mentor_name']); ?></strong>
                                                                    <?php if ($pub['mentor_affiliation']): ?>
                                                                        <br><small class="text-muted">🎓 <?php echo htmlspecialchars($pub['mentor_affiliation']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No mentor assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucwords(str_replace('_', ' ', $pub['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="edit_ready_publication.php?id=<?php echo $pub['id']; ?>">
                                                                    <i class="bx bx-edit me-1"></i> Edit Details
                                                                </a>
                                                                <div class="dropdown-divider"></div>
                                                                <?php if (hasPermission('admin')): ?>
                                                                <a class="dropdown-item text-danger" href="?delete=<?php echo $pub['id']; ?>" onclick="return confirm('Are you sure you want to remove this from ready for publication list?')">
                                                                    <i class="bx bx-trash me-1"></i> Remove
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

    <!-- Add Project Modal -->
    <?php if (!empty($ready_projects)): ?>
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Project to Ready for Publication</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Project <span class="text-danger">*</span></label>
                            <select class="form-select" name="project_id" required>
                                <option value="">Choose a project...</option>
                                <?php foreach ($ready_projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['project_name']); ?> 
                                        (<?php echo htmlspecialchars($project['project_id']); ?>)
                                        - <?php echo htmlspecialchars($project['mentor_name'] ?? 'No mentor'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Only projects marked as "Ready for Publication" are shown.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_project" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add to List
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Manual Add Publication Modal -->
    <div class="modal fade" id="addManualModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Publication</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Project <span class="text-danger">*</span></label>
                                <select class="form-select" name="manual_project_id" id="manual_project_id" required>
                                    <option value="">Choose a project...</option>
                                    <?php foreach ($all_projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($project['project_name']); ?>"
                                                data-mentor="<?php echo htmlspecialchars($project['mentor_specialization'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($project['project_name']); ?> 
                                            (<?php echo htmlspecialchars($project['project_id']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Publication Status</label>
                                <select class="form-select" name="publication_status">
                                    <option value="pending">Pending</option>
                                    <option value="in_review">In Review</option>
                                    <option value="approved">Approved</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>
                        

                        
                        <div class="mb-3">
                            <label class="form-label">Paper Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="paper_title" id="paper_title" required 
                                   placeholder="Enter paper title...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mentor Affiliation</label>
                            <input type="text" class="form-control" name="mentor_affiliation" id="mentor_affiliation" 
                                   placeholder="Mentor's institutional affiliation...">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Draft Link</label>
                                <input type="url" class="form-control" name="first_draft_link" 
                                       placeholder="https://drive.google.com/...">
                                <div class="form-text">Link to the first draft document</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Plagiarism Report Link</label>
                                <input type="url" class="form-control" name="plagiarism_report_link" 
                                       placeholder="https://drive.google.com/...">
                                <div class="form-text">Link to the plagiarism check report</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="Additional notes about this publication..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_manual" class="btn btn-success">
                            <i class="bx bx-plus me-1"></i> Create Publication
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        document.querySelectorAll('select[name="status"]').forEach(function(select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Auto-populate fields when project is selected
        document.getElementById('manual_project_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const projectName = selectedOption.getAttribute('data-name');
                const mentorSpecialization = selectedOption.getAttribute('data-mentor');
                
                document.getElementById('paper_title').value = projectName;
                document.getElementById('mentor_affiliation').value = mentorSpecialization || '';
            } else {
                document.getElementById('paper_title').value = '';
                document.getElementById('mentor_affiliation').value = '';
            }
        });
    </script>
</body>

</html> 