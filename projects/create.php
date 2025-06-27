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
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Handle new status addition
        if (isset($_POST['add_status']) && !empty($_POST['new_status'])) {
            $project->addStatus($_POST['new_status']);
            $message = "New status added successfully!";
        }
        
        // Handle new subject addition
        if (isset($_POST['add_subject']) && !empty($_POST['new_subject'])) {
            $project->addSubject($_POST['new_subject']);
            $message = "New subject added successfully!";
        }
        
        // Handle new tag addition
        if (isset($_POST['add_tag']) && !empty($_POST['new_tag'])) {
            $tag_color = !empty($_POST['new_tag_color']) ? $_POST['new_tag_color'] : '#007bff';
            $project->addTag($_POST['new_tag'], $tag_color);
            $message = "New tag added successfully!";
        }
        
        // Handle project creation
        if (isset($_POST['create_project'])) {
            $project->project_name = $_POST['project_name'];
            $project->status_id = $_POST['status_id'];
            $project->lead_mentor_id = !empty($_POST['lead_mentor_id']) ? $_POST['lead_mentor_id'] : null;
            $project->subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
            $project->has_prototype = $_POST['has_prototype'];
            $project->start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $project->assigned_date = !empty($_POST['assigned_date']) ? $_POST['assigned_date'] : null;
            $project->completion_date = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
            $project->drive_link = $_POST['drive_link'];
            $project->rbm_id = !empty($_POST['rbm_id']) ? $_POST['rbm_id'] : null;
            $project->description = $_POST['description'];
            $project->notes = $_POST['notes'];
            
            if ($project->create()) {
                $project_id = $project->id;
                
                // Assign students
                if (!empty($_POST['assigned_students'])) {
                    $project->assignStudents($project_id, $_POST['assigned_students']);
                }
                
                // Assign mentors
                if (!empty($_POST['assigned_mentors'])) {
                    $project->assignMentors($project_id, $_POST['assigned_mentors']);
                }
                
                // Assign tags
                if (!empty($_POST['assigned_tags'])) {
                    $project->assignTags($project_id, $_POST['assigned_tags']);
                }
                
                header("Location: list.php?success=Project created successfully!");
                exit();
            } else {
                $error = "Failed to create project. Please try again.";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get data for dropdowns
$statuses = $project->getStatuses();
$mentors = $project->getMentors();
$rbms = $project->getRBMs();
$subjects = $project->getSubjects();
$students = $project->getStudents();
$tags = $project->getTags();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Create Project - Research Apps</title>
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
    
    <!-- OMOTEC Custom Theme -->


    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- Helpers -->
    <script src="../Apps/assets/vendor/js/helpers.js"></script>
    <script src="../Apps/assets/js/config.js"></script>

    <style>
        .multi-select-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
        }
        .multi-select-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .multi-select-item:last-child {
            border-bottom: none;
        }
        .form-check-label {
            cursor: pointer;
            width: 100%;
        }
        .form-check-label .badge {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
        .tag-badge {
            color: #495057 !important;
            font-weight: 500;
            border: none;
        }
        .tag-primary { background-color: #e3f2fd; border-color: #2196f3; color: #1976d2 !important; }
        .tag-success { background-color: #e8f5e8; border-color: #4caf50; color: #388e3c !important; }
        .tag-info { background-color: #e1f5fe; border-color: #00bcd4; color: #0097a7 !important; }
        .tag-warning { background-color: #fff8e1; border-color: #ff9800; color: #f57c00 !important; }
        .tag-danger { background-color: #ffebee; border-color: #f44336; color: #d32f2f !important; }
        .tag-secondary { background-color: #f5f5f5; border-color: #9e9e9e; color: #424242 !important; }
        .tag-dark { background-color: #f5f5f5; border-color: #424242; color: #212121 !important; }
        .tag-preview {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            color: white;
            font-size: 0.875rem;
            margin: 0.125rem;
        }
    </style>
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
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <span class="text-muted fw-light">Project Management /</span> Create New Project
                                        </h5>
                                        <a href="/research_apps/projects/list.php" class="btn btn-secondary">
                                            <i class="bx bx-arrow-back me-1"></i> Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="row">
                            <!-- Basic Information -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Project Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="project_name" required>
                                                <div class="form-text">Enter a descriptive name for the project</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select class="form-select" name="status_id" required>
                                                        <option value="">Select Status</option>
                                                        <?php foreach ($statuses as $status): ?>
                                                            <option value="<?php echo $status['id']; ?>">
                                                                <?php echo htmlspecialchars($status['status_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addStatusModal">
                                                        <i class="bx bx-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3" placeholder="Describe the project objectives and scope..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Information -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Assignment Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Lead Mentor</label>
                                                <select class="form-select" name="lead_mentor_id">
                                                    <option value="">Select Lead Mentor</option>
                                                    <?php foreach ($mentors as $mentor): ?>
                                                        <option value="<?php echo $mentor['id']; ?>">
                                                            <?php echo htmlspecialchars($mentor['full_name']); ?>
                                                            <?php if ($mentor['specialization']): ?>
                                                                - <?php echo htmlspecialchars($mentor['specialization']); ?>
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">RBM (Research Branch Manager)</label>
                                                <select class="form-select" name="rbm_id">
                                                    <option value="">Select RBM</option>
                                                    <?php foreach ($rbms as $rbm): ?>
                                                        <option value="<?php echo $rbm['id']; ?>">
                                                            <?php echo htmlspecialchars($rbm['full_name']); ?>
                                                            <?php if ($rbm['branch']): ?>
                                                                - <?php echo htmlspecialchars($rbm['branch']); ?>
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Additional Mentors</label>
                                                <div class="multi-select-container">
                                                    <?php foreach ($mentors as $mentor): ?>
                                                        <div class="multi-select-item">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="assigned_mentors[]" value="<?php echo $mentor['id']; ?>" id="mentor_<?php echo $mentor['id']; ?>">
                                                                <label class="form-check-label" for="mentor_<?php echo $mentor['id']; ?>">
                                                                    <strong><?php echo htmlspecialchars($mentor['full_name']); ?></strong>
                                                                    <?php if ($mentor['specialization']): ?>
                                                                        <small class="text-muted">
                                                                            - <?php echo htmlspecialchars($mentor['specialization']); ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="form-text">Select additional mentors to assist with this project (optional)</div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Assigned Students</label>
                                                <div class="multi-select-container">
                                                    <?php foreach ($students as $student): ?>
                                                        <div class="multi-select-item">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="assigned_students[]" value="<?php echo $student['id']; ?>" id="student_<?php echo $student['id']; ?>">
                                                                <label class="form-check-label" for="student_<?php echo $student['id']; ?>">
                                                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                                    <small class="text-muted">
                                                                        (<?php echo htmlspecialchars($student['student_id']); ?> - <?php echo htmlspecialchars($student['grade']); ?>)
                                                                        <?php if ($student['affiliation']): ?>
                                                                            - <?php echo htmlspecialchars($student['affiliation']); ?>
                                                                        <?php endif; ?>
                                                                    </small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="form-text">Select multiple students to assign to this project</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Project Details -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Project Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Subject</label>
                                                <div class="input-group">
                                                    <select class="form-select" name="subject_id">
                                                        <option value="">Select Subject</option>
                                                        <?php foreach ($subjects as $subject): ?>
                                                            <option value="<?php echo $subject['id']; ?>">
                                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                                        <i class="bx bx-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Prototype</label>
                                                <select class="form-select" name="has_prototype">
                                                    <option value="No">No</option>
                                                    <option value="Yes">Yes</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Project Tags</label>
                                                <div class="multi-select-container">
                                                    <?php if (empty($tags)): ?>
                                                        <p class="text-muted">No tags available. <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">Add the first tag</button></p>
                                                    <?php else: ?>
                                                        <?php foreach ($tags as $tag): ?>
                                                            <div class="multi-select-item">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="assigned_tags[]" value="<?php echo $tag['id']; ?>" id="tag_<?php echo $tag['id']; ?>">
                                                                    <label class="form-check-label" for="tag_<?php echo $tag['id']; ?>">
                                                                        <span class="badge tag-badge tag-<?php echo htmlspecialchars($tag['color']); ?> me-1">
                                                                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
                                                        <i class="bx bx-plus me-1"></i> Add New Tag
                                                    </button>
                                                </div>
                                                <small class="text-muted">Available tags: <?php echo count($tags); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Timeline</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Start Date</label>
                                                <input type="date" class="form-control" name="start_date" id="start_date" onchange="calculateEndDate()">
                                                <div class="form-text">End date will be auto-calculated (4 months later)</div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">End Date</label>
                                                <input type="date" class="form-control" name="end_date" id="end_date" readonly>
                                                <div class="form-text">Auto-generated based on start date</div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Assigned Date</label>
                                                <input type="date" class="form-control" name="assigned_date" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Completion Date</label>
                                                <input type="date" class="form-control" name="completion_date">
                                                <div class="form-text">Leave empty if not completed yet</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Drive Link</label>
                                                <input type="url" class="form-control" name="drive_link" placeholder="https://drive.google.com/...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Notes -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" name="notes" rows="4" placeholder="Any additional notes or comments about the project..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <a href="/research_apps/projects/list.php" class="btn btn-secondary">
                                                <i class="bx bx-x me-1"></i> Cancel
                                            </a>
                                            <button type="submit" name="create_project" class="btn btn-primary">
                                                <i class="bx bx-check me-1"></i> Create Project
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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

    <!-- Add Status Modal -->
    <div class="modal fade" id="addStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status Name</label>
                            <input type="text" class="form-control" name="new_status" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_status" class="btn btn-primary">Add Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" class="form-control" name="new_subject" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Tag Modal -->
    <div class="modal fade" id="addTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tag Name</label>
                            <input type="text" class="form-control" name="new_tag" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tag Color</label>
                            <input type="color" class="form-control" name="new_tag_color" value="#007bff">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_tag" class="btn btn-primary">Add Tag</button>
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
        function calculateEndDate() {
            const startDate = document.getElementById('start_date').value;
            if (startDate) {
                const start = new Date(startDate);
                const end = new Date(start);
                end.setMonth(end.getMonth() + 4);
                
                const year = end.getFullYear();
                const month = String(end.getMonth() + 1).padStart(2, '0');
                const day = String(end.getDate()).padStart(2, '0');
                
                document.getElementById('end_date').value = `${year}-${month}-${day}`;
            }
        }

        // Set today's date as default for assigned date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const assignedDateField = document.querySelector('input[name="assigned_date"]');
            if (assignedDateField && !assignedDateField.value) {
                assignedDateField.value = today;
            }
        });
    </script>
</body>

</html>