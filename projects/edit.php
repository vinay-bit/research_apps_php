<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../classes/Project.php';
require_once '../classes/User.php';
require_once '../classes/Student.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /research_apps/login.php");
    exit();
}

// Check if project ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: list.php?error=Project not found");
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

$project = new Project();
$user = new User($db);
$student = new Student($db);
$project_id = $_GET['id'];

// Get project details
$project_data = $project->getById($project_id);
if (!$project_data) {
    header("Location: list.php?error=Project not found");
    exit();
}

// Get assigned students, mentors, and tags
$assigned_students = $project->getAssignedStudents($project_id);
$assigned_mentors = $project->getAssignedMentors($project_id);
$assigned_tags = $project->getAssignedTags($project_id);

// Get available options for dropdowns
$mentors = $user->getByType('mentor');
$rbms = $user->getByType('rbm');
$students = $student->getAll();
$statuses = $project->getAllStatuses();
$subjects = $project->getAllSubjects();
$tags = $project->getAllTags();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['project_name']) || empty($_POST['status_id']) || empty($_POST['lead_mentor_id'])) {
            throw new Exception("Project name, status, and lead mentor are required.");
        }

        // Prepare project data
        $project_data_update = [
            'project_name' => $_POST['project_name'],
            'description' => $_POST['description'] ?? '',
            'status_id' => $_POST['status_id'],
            'lead_mentor_id' => $_POST['lead_mentor_id'],
            'subject_id' => !empty($_POST['subject_id']) ? $_POST['subject_id'] : null,
            'has_prototype' => $_POST['has_prototype'] ?? 'No',
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'assigned_date' => !empty($_POST['assigned_date']) ? $_POST['assigned_date'] : null,
            'completion_date' => !empty($_POST['completion_date']) ? $_POST['completion_date'] : null,
            'drive_link' => $_POST['drive_link'] ?? '',
            'rbm_id' => !empty($_POST['rbm_id']) ? $_POST['rbm_id'] : null,
            'notes' => $_POST['notes'] ?? ''
        ];

        // Update project
        if ($project->updateProject($project_id, $project_data_update)) {
            // Update student assignments
            $assigned_students_ids = $_POST['assigned_students'] ?? [];
            $project->updateStudentAssignments($project_id, $assigned_students_ids);

            // Update mentor assignments
            $assigned_mentors_ids = $_POST['assigned_mentors'] ?? [];
            $project->updateMentorAssignments($project_id, $assigned_mentors_ids);

            // Update tag assignments
            $assigned_tags_ids = $_POST['assigned_tags'] ?? [];
            $project->updateTagAssignments($project_id, $assigned_tags_ids);

            // Handle new status
            if (!empty($_POST['new_status'])) {
                $project->addStatus($_POST['new_status']);
            }

            // Handle new subject
            if (!empty($_POST['new_subject'])) {
                $project->addSubject($_POST['new_subject']);
            }

            // Handle new tag
            if (!empty($_POST['new_tag_name']) && !empty($_POST['new_tag_color'])) {
                $project->addTag($_POST['new_tag_name'], $_POST['new_tag_color']);
            }

            header("Location: view.php?id=" . $project_id . "&success=Project updated successfully");
            exit();
        } else {
            throw new Exception("Failed to update project.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Convert assigned arrays to ID arrays for form population
$assigned_student_ids = array_column($assigned_students, 'id');
$assigned_mentor_ids = array_column($assigned_mentors, 'id');
$assigned_tag_ids = array_column($assigned_tags, 'id');
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Edit Project - <?php echo htmlspecialchars($project_data['project_name']); ?> - Research Apps</title>
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
    
    <style>
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
            margin-left: 10px;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
        }
        .student-card, .mentor-card {
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
        }
        .student-card.selected, .mentor-card.selected {
            border-color: #696cff;
            background-color: #f0f0ff;
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
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Project Management /</span> Edit Project
                        </h4>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-xl-8">
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Project Information</h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($project_data['project_id']); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label" for="project_name">Project Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="project_name" name="project_name" 
                                                           value="<?php echo htmlspecialchars($project_data['project_name']); ?>" required>
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label" for="description">Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($project_data['description']); ?></textarea>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="status_id">Status <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="status_id" name="status_id" required>
                                                        <option value="">Select Status</option>
                                                        <?php foreach ($statuses as $status): ?>
                                                            <option value="<?php echo $status['id']; ?>" 
                                                                    <?php echo ($status['id'] == $project_data['status_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($status['status_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="subject_id">Subject</label>
                                                    <select class="form-select" id="subject_id" name="subject_id">
                                                        <option value="">Select Subject</option>
                                                        <?php foreach ($subjects as $subject): ?>
                                                            <option value="<?php echo $subject['id']; ?>" 
                                                                    <?php echo ($subject['id'] == $project_data['subject_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="lead_mentor_id">Lead Mentor <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="lead_mentor_id" name="lead_mentor_id" required>
                                                        <option value="">Select Lead Mentor</option>
                                                        <?php foreach ($mentors as $mentor): ?>
                                                            <option value="<?php echo $mentor['id']; ?>" 
                                                                    <?php echo ($mentor['id'] == $project_data['lead_mentor_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($mentor['full_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="rbm_id">Research Branch Manager</label>
                                                    <select class="form-select" id="rbm_id" name="rbm_id">
                                                        <option value="">Select RBM</option>
                                                        <?php foreach ($rbms as $rbm): ?>
                                                            <option value="<?php echo $rbm['id']; ?>" 
                                                                    <?php echo ($rbm['id'] == $project_data['rbm_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($rbm['full_name']); ?>
                                                                <?php if ($rbm['branch']): ?>
                                                                    - <?php echo htmlspecialchars($rbm['branch']); ?>
                                                                <?php endif; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="has_prototype">Prototype</label>
                                                    <select class="form-select" id="has_prototype" name="has_prototype">
                                                        <option value="No" <?php echo ($project_data['has_prototype'] == 'No') ? 'selected' : ''; ?>>No</option>
                                                        <option value="Yes" <?php echo ($project_data['has_prototype'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="drive_link">Drive Link</label>
                                                    <input type="url" class="form-control" id="drive_link" name="drive_link" 
                                                           value="<?php echo htmlspecialchars($project_data['drive_link']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Timeline -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Timeline & Dates</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="start_date">Start Date</label>
                                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                                           value="<?php echo $project_data['start_date']; ?>" onchange="calculateEndDate()">
                                                    <div class="form-text">End date will be automatically calculated (4 months from start date)</div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="end_date_display">End Date (Auto-calculated)</label>
                                                    <input type="text" class="form-control" id="end_date_display" 
                                                           value="<?php echo $project_data['end_date'] ? date('Y-m-d', strtotime($project_data['end_date'])) : ''; ?>" readonly>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="assigned_date">Assigned Date</label>
                                                    <input type="date" class="form-control" id="assigned_date" name="assigned_date" 
                                                           value="<?php echo $project_data['assigned_date']; ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="completion_date">Completion Date</label>
                                                    <input type="date" class="form-control" id="completion_date" name="completion_date" 
                                                           value="<?php echo $project_data['completion_date']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Notes -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Additional Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label" for="notes">Notes</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo htmlspecialchars($project_data['notes']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assignments -->
                                <div class="col-xl-4">
                                    <!-- Assigned Students -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0">Assigned Students</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <input type="text" class="form-control mb-2" id="student_search" placeholder="Search students...">
                                                <div style="max-height: 300px; overflow-y: auto;" id="students_list">
                                                    <?php foreach ($students as $student): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" name="assigned_students[]" 
                                                                   value="<?php echo $student['id']; ?>" id="student_<?php echo $student['id']; ?>"
                                                                   <?php echo in_array($student['id'], $assigned_student_ids) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="student_<?php echo $student['id']; ?>">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar avatar-sm me-2">
                                                                        <div class="avatar-initial bg-label-primary rounded-circle">
                                                                            <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-semibold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?> - <?php echo htmlspecialchars($student['grade']); ?></small>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Mentors -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0">Additional Mentors</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div style="max-height: 200px; overflow-y: auto;">
                                                    <?php foreach ($mentors as $mentor): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" name="assigned_mentors[]" 
                                                                   value="<?php echo $mentor['id']; ?>" id="mentor_<?php echo $mentor['id']; ?>"
                                                                   <?php echo in_array($mentor['id'], $assigned_mentor_ids) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="mentor_<?php echo $mentor['id']; ?>">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar avatar-sm me-2">
                                                                        <div class="avatar-initial bg-label-info rounded-circle">
                                                                            <?php echo strtoupper(substr($mentor['full_name'], 0, 2)); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-semibold"><?php echo htmlspecialchars($mentor['full_name']); ?></div>
                                                                        <?php if ($mentor['specialization']): ?>
                                                                            <small class="text-muted"><?php echo htmlspecialchars($mentor['specialization']); ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Tags -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Project Tags</h6>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
                                                <i class="bx bx-plus me-1"></i> Add New Tag
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div style="max-height: 200px; overflow-y: auto;">
                                                    <?php foreach ($tags as $tag): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" name="assigned_tags[]" 
                                                                   value="<?php echo $tag['id']; ?>" id="tag_<?php echo $tag['id']; ?>"
                                                                   <?php echo in_array($tag['id'], $assigned_tag_ids) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="tag_<?php echo $tag['id']; ?>">
                                                                <span class="badge tag-badge tag-<?php echo htmlspecialchars($tag['color']); ?> me-1">
                                                                    <?php echo htmlspecialchars($tag['tag_name']); ?>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <small class="text-muted">Available tags: <?php echo count($tags); ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="card">
                                        <div class="card-body">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bx bx-save me-1"></i> Update Project
                                            </button>
                                            <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">
                                                <i class="bx bx-x me-1"></i> Cancel
                                            </a>
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

    <!-- Add Tag Modal -->
    <div class="modal fade" id="addTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="new_tag_name">Tag Name</label>
                        <input type="text" class="form-control" id="new_tag_name" name="new_tag_name" placeholder="Enter tag name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_tag_color">Tag Color</label>
                        <select class="form-select" id="new_tag_color" name="new_tag_color">
                            <option value="primary">Primary (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="info">Info (Cyan)</option>
                            <option value="warning">Warning (Orange)</option>
                            <option value="danger">Danger (Red)</option>
                            <option value="secondary">Secondary (Gray)</option>
                            <option value="dark">Dark (Black)</option>
                        </select>
                        <div class="mt-2">
                            <span class="badge tag-badge tag-primary me-1" id="tag_preview">Preview</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addNewTag()">Add Tag</button>
                </div>
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
        // Calculate end date based on start date
        function calculateEndDate() {
            const startDate = document.getElementById('start_date').value;
            if (startDate) {
                const start = new Date(startDate);
                const end = new Date(start.getFullYear(), start.getMonth() + 4, start.getDate());
                document.getElementById('end_date_display').value = end.toISOString().split('T')[0];
            }
        }

        // Student search functionality
        document.getElementById('student_search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const studentsList = document.getElementById('students_list');
            const students = studentsList.querySelectorAll('.form-check');
            
            students.forEach(function(student) {
                const label = student.querySelector('.form-check-label');
                const text = label.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    student.style.display = 'block';
                } else {
                    student.style.display = 'none';
                }
            });
        });

        // Tag color preview
        document.getElementById('new_tag_color').addEventListener('change', function() {
            const preview = document.getElementById('tag_preview');
            const color = this.value;
            preview.className = `badge tag-badge tag-${color} me-1`;
            preview.textContent = document.getElementById('new_tag_name').value || 'Preview';
        });

        document.getElementById('new_tag_name').addEventListener('input', function() {
            const preview = document.getElementById('tag_preview');
            preview.textContent = this.value || 'Preview';
        });

        // Add new tag
        function addNewTag() {
            const tagName = document.getElementById('new_tag_name').value.trim();
            const tagColor = document.getElementById('new_tag_color').value;
            
            if (!tagName) {
                alert('Please enter a tag name');
                return;
            }

            // Add hidden inputs to form
            const form = document.querySelector('form');
            const tagNameInput = document.createElement('input');
            tagNameInput.type = 'hidden';
            tagNameInput.name = 'new_tag_name';
            tagNameInput.value = tagName;
            form.appendChild(tagNameInput);

            const tagColorInput = document.createElement('input');
            tagColorInput.type = 'hidden';
            tagColorInput.name = 'new_tag_color';
            tagColorInput.value = tagColor;
            form.appendChild(tagColorInput);

            // Close modal and submit form
            const modal = bootstrap.Modal.getInstance(document.getElementById('addTagModal'));
            modal.hide();
            
            // Show success message
            alert('Tag will be added when you save the project');
        }

        // Initialize end date calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateEndDate();
        });
    </script>
</body>

</html> 