<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Conference.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$conference = new Conference();
$message = '';
$error = '';

// Handle add new conference
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_conference'])) {
    try {
        $data = [
            'conference_name' => trim($_POST['conference_name']),
            'conference_shortform' => trim($_POST['conference_shortform']),
            'conference_link' => trim($_POST['conference_link']),
            'affiliation' => $_POST['affiliation'],
            'conference_type' => $_POST['conference_type'],
            'conference_date' => $_POST['conference_date'],
            'submission_due_date' => !empty($_POST['submission_due_date']) ? $_POST['submission_due_date'] : null,
            'created_by' => $_SESSION['user_id']
        ];
        
        $conference_id = $conference->create($data);
        if ($conference_id) {
            $message = "Conference added successfully!";
        } else {
            $error = "Error creating conference. Please try again.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle update conference
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_conference'])) {
    try {
        $conference_id = intval($_POST['conference_id']);
        $data = [
            'conference_name' => trim($_POST['conference_name']),
            'conference_shortform' => trim($_POST['conference_shortform']),
            'conference_link' => trim($_POST['conference_link']),
            'affiliation' => $_POST['affiliation'],
            'conference_type' => $_POST['conference_type'],
            'conference_date' => $_POST['conference_date'],
            'submission_due_date' => !empty($_POST['submission_due_date']) ? $_POST['submission_due_date'] : null
        ];
        
        if ($conference->update($conference_id, $data)) {
            $message = "Conference updated successfully!";
        } else {
            $error = "Error updating conference. Please try again.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle delete conference
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && hasPermission('admin')) {
    $conference_id = intval($_GET['delete']);
    if ($conference->delete($conference_id)) {
        $message = "Conference deleted successfully!";
    } else {
        $error = "Error deleting conference. Please try again.";
    }
}

// Handle filters
$filters = [];
if (isset($_GET['affiliation']) && !empty($_GET['affiliation'])) {
    $filters['affiliation'] = $_GET['affiliation'];
}
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $filters['type'] = $_GET['type'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get data
$conferences = $conference->getAll($filters);
$upcoming_conferences = $conference->getUpcoming();
$statistics = $conference->getStatistics();

// Get conference for editing
$edit_conference = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_conference = $conference->getById(intval($_GET['edit']));
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Conference Management - Research Apps</title>
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
                                            <span class="text-muted fw-light">Research & Publications /</span> Conference Management
                                        </h5>
                                        <div>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConferenceModal">
                                                <i class="bx bx-plus me-1"></i> Add New Conference
                                            </button>
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
                                                    <i class="bx bx-calendar text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Conferences</span>
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
                                                    <i class="bx bx-time-five text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Upcoming Conferences</span>
                                        <h3 class="card-title mb-2"><?php echo $statistics['upcoming']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="avatar-initial bg-info rounded">
                                                    <i class="bx bx-world text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">International</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $international = array_filter($statistics['by_type'], function($item) { 
                                                return $item['conference_type'] == 'International'; 
                                            });
                                            echo !empty($international) ? reset($international)['count'] : 0;
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
                                                <div class="avatar-initial bg-warning rounded">
                                                    <i class="bx bx-flag text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">National</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $national = array_filter($statistics['by_type'], function($item) { 
                                                return $item['conference_type'] == 'National'; 
                                            });
                                            echo !empty($national) ? reset($national)['count'] : 0;
                                            ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Filter Conferences</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" name="search" placeholder="Conference name or shortform..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Affiliation</label>
                                        <select class="form-select" name="affiliation">
                                            <option value="">All Affiliations</option>
                                            <option value="IEEE" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'IEEE') ? 'selected' : ''; ?>>IEEE</option>
                                            <option value="Springer" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Springer') ? 'selected' : ''; ?>>Springer</option>
                                            <option value="ACM" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'ACM') ? 'selected' : ''; ?>>ACM</option>
                                            <option value="Elsevier" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Elsevier') ? 'selected' : ''; ?>>Elsevier</option>
                                            <option value="Taylor & Francis" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Taylor & Francis') ? 'selected' : ''; ?>>Taylor & Francis</option>
                                            <option value="Wiley" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Wiley') ? 'selected' : ''; ?>>Wiley</option>
                                            <option value="MDPI" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'MDPI') ? 'selected' : ''; ?>>MDPI</option>
                                            <option value="Nature" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Nature') ? 'selected' : ''; ?>>Nature</option>
                                            <option value="Oxford Academic" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Oxford Academic') ? 'selected' : ''; ?>>Oxford Academic</option>
                                            <option value="Cambridge University Press" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Cambridge University Press') ? 'selected' : ''; ?>>Cambridge University Press</option>
                                            <option value="Other" <?php echo (isset($_GET['affiliation']) && $_GET['affiliation'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="type">
                                            <option value="">All Types</option>
                                            <option value="National" <?php echo (isset($_GET['type']) && $_GET['type'] == 'National') ? 'selected' : ''; ?>>National</option>
                                            <option value="International" <?php echo (isset($_GET['type']) && $_GET['type'] == 'International') ? 'selected' : ''; ?>>International</option>
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

                        <!-- Conferences List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Conferences List</h5>
                                <span class="badge bg-primary"><?php echo count($conferences); ?> conferences found</span>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Conference Details</th>
                                            <th>Affiliation</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Submission Due</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if (empty($conferences)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="bx bx-calendar-x display-4 text-muted"></i>
                                                        <h5 class="mt-2">No conferences found</h5>
                                                        <p class="text-muted">Start by adding a new conference.</p>
                                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConferenceModal">
                                                            <i class="bx bx-plus me-1"></i> Add New Conference
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($conferences as $conf): 
                                                $is_upcoming = strtotime($conf['conference_date']) >= strtotime('today');
                                                $is_past = strtotime($conf['conference_date']) < strtotime('today');
                                                
                                                // Affiliation badge color
                                                $affiliation_class = 'bg-secondary';
                                                if ($conf['affiliation'] == 'IEEE') $affiliation_class = 'bg-primary';
                                                elseif ($conf['affiliation'] == 'Springer') $affiliation_class = 'bg-success';
                                                elseif ($conf['affiliation'] == 'ACM') $affiliation_class = 'bg-info';
                                                elseif ($conf['affiliation'] == 'Elsevier') $affiliation_class = 'bg-warning';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar flex-shrink-0 me-3">
                                                                <div class="avatar-initial bg-label-primary rounded">
                                                                    <i class="bx bx-calendar"></i>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($conf['conference_name']); ?></strong>
                                                                <?php if ($conf['conference_shortform']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($conf['conference_shortform']); ?></small>
                                                                <?php endif; ?>
                                                                <?php if ($conf['conference_link']): ?>
                                                                    <br><a href="<?php echo htmlspecialchars($conf['conference_link']); ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                                                                        <i class="bx bx-link-external me-1"></i>Visit Website
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $affiliation_class; ?>">
                                                            <?php echo htmlspecialchars($conf['affiliation']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $conf['conference_type'] == 'International' ? 'bg-info' : 'bg-warning'; ?>">
                                                            <i class="bx <?php echo $conf['conference_type'] == 'International' ? 'bx-world' : 'bx-flag'; ?> me-1"></i>
                                                            <?php echo htmlspecialchars($conf['conference_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo date('M d, Y', strtotime($conf['conference_date'])); ?></strong>
                                                        <br><small class="text-muted"><?php echo date('l', strtotime($conf['conference_date'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($conf['submission_due_date']): ?>
                                                            <strong><?php echo date('M d, Y', strtotime($conf['submission_due_date'])); ?></strong>
                                                            <br>
                                                            <?php 
                                                            $due_date = strtotime($conf['submission_due_date']);
                                                            $today = strtotime('today');
                                                            $days_diff = floor(($due_date - $today) / (60 * 60 * 24));
                                                            
                                                            if ($days_diff > 0): ?>
                                                                <small class="text-success">
                                                                    <i class="bx bx-time me-1"></i><?php echo $days_diff; ?> days left
                                                                </small>
                                                            <?php elseif ($days_diff == 0): ?>
                                                                <small class="text-warning">
                                                                    <i class="bx bx-alarm me-1"></i>Due today
                                                                </small>
                                                            <?php else: ?>
                                                                <small class="text-danger">
                                                                    <i class="bx bx-x me-1"></i>Overdue
                                                                </small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">
                                                                <i class="bx bx-minus me-1"></i>Not set
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($is_upcoming): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bx bx-time-five me-1"></i>Upcoming
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="bx bx-check me-1"></i>Past
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="?edit=<?php echo $conf['id']; ?>">
                                                                    <i class="bx bx-edit me-1"></i> Edit Conference
                                                                </a>
                                                                <div class="dropdown-divider"></div>
                                                                <?php if (hasPermission('admin')): ?>
                                                                <a class="dropdown-item text-danger" href="?delete=<?php echo $conf['id']; ?>" onclick="return confirm('Are you sure you want to delete this conference?')">
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

    <!-- Add Conference Modal -->
    <div class="modal fade" id="addConferenceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Conference</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Conference Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="conference_name" required 
                                       placeholder="Enter full conference name...">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Conference Shortform</label>
                                <input type="text" class="form-control" name="conference_shortform" 
                                       placeholder="e.g., ICCSE, NCAI">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Conference Link</label>
                            <input type="url" class="form-control" name="conference_link" 
                                   placeholder="https://conference-website.com">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Affiliation <span class="text-danger">*</span></label>
                                <select class="form-select" name="affiliation" required>
                                    <option value="">Select affiliation...</option>
                                    <option value="IEEE">IEEE</option>
                                    <option value="Springer">Springer</option>
                                    <option value="ACM">ACM</option>
                                    <option value="Elsevier">Elsevier</option>
                                    <option value="Taylor & Francis">Taylor & Francis</option>
                                    <option value="Wiley">Wiley</option>
                                    <option value="MDPI">MDPI</option>
                                    <option value="Nature">Nature</option>
                                    <option value="Oxford Academic">Oxford Academic</option>
                                    <option value="Cambridge University Press">Cambridge University Press</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Conference Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="conference_type" required>
                                    <option value="">Select type...</option>
                                    <option value="National">National</option>
                                    <option value="International">International</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Conference Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="conference_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Submission Due Date</label>
                                <input type="date" class="form-control" name="submission_due_date" 
                                       placeholder="Paper submission deadline">
                                <small class="text-muted">Deadline for paper submissions</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_conference" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Conference
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Conference Modal -->
    <?php if ($edit_conference): ?>
    <div class="modal fade" id="editConferenceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="conference_id" value="<?php echo $edit_conference['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Conference</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Conference Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="conference_name" required 
                                       value="<?php echo htmlspecialchars($edit_conference['conference_name']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Conference Shortform</label>
                                <input type="text" class="form-control" name="conference_shortform" 
                                       value="<?php echo htmlspecialchars($edit_conference['conference_shortform']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Conference Link</label>
                            <input type="url" class="form-control" name="conference_link" 
                                   value="<?php echo htmlspecialchars($edit_conference['conference_link']); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Affiliation <span class="text-danger">*</span></label>
                                <select class="form-select" name="affiliation" required>
                                    <option value="IEEE" <?php echo $edit_conference['affiliation'] == 'IEEE' ? 'selected' : ''; ?>>IEEE</option>
                                    <option value="Springer" <?php echo $edit_conference['affiliation'] == 'Springer' ? 'selected' : ''; ?>>Springer</option>
                                    <option value="ACM" <?php echo $edit_conference['affiliation'] == 'ACM' ? 'selected' : ''; ?>>ACM</option>
                                    <option value="Elsevier" <?php echo $edit_conference['affiliation'] == 'Elsevier' ? 'selected' : ''; ?>>Elsevier</option>
                                    <option value="Taylor & Francis" <?php echo $edit_conference['affiliation'] == 'Taylor & Francis' ? 'selected' : ''; ?>>Taylor & Francis</option>
                                    <option value="Wiley" <?php echo $edit_conference['affiliation'] == 'Wiley' ? 'selected' : ''; ?>>Wiley</option>
                                    <option value="MDPI" <?php echo $edit_conference['affiliation'] == 'MDPI' ? 'selected' : ''; ?>>MDPI</option>
                                    <option value="Nature" <?php echo $edit_conference['affiliation'] == 'Nature' ? 'selected' : ''; ?>>Nature</option>
                                    <option value="Oxford Academic" <?php echo $edit_conference['affiliation'] == 'Oxford Academic' ? 'selected' : ''; ?>>Oxford Academic</option>
                                    <option value="Cambridge University Press" <?php echo $edit_conference['affiliation'] == 'Cambridge University Press' ? 'selected' : ''; ?>>Cambridge University Press</option>
                                    <option value="Other" <?php echo $edit_conference['affiliation'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Conference Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="conference_type" required>
                                    <option value="National" <?php echo $edit_conference['conference_type'] == 'National' ? 'selected' : ''; ?>>National</option>
                                    <option value="International" <?php echo $edit_conference['conference_type'] == 'International' ? 'selected' : ''; ?>>International</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Conference Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="conference_date" required 
                                       value="<?php echo $edit_conference['conference_date']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Submission Due Date</label>
                                <input type="date" class="form-control" name="submission_due_date" 
                                       value="<?php echo htmlspecialchars($edit_conference['submission_due_date'] ?? ''); ?>">
                                <small class="text-muted">Deadline for paper submissions</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_conference" class="btn btn-success">
                            <i class="bx bx-check me-1"></i> Update Conference
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
        document.querySelectorAll('select[name="affiliation"], select[name="type"]').forEach(function(select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Show edit modal if edit parameter is present
        <?php if ($edit_conference): ?>
        window.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editConferenceModal'));
            editModal.show();
        });
        <?php endif; ?>

        // Set minimum date to today for new conferences
        document.querySelector('input[name="conference_date"]').min = new Date().toISOString().split('T')[0];
    </script>
</body>

</html> 