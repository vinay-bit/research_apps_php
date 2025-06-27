<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Project.php';

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

$project = new Project();
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
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Project Details - <?php echo htmlspecialchars($project_data['project_name']); ?> - Research Apps</title>
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
                        <!-- Header -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">
                                                <span class="text-muted fw-light">Project Management /</span> 
                                                <?php echo htmlspecialchars($project_data['project_name']); ?>
                                            </h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($project_data['project_id']); ?></small>
                                        </div>
                                        <div>
                                            <a href="edit.php?id=<?php echo $project_id; ?>" class="btn btn-primary me-2">
                                                <i class="bx bx-edit me-1"></i> Edit Project
                                            </a>
                                            <a href="list.php" class="btn btn-secondary">
                                                <i class="bx bx-arrow-back me-1"></i> Back to List
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Project Overview -->
                            <div class="col-xl-8 col-lg-7 col-md-7">
                                <!-- Basic Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Project Overview</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-3">
                                                <h6 class="mb-0">Project Name</h6>
                                            </div>
                                            <div class="col-sm-9">
                                                <strong><?php echo htmlspecialchars($project_data['project_name']); ?></strong>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-3">
                                                <h6 class="mb-0">Status</h6>
                                            </div>
                                            <div class="col-sm-9">
                                                <?php 
                                                $status_class = 'bg-secondary';
                                                if (strpos($project_data['status_name'], 'completed') !== false) {
                                                    $status_class = 'bg-success';
                                                } elseif (strpos($project_data['status_name'], 'in progress') !== false) {
                                                    $status_class = 'bg-primary';
                                                } elseif (strpos($project_data['status_name'], 'yet to start') !== false) {
                                                    $status_class = 'bg-warning';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($project_data['status_name']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-3">
                                                <h6 class="mb-0">Subject</h6>
                                            </div>
                                            <div class="col-sm-9">
                                                <?php if ($project_data['subject_name']): ?>
                                                    <span class="badge bg-label-secondary"><?php echo htmlspecialchars($project_data['subject_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-3">
                                                <h6 class="mb-0">Prototype</h6>
                                            </div>
                                            <div class="col-sm-9">
                                                <?php if ($project_data['has_prototype'] == 'Yes'): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($project_data['description']): ?>
                                        <div class="row mb-3">
                                            <div class="col-sm-3">
                                                <h6 class="mb-0">Description</h6>
                                            </div>
                                            <div class="col-sm-9">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($project_data['description'])); ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($project_data['drive_link']): ?>
                                        <div class="row mb-3">
                                            <div class="col-sm-3">
                                                <h6 class="mb-0">Drive Link</h6>
                                            </div>
                                            <div class="col-sm-9">
                                                <a href="<?php echo htmlspecialchars($project_data['drive_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-link-external me-1"></i> Open Drive
                                                </a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Timeline -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Timeline & Deadlines</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h6>Start Date</h6>
                                                <?php if ($project_data['start_date']): ?>
                                                    <p class="mb-0"><?php echo date('F d, Y', strtotime($project_data['start_date'])); ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Not set</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6>End Date (Deadline)</h6>
                                                <?php if ($project_data['end_date']): ?>
                                                    <?php 
                                                    $end_date = strtotime($project_data['end_date']);
                                                    $today = time();
                                                    $days_remaining = ceil(($end_date - $today) / (60 * 60 * 24));
                                                    ?>
                                                    <p class="mb-1"><?php echo date('F d, Y', $end_date); ?></p>
                                                    <?php if ($days_remaining < 0): ?>
                                                        <span class="badge bg-danger">Overdue by <?php echo abs($days_remaining); ?> days</span>
                                                    <?php elseif ($days_remaining <= 7): ?>
                                                        <span class="badge bg-warning">Due in <?php echo $days_remaining; ?> days</span>
                                                    <?php elseif ($days_remaining <= 30): ?>
                                                        <span class="badge bg-info"><?php echo $days_remaining; ?> days remaining</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><?php echo $days_remaining; ?> days remaining</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Not set</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6>Assigned Date</h6>
                                                <?php if ($project_data['assigned_date']): ?>
                                                    <p class="mb-0"><?php echo date('F d, Y', strtotime($project_data['assigned_date'])); ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Not set</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h6>Completion Date</h6>
                                                <?php if ($project_data['completion_date']): ?>
                                                    <p class="mb-0"><?php echo date('F d, Y', strtotime($project_data['completion_date'])); ?></p>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Not completed yet</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assigned Students -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Assigned Students (<?php echo count($assigned_students); ?>)</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($assigned_students)): ?>
                                            <p class="text-muted mb-0">No students assigned to this project.</p>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($assigned_students as $student): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar flex-shrink-0 me-3">
                                                                <div class="avatar-initial bg-label-primary rounded-circle">
                                                                    <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($student['full_name']); ?></h6>
                                                                <small class="text-muted">
                                                                    <?php echo htmlspecialchars($student['student_id']); ?> - <?php echo htmlspecialchars($student['grade']); ?>
                                                                </small>
                                                                <?php if ($student['email_address']): ?>
                                                                    <br><small class="text-info"><?php echo htmlspecialchars($student['email_address']); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Project Tags -->
                                <?php if (!empty($assigned_tags)): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Project Tags</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($assigned_tags as $tag): ?>
                                            <span class="badge tag-badge tag-<?php echo htmlspecialchars($tag['color']); ?> me-1 mb-1">
                                                <?php echo htmlspecialchars($tag['tag_name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Notes -->
                                <?php if ($project_data['notes']): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Additional Notes</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($project_data['notes'])); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-xl-4 col-lg-5 col-md-5">
                                <!-- Team -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Project Team</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Lead Mentor -->
                                        <div class="mb-3">
                                            <h6 class="text-muted">Lead Mentor</h6>
                                            <?php if ($project_data['mentor_name']): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <div class="avatar-initial bg-label-info rounded-circle">
                                                            <?php echo strtoupper(substr($project_data['mentor_name'], 0, 2)); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($project_data['mentor_name']); ?></h6>
                                                        <small class="text-muted">Lead Mentor</small>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Not assigned</p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Additional Mentors -->
                                        <?php if (!empty($assigned_mentors)): ?>
                                        <div class="mb-3">
                                            <h6 class="text-muted">Additional Mentors</h6>
                                            <?php foreach ($assigned_mentors as $mentor): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar avatar-sm flex-shrink-0 me-2">
                                                        <div class="avatar-initial bg-label-success rounded-circle">
                                                            <?php echo strtoupper(substr($mentor['full_name'], 0, 2)); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <small class="fw-semibold"><?php echo htmlspecialchars($mentor['full_name']); ?></small>
                                                        <?php if ($mentor['specialization']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($mentor['specialization']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <!-- RBM -->
                                        <div class="mb-0">
                                            <h6 class="text-muted">Research Branch Manager</h6>
                                            <?php if ($project_data['rbm_name']): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <div class="avatar-initial bg-label-warning rounded-circle">
                                                            <?php echo strtoupper(substr($project_data['rbm_name'], 0, 2)); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($project_data['rbm_name']); ?></h6>
                                                        <?php if ($project_data['rbm_branch']): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($project_data['rbm_branch']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Not assigned</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Statistics -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Project Statistics</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Students Assigned</span>
                                            <span class="badge bg-primary"><?php echo count($assigned_students); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Additional Mentors</span>
                                            <span class="badge bg-info"><?php echo count($assigned_mentors); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Project Tags</span>
                                            <span class="badge bg-secondary"><?php echo count($assigned_tags); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Created</span>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($project_data['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
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
</body>

</html> 