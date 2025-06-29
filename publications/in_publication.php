<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/InPublication.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$inPublication = new InPublication();
$message = '';
$error = '';

// Handle conference application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_conference'])) {
    try {
        $data = [
            'conference_id' => intval($_POST['conference_id']),
            'application_date' => $_POST['application_date'],
            'submission_deadline' => !empty($_POST['submission_deadline']) ? $_POST['submission_deadline'] : null,
            'submission_link' => trim($_POST['submission_link']),
            'notes' => trim($_POST['notes'])
        ];
        
        $in_publication_id = intval($_POST['in_publication_id']);
        if ($inPublication->applyToConference($in_publication_id, $data)) {
            $message = "Successfully applied to conference!";
        } else {
            $error = "Error applying to conference. Please try again.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle journal application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_journal'])) {
    try {
        $data = [
            'journal_id' => intval($_POST['journal_id']),
            'application_date' => $_POST['application_date'],
            'submission_deadline' => !empty($_POST['submission_deadline']) ? $_POST['submission_deadline'] : null,
            'submission_link' => trim($_POST['submission_link']),
            'manuscript_id' => trim($_POST['manuscript_id']),
            'notes' => trim($_POST['notes'])
        ];
        
        $in_publication_id = intval($_POST['in_publication_id']);
        if ($inPublication->applyToJournal($in_publication_id, $data)) {
            $message = "Successfully applied to journal!";
        } else {
            $error = "Error applying to journal. Please try again.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    try {
        $application_id = intval($_POST['application_id']);
        $data = [
            'status' => $_POST['status'],
            'feedback' => trim($_POST['feedback']),
            'response_date' => !empty($_POST['response_date']) ? $_POST['response_date'] : null
        ];
        
        // For conferences, include acceptance fields if status is accepted
        if ($_POST['application_type'] == 'conference') {
            if ($data['status'] == 'accepted') {
                $data['acceptance_date'] = !empty($_POST['acceptance_date']) ? $_POST['acceptance_date'] : null;
                $data['reviewer_changes'] = trim($_POST['reviewer_changes']);
                $data['formatted_paper_link'] = trim($_POST['formatted_paper_link']);
                $data['presentation_link'] = trim($_POST['presentation_link']);
                $data['attended'] = isset($_POST['attended']) ? intval($_POST['attended']) : null;
                $data['certificate_received'] = isset($_POST['certificate_received']) ? intval($_POST['certificate_received']) : null;
            }
            $success = $inPublication->updateConferenceApplication($application_id, $data);
        } else {
            $success = $inPublication->updateJournalApplication($application_id, $data);
        }
        
        if ($success) {
            $message = "Application status updated successfully!";
        } else {
            $error = "Error updating application status. Please try again.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle filters
$filters = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get data
$publications = $inPublication->getAll($filters);
$all_conferences = $inPublication->getAllConferences();
$all_journals = $inPublication->getAllJournals();
$statistics = $inPublication->getStatistics();

// Get current user info
$current_user = $_SESSION;
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>In Publication - Research Apps</title>
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
                                            <span class="text-muted fw-light">Research & Publications /</span> In Publication
                                        </h5>
                                        <div>
                                            <a href="ready_for_publication.php" class="btn btn-secondary">
                                                <i class="bx bx-arrow-back me-1"></i> Back to Ready for Publication
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
                                                    <i class="bx bx-paper-plane text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">In Publication</span>
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
                                                    <i class="bx bx-calendar-event text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Conference Apps</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $conf_total = 0;
                                            foreach ($statistics['conference_applications'] as $app) {
                                                $conf_total += $app['count'];
                                            }
                                            echo $conf_total;
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
                                                    <i class="bx bx-book-open text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Journal Apps</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $journal_total = 0;
                                            foreach ($statistics['journal_applications'] as $app) {
                                                $journal_total += $app['count'];
                                            }
                                            echo $journal_total;
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
                                        <span class="fw-semibold d-block mb-1">Accepted</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $accepted_count = 0;
                                            foreach ($statistics['conference_applications'] as $app) {
                                                if ($app['status'] == 'accepted') $accepted_count += $app['count'];
                                            }
                                            foreach ($statistics['journal_applications'] as $app) {
                                                if ($app['status'] == 'accepted') $accepted_count += $app['count'];
                                            }
                                            echo $accepted_count;
                                            ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search and Filter -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Search Publications</label>
                                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search by paper title or project name...">
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary me-2">
                                                    <i class="bx bx-search me-1"></i> Search
                                                </button>
                                                <a href="in_publication.php" class="btn btn-secondary">
                                                    <i class="bx bx-refresh me-1"></i> Clear
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Publications List -->
                        <div class="row">
                            <div class="col-12">
                                <?php if (empty($publications)): ?>
                                    <div class="card">
                                        <div class="card-body text-center py-5">
                                            <div class="empty-state">
                                                <i class="bx bx-paper-plane display-4 text-muted"></i>
                                                <h5 class="mt-2">No publications in workflow</h5>
                                                <p class="text-muted">Publications moved from "Ready for Publication" with approved status will appear here.</p>
                                                <a href="ready_for_publication.php" class="btn btn-primary">
                                                    <i class="bx bx-arrow-back me-1"></i> Go to Ready for Publication
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Publications in Workflow</h5>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Paper Title</th>
                                                        <th>Project</th>
                                                        <th>Authors</th>
                                                        <th>Conference Apps</th>
                                                        <th>Journal Apps</th>
                                                        <th>Accepted</th>
                                                        <th>Attended</th>
                                                        <th>Certificate</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($publications as $pub): 
                                                        // Get students for this publication
                                                        $students = $inPublication->getStudentsByPublicationId($pub['id']);
                                                        
                                                        // Calculate total accepted
                                                        $total_accepted = $pub['accepted_conferences'] + $pub['accepted_journals'];
                                                    ?>
                                                        <tr style="cursor: pointer;" onclick="toggleDetails(<?php echo $pub['id']; ?>)">
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($pub['paper_title']); ?></strong>
                                                                <br><small class="text-muted">Moved: <?php echo date('M d, Y', strtotime($pub['moved_date'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($pub['project_name']); ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($pub['project_code']); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($students)): ?>
                                                                    <?php 
                                                                    $author_names = array_map(function($student) {
                                                                        return $student['full_name'];
                                                                    }, array_slice($students, 0, 2));
                                                                    echo htmlspecialchars(implode(', ', $author_names));
                                                                    if (count($students) > 2) {
                                                                        echo ' <small class="text-muted">+' . (count($students) - 2) . ' more</small>';
                                                                    }
                                                                    ?>
                                                                <?php else: ?>
                                                                    <small class="text-muted">No authors</small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-warning"><?php echo $pub['conference_applications']; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo $pub['journal_applications']; ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($total_accepted > 0): ?>
                                                                    <span class="badge bg-success"><?php echo $total_accepted; ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">0</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                // Get conference applications for attendance status
                                                                $conf_applications = $inPublication->getConferenceApplications($pub['id']);
                                                                $attended_count = 0;
                                                                $not_attended_count = 0;
                                                                foreach ($conf_applications as $app) {
                                                                    if ($app['status'] == 'accepted') {
                                                                        if (isset($app['attended']) && $app['attended'] === 1) {
                                                                            $attended_count++;
                                                                        } elseif (isset($app['attended']) && $app['attended'] === 0) {
                                                                            $not_attended_count++;
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                                <?php if ($attended_count > 0): ?>
                                                                    <span class="badge bg-success" title="Attended conferences">
                                                                        <i class="bx bx-check me-1"></i><?php echo $attended_count; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if ($not_attended_count > 0): ?>
                                                                    <span class="badge bg-warning" title="Not attended conferences">
                                                                        <i class="bx bx-x me-1"></i><?php echo $not_attended_count; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if ($attended_count == 0 && $not_attended_count == 0): ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                // Get certificate status
                                                                $certificate_count = 0;
                                                                $no_certificate_count = 0;
                                                                foreach ($conf_applications as $app) {
                                                                    if ($app['status'] == 'accepted') {
                                                                        if (isset($app['certificate_received']) && $app['certificate_received'] === 1) {
                                                                            $certificate_count++;
                                                                        } elseif (isset($app['certificate_received']) && $app['certificate_received'] === 0) {
                                                                            $no_certificate_count++;
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                                <?php if ($certificate_count > 0): ?>
                                                                    <span class="badge bg-primary" title="Certificates received">
                                                                        <i class="bx bx-award me-1"></i><?php echo $certificate_count; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if ($no_certificate_count > 0): ?>
                                                                    <span class="badge bg-secondary" title="No certificates">
                                                                        <i class="bx bx-award me-1"></i><?php echo $no_certificate_count; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if ($certificate_count == 0 && $no_certificate_count == 0): ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td onclick="event.stopPropagation();">
                                                                <div class="dropdown">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                                        <i class="bx bx-plus me-1"></i> Apply
                                                                    </button>
                                                                    <div class="dropdown-menu">
                                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#conferenceModal<?php echo $pub['id']; ?>">
                                                                            <i class="bx bx-calendar me-1"></i> Conference
                                                                        </button>
                                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#journalModal<?php echo $pub['id']; ?>">
                                                                            <i class="bx bx-book me-1"></i> Journal
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <!-- Expandable Details Row -->
                                                        <tr id="details-<?php echo $pub['id']; ?>" class="details-row" style="display: none;">
                                                            <td colspan="9">
                                                                <div class="p-4 bg-light border-start border-primary border-3">
                                                                    <!-- Tab Navigation -->
                                                                    <ul class="nav nav-tabs mb-3" id="detailsTab<?php echo $pub['id']; ?>" role="tablist">
                                                                        <li class="nav-item" role="presentation">
                                                                            <button class="nav-link active" id="info-tab-<?php echo $pub['id']; ?>" data-bs-toggle="tab" data-bs-target="#info-<?php echo $pub['id']; ?>" type="button" role="tab">
                                                                                <i class="bx bx-info-circle me-1"></i>Publication Info
                                                                            </button>
                                                                        </li>
                                                                        <li class="nav-item" role="presentation">
                                                                            <button class="nav-link" id="applications-tab-<?php echo $pub['id']; ?>" data-bs-toggle="tab" data-bs-target="#applications-<?php echo $pub['id']; ?>" type="button" role="tab">
                                                                                <i class="bx bx-send me-1"></i>Applications 
                                                                                <span class="badge bg-primary ms-1"><?php echo ($pub['conference_applications'] + $pub['journal_applications']); ?></span>
                                                                            </button>
                                                                        </li>
                                                                    </ul>

                                                                    <!-- Tab Content -->
                                                                    <div class="tab-content" id="detailsTabContent<?php echo $pub['id']; ?>">
                                                                        <!-- Publication Info Tab -->
                                                                        <div class="tab-pane fade show active" id="info-<?php echo $pub['id']; ?>" role="tabpanel">
                                                                            <div class="row g-4">
                                                                                <div class="col-md-4">
                                                                                    <div class="card h-100">
                                                                                        <div class="card-body">
                                                                                            <h6 class="card-title text-primary mb-3">
                                                                                                <i class="bx bx-link me-2"></i>Documents & Links
                                                                                            </h6>
                                                                                            <div class="d-grid gap-2">
                                                                                                <?php if ($pub['first_draft_link']): ?>
                                                                                                    <a href="<?php echo htmlspecialchars($pub['first_draft_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                                                        <i class="bx bx-file me-2"></i>First Draft
                                                                                                    </a>
                                                                                                <?php endif; ?>
                                                                                                <?php if ($pub['plagiarism_report_link']): ?>
                                                                                                    <a href="<?php echo htmlspecialchars($pub['plagiarism_report_link']); ?>" target="_blank" class="btn btn-sm btn-outline-warning">
                                                                                                        <i class="bx bx-shield me-2"></i>Plagiarism Report
                                                                                                    </a>
                                                                                                <?php endif; ?>
                                                                                                <?php if ($pub['ai_detection_link']): ?>
                                                                                                    <a href="<?php echo htmlspecialchars($pub['ai_detection_link']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                                                                        <i class="bx bx-brain me-2"></i>AI Detection
                                                                                                    </a>
                                                                                                <?php endif; ?>
                                                                                                <?php if ($pub['final_paper_link']): ?>
                                                                                                    <a href="<?php echo htmlspecialchars($pub['final_paper_link']); ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                                                                        <i class="bx bx-check me-2"></i>Final Paper
                                                                                                    </a>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                                <div class="col-md-4">
                                                                                    <div class="card h-100">
                                                                                        <div class="card-body">
                                                                                            <h6 class="card-title text-success mb-3">
                                                                                                <i class="bx bx-group me-2"></i>Authors
                                                                                            </h6>
                                                                                            <?php if (!empty($students)): ?>
                                                                                                <?php foreach ($students as $student): ?>
                                                                                                    <div class="d-flex align-items-center mb-3">
                                                                                                        <div class="avatar-initial bg-light text-primary rounded-circle me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                                                                            <i class="bx bx-user"></i>
                                                                                                        </div>
                                                                                                        <div>
                                                                                                            <div class="fw-semibold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                                                                                            <small class="text-muted"><?php echo htmlspecialchars($student['student_affiliation'] ?? $student['original_affiliation']); ?></small>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                <?php endforeach; ?>
                                                                                            <?php else: ?>
                                                                                                <div class="text-center text-muted">
                                                                                                    <i class="bx bx-user-x display-6"></i>
                                                                                                    <p class="mt-2">No authors assigned</p>
                                                                                                </div>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                                <div class="col-md-4">
                                                                                    <div class="card h-100">
                                                                                        <div class="card-body">
                                                                                            <h6 class="card-title text-info mb-3">
                                                                                                <i class="bx bx-user-check me-2"></i>Mentor & Summary
                                                                                            </h6>
                                                                                            <?php if ($pub['mentor_name']): ?>
                                                                                                <div class="d-flex align-items-center mb-3">
                                                                                                    <div class="avatar-initial bg-info text-white rounded-circle me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                                                                        <i class="bx bx-user-circle"></i>
                                                                                                    </div>
                                                                                                    <div>
                                                                                                        <div class="fw-semibold"><?php echo htmlspecialchars($pub['mentor_name']); ?></div>
                                                                                                        <?php if ($pub['mentor_affiliation']): ?>
                                                                                                            <small class="text-muted"><?php echo htmlspecialchars($pub['mentor_affiliation']); ?></small>
                                                                                                        <?php endif; ?>
                                                                                                    </div>
                                                                                                </div>
                                                                                            <?php endif; ?>
                                                                                            
                                                                                            <hr class="my-3">
                                                                                            
                                                                                            <div class="text-center">
                                                                                                <div class="row text-center">
                                                                                                    <div class="col-6">
                                                                                                        <div class="border rounded p-2">
                                                                                                            <div class="h5 mb-0 text-warning"><?php echo $pub['conference_applications']; ?></div>
                                                                                                            <small class="text-muted">Conferences</small>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-6">
                                                                                                        <div class="border rounded p-2">
                                                                                                            <div class="h5 mb-0 text-info"><?php echo $pub['journal_applications']; ?></div>
                                                                                                            <small class="text-muted">Journals</small>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <?php if (($pub['accepted_conferences'] + $pub['accepted_journals']) > 0): ?>
                                                                                                    <div class="mt-2">
                                                                                                        <span class="badge bg-success">
                                                                                                            <i class="bx bx-check me-1"></i><?php echo ($pub['accepted_conferences'] + $pub['accepted_journals']); ?> Accepted
                                                                                                        </span>
                                                                                                    </div>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Applications Tab -->
                                                                        <div class="tab-pane fade" id="applications-<?php echo $pub['id']; ?>" role="tabpanel">
                                                                            <?php 
                                                                            $conf_applications = $inPublication->getConferenceApplications($pub['id']);
                                                                            $journal_applications = $inPublication->getJournalApplications($pub['id']);
                                                                            ?>
                                                                            
                                                                            <?php if (!empty($conf_applications)): ?>
                                                                                <div class="mb-4">
                                                                                    <h6 class="mb-3">
                                                                                        <i class="bx bx-calendar-event text-warning me-2"></i>Conference Applications
                                                                                    </h6>
                                                                                    <?php foreach ($conf_applications as $app): ?>
                                                                                        <div class="card mb-3">
                                                                                            <div class="card-body">
                                                                                                <div class="row align-items-center">
                                                                                                    <div class="col-md-4">
                                                                                                        <h6 class="mb-1"><?php echo htmlspecialchars($app['conference_name']); ?></h6>
                                                                                                        <small class="text-muted">Applied: <?php echo date('M d, Y', strtotime($app['application_date'])); ?></small>
                                                                                                    </div>
                                                                                                    <div class="col-md-3">
                                                                                                        <?php
                                                                                                        $status_class = 'secondary';
                                                                                                        if ($app['status'] == 'accepted') $status_class = 'success';
                                                                                                        elseif ($app['status'] == 'rejected') $status_class = 'danger';
                                                                                                        elseif ($app['status'] == 'under_review') $status_class = 'warning';
                                                                                                        ?>
                                                                                                        <span class="badge bg-<?php echo $status_class; ?> fs-6"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span>
                                                                                                        <?php if ($app['status'] == 'accepted' && $app['acceptance_date']): ?>
                                                                                                            <br><small class="text-success mt-1">Accepted: <?php echo date('M d, Y', strtotime($app['acceptance_date'])); ?></small>
                                                                                                        <?php endif; ?>
                                                                                                    </div>
                                                                                                    <div class="col-md-3">
                                                                                                        <?php if ($app['status'] == 'accepted'): ?>
                                                                                                            <div class="d-flex gap-1 flex-wrap">
                                                                                                                <?php if ($app['formatted_paper_link']): ?>
                                                                                                                    <a href="<?php echo htmlspecialchars($app['formatted_paper_link']); ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Formatted Paper">
                                                                                                                        <i class="bx bx-file"></i>
                                                                                                                    </a>
                                                                                                                <?php endif; ?>
                                                                                                                <?php if ($app['presentation_link']): ?>
                                                                                                                    <a href="<?php echo htmlspecialchars($app['presentation_link']); ?>" target="_blank" class="btn btn-sm btn-outline-warning" title="Presentation">
                                                                                                                        <i class="bx bx-slideshow"></i>
                                                                                                                    </a>
                                                                                                                <?php endif; ?>
                                                                                                                <?php if (isset($app['attended']) && $app['attended'] === 1): ?>
                                                                                                                    <span class="badge bg-success" title="Conference Attended">
                                                                                                                        <i class="bx bx-check"></i> Attended
                                                                                                                    </span>
                                                                                                                <?php elseif (isset($app['attended']) && $app['attended'] === 0): ?>
                                                                                                                    <span class="badge bg-warning" title="Conference Not Attended">
                                                                                                                        <i class="bx bx-x"></i> Not Attended
                                                                                                                    </span>
                                                                                                                <?php endif; ?>
                                                                                                                <?php if (isset($app['certificate_received']) && $app['certificate_received'] === 1): ?>
                                                                                                                    <span class="badge bg-primary" title="Certificate Received">
                                                                                                                        <i class="bx bx-award"></i> Certificate
                                                                                                                    </span>
                                                                                                                <?php elseif (isset($app['certificate_received']) && $app['certificate_received'] === 0): ?>
                                                                                                                    <span class="badge bg-secondary" title="No Certificate">
                                                                                                                        <i class="bx bx-award"></i> No Certificate
                                                                                                                    </span>
                                                                                                                <?php endif; ?>
                                                                                                            </div>
                                                                                                        <?php endif; ?>
                                                                                                    </div>
                                                                                                    <div class="col-md-2 text-end">
                                                                                                        <button class="btn btn-sm btn-outline-primary update-status-btn" 
                                                                                                               data-bs-toggle="modal" 
                                                                                                               data-bs-target="#updateStatusModal"
                                                                                                               data-type="conference"
                                                                                                               data-id="<?php echo $app['id']; ?>"
                                                                                                               data-status="<?php echo htmlspecialchars($app['status']); ?>"
                                                                                                               data-response-date="<?php echo htmlspecialchars($app['response_date'] ?? ''); ?>"
                                                                                                               data-feedback="<?php echo htmlspecialchars($app['feedback'] ?? ''); ?>"
                                                                                                               data-acceptance-date="<?php echo htmlspecialchars($app['acceptance_date'] ?? ''); ?>"
                                                                                                               data-reviewer-changes="<?php echo htmlspecialchars($app['reviewer_changes'] ?? ''); ?>"
                                                                                                               data-formatted-paper-link="<?php echo htmlspecialchars($app['formatted_paper_link'] ?? ''); ?>"
                                                                                                               data-presentation-link="<?php echo htmlspecialchars($app['presentation_link'] ?? ''); ?>"
                                                                                                               data-attended="<?php echo isset($app['attended']) ? $app['attended'] : ''; ?>"
                                                                                                               data-certificate-received="<?php echo isset($app['certificate_received']) ? $app['certificate_received'] : ''; ?>">
                                                                                                            <i class="bx bx-edit me-1"></i>Update
                                                                                                        </button>
                                                                                                    </div>
                                                                                                </div>
                                                                                                
                                                                                                <?php if ($app['status'] == 'accepted' && $app['reviewer_changes']): ?>
                                                                                                    <hr class="my-2">
                                                                                                    <div class="alert alert-info py-2 mb-0">
                                                                                                        <h6 class="alert-heading mb-1">
                                                                                                            <i class="bx bx-info-circle me-1"></i>Reviewer Changes
                                                                                                        </h6>
                                                                                                        <small><?php echo nl2br(htmlspecialchars($app['reviewer_changes'])); ?></small>
                                                                                                    </div>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            
                                                                            <?php if (!empty($journal_applications)): ?>
                                                                                <div class="mb-4">
                                                                                    <h6 class="mb-3">
                                                                                        <i class="bx bx-book-open text-info me-2"></i>Journal Applications
                                                                                    </h6>
                                                                                    <?php foreach ($journal_applications as $app): ?>
                                                                                        <div class="card mb-3">
                                                                                            <div class="card-body">
                                                                                                <div class="row align-items-center">
                                                                                                    <div class="col-md-5">
                                                                                                        <h6 class="mb-1"><?php echo htmlspecialchars($app['journal_name']); ?></h6>
                                                                                                        <small class="text-muted">
                                                                                                            <?php echo htmlspecialchars($app['publisher']); ?> • Applied: <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
                                                                                                        </small>
                                                                                                    </div>
                                                                                                    <div class="col-md-3">
                                                                                                        <?php
                                                                                                        $status_class = 'secondary';
                                                                                                        if ($app['status'] == 'accepted') $status_class = 'success';
                                                                                                        elseif ($app['status'] == 'rejected') $status_class = 'danger';
                                                                                                        elseif ($app['status'] == 'under_review') $status_class = 'warning';
                                                                                                        ?>
                                                                                                        <span class="badge bg-<?php echo $status_class; ?> fs-6"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span>
                                                                                                        <?php if ($app['manuscript_id']): ?>
                                                                                                            <br><small class="text-muted">ID: <?php echo htmlspecialchars($app['manuscript_id']); ?></small>
                                                                                                        <?php endif; ?>
                                                                                                    </div>
                                                                                                    <div class="col-md-4 text-end">
                                                                                                        <button class="btn btn-sm btn-outline-primary update-status-btn" 
                                                                                                               data-bs-toggle="modal" 
                                                                                                               data-bs-target="#updateStatusModal"
                                                                                                               data-type="journal"
                                                                                                               data-id="<?php echo $app['id']; ?>"
                                                                                                               data-status="<?php echo htmlspecialchars($app['status']); ?>"
                                                                                                               data-response-date="<?php echo htmlspecialchars($app['response_date'] ?? ''); ?>"
                                                                                                               data-feedback="<?php echo htmlspecialchars($app['feedback'] ?? ''); ?>">
                                                                                                            <i class="bx bx-edit me-1"></i>Update
                                                                                                        </button>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            
                                                                            <?php if (empty($conf_applications) && empty($journal_applications)): ?>
                                                                                <div class="text-center py-4">
                                                                                    <i class="bx bx-send display-4 text-muted"></i>
                                                                                    <h6 class="mt-2 text-muted">No Applications Yet</h6>
                                                                                    <p class="text-muted small">Use the Apply button to submit this publication to conferences or journals.</p>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Application Modals -->
                        <?php if (!empty($publications)): ?>
                            <?php foreach ($publications as $pub): ?>
                                <!-- Conference Application Modal -->
                                <div class="modal fade" id="conferenceModal<?php echo $pub['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Apply to Conference</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="in_publication_id" value="<?php echo $pub['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Conference <span class="text-danger">*</span></label>
                                                        <select class="form-select" name="conference_id" required>
                                                            <option value="">Select a conference...</option>
                                                            <?php foreach ($all_conferences as $conf): ?>
                                                                <option value="<?php echo $conf['id']; ?>">
                                                                    <?php echo htmlspecialchars($conf['conference_name']); ?>
                                                                    <?php if ($conf['conference_shortform']): ?>
                                                                        (<?php echo htmlspecialchars($conf['conference_shortform']); ?>)
                                                                    <?php endif; ?>
                                                                    - <?php echo date('M Y', strtotime($conf['conference_date'])); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Application Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="application_date" value="<?php echo date('Y-m-d'); ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Submission Deadline</label>
                                                            <input type="date" class="form-control" name="submission_deadline">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Submission Link</label>
                                                        <input type="url" class="form-control" name="submission_link" placeholder="https://...">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about this application..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="apply_conference" class="btn btn-primary">Apply to Conference</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Journal Application Modal -->
                                <div class="modal fade" id="journalModal<?php echo $pub['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Apply to Journal</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="in_publication_id" value="<?php echo $pub['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Journal <span class="text-danger">*</span></label>
                                                        <select class="form-select" name="journal_id" required>
                                                            <option value="">Select a journal...</option>
                                                            <?php foreach ($all_journals as $journal): ?>
                                                                <option value="<?php echo $journal['id']; ?>">
                                                                    <?php echo htmlspecialchars($journal['journal_name']); ?>
                                                                    - <?php echo htmlspecialchars($journal['publisher']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Application Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="application_date" value="<?php echo date('Y-m-d'); ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Submission Deadline</label>
                                                            <input type="date" class="form-control" name="submission_deadline">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Manuscript ID</label>
                                                        <input type="text" class="form-control" name="manuscript_id" placeholder="Journal manuscript tracking ID">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Submission Link</label>
                                                        <input type="url" class="form-control" name="submission_link" placeholder="https://...">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about this application..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="apply_journal" class="btn btn-primary">Apply to Journal</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Application Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="modal_application_id" name="application_id">
                        <input type="hidden" id="modal_application_type" name="application_type">
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="modal_status" name="status" required onchange="toggleAcceptanceFields()">
                                <option value="applied">Applied</option>
                                <option value="under_review">Under Review</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                                <option value="withdrawn">Withdrawn</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Response Date</label>
                            <input type="date" class="form-control" id="modal_response_date" name="response_date">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Feedback</label>
                            <textarea class="form-control" id="modal_feedback" name="feedback" rows="3" placeholder="Enter reviewer feedback or notes..."></textarea>
                        </div>
                        
                        <!-- Conference Acceptance Fields (only shown when status is accepted and type is conference) -->
                        <div id="acceptance_fields" style="display: none;">
                            <hr class="my-3">
                            <h6 class="text-success mb-3">
                                <i class="bx bx-check-circle me-1"></i>Conference Acceptance Details
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Acceptance</label>
                                    <input type="date" class="form-control" id="modal_acceptance_date" name="acceptance_date">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reviewer Changes</label>
                                    <textarea class="form-control" id="modal_reviewer_changes" name="reviewer_changes" rows="2" placeholder="Required changes from reviewers..."></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Formatted Paper Link</label>
                                    <input type="url" class="form-control" id="modal_formatted_paper_link" name="formatted_paper_link" placeholder="https://...">
                                    <small class="text-muted">Link to the formatted/final version of the paper</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Presentation Link</label>
                                    <input type="url" class="form-control" id="modal_presentation_link" name="presentation_link" placeholder="https://...">
                                    <small class="text-muted">Link to the PowerPoint presentation</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Conference Attended</label>
                                    <select class="form-select" id="modal_attended" name="attended">
                                        <option value="">Not specified</option>
                                        <option value="1">Yes, attended</option>
                                        <option value="0">No, did not attend</option>
                                    </select>
                                    <small class="text-muted">Did you attend the conference?</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Certificate Received</label>
                                    <select class="form-select" id="modal_certificate_received" name="certificate_received">
                                        <option value="">Not specified</option>
                                        <option value="1">Yes, received</option>
                                        <option value="0">No, not received</option>
                                    </select>
                                    <small class="text-muted">Did you receive the certificate?</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Function to toggle details row
        function toggleDetails(publicationId) {
            const detailsRow = document.getElementById('details-' + publicationId);
            if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
                // Hide all other details rows first
                document.querySelectorAll('.details-row').forEach(row => {
                    row.style.display = 'none';
                });
                detailsRow.style.display = 'table-row';
            } else {
                detailsRow.style.display = 'none';
            }
        }

        // Function to toggle acceptance fields visibility
        function toggleAcceptanceFields() {
            const status = document.getElementById('modal_status').value;
            const applicationType = document.getElementById('modal_application_type').value;
            const acceptanceFields = document.getElementById('acceptance_fields');
            
            // Show acceptance fields only for conferences with accepted status
            if (status === 'accepted' && applicationType === 'conference') {
                acceptanceFields.style.display = 'block';
            } else {
                acceptanceFields.style.display = 'none';
            }
        }

        // Function to set status update modal data
        function setStatusUpdate(type, applicationId, currentStatus) {
            document.getElementById('modal_application_id').value = applicationId;
            document.getElementById('modal_application_type').value = type;
            document.getElementById('modal_status').value = currentStatus;
            
            // Clear all fields
            document.getElementById('modal_response_date').value = '';
            document.getElementById('modal_feedback').value = '';
            document.getElementById('modal_acceptance_date').value = '';
            document.getElementById('modal_reviewer_changes').value = '';
            document.getElementById('modal_formatted_paper_link').value = '';
            document.getElementById('modal_presentation_link').value = '';
            document.getElementById('modal_attended').value = '';
            document.getElementById('modal_certificate_received').value = '';
            
            // Show/hide acceptance fields based on current status and type
            toggleAcceptanceFields();
        }

        // Event handler for update status buttons using data attributes
        document.addEventListener('DOMContentLoaded', function() {
            // Handle update status button clicks
            document.addEventListener('click', function(e) {
                if (e.target.closest('.update-status-btn')) {
                    const btn = e.target.closest('.update-status-btn');
                    const data = btn.dataset;
                    
                    // Set modal fields
                    document.getElementById('modal_application_id').value = data.id;
                    document.getElementById('modal_application_type').value = data.type;
                    document.getElementById('modal_status').value = data.status;
                    document.getElementById('modal_response_date').value = data.responseDate || '';
                    document.getElementById('modal_feedback').value = data.feedback || '';
                    
                    // Conference-specific fields
                    if (data.type === 'conference') {
                        document.getElementById('modal_acceptance_date').value = data.acceptanceDate || '';
                        document.getElementById('modal_reviewer_changes').value = data.reviewerChanges || '';
                        document.getElementById('modal_formatted_paper_link').value = data.formattedPaperLink || '';
                        document.getElementById('modal_presentation_link').value = data.presentationLink || '';
                        document.getElementById('modal_attended').value = data.attended || '';
                        document.getElementById('modal_certificate_received').value = data.certificateReceived || '';
                    } else {
                        // Clear conference-specific fields for journals
                        document.getElementById('modal_acceptance_date').value = '';
                        document.getElementById('modal_reviewer_changes').value = '';
                        document.getElementById('modal_formatted_paper_link').value = '';
                        document.getElementById('modal_presentation_link').value = '';
                        document.getElementById('modal_attended').value = '';
                        document.getElementById('modal_certificate_received').value = '';
                    }
                    
                    // Show/hide acceptance fields based on current status and type
                    toggleAcceptanceFields();
                }
            });
        });

        // Add hover effect to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr:not(.details-row)');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.classList.add('table-active');
                });
                row.addEventListener('mouseleave', function() {
                    this.classList.remove('table-active');
                });
            });
        });
    </script>
</body>

</html> 