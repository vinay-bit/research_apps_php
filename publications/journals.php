<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Journal.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$journal = new Journal();
$message = '';
$error = '';

// Handle success messages
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = "Journal updated successfully!";
} elseif (isset($_GET['added']) && $_GET['added'] == '1') {
    $message = "Journal added successfully!";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = "Journal deleted successfully!";
}

// Handle add new journal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_journal'])) {
    try {
        $data = [
            'journal_name' => trim($_POST['journal_name']),
            'publisher' => trim($_POST['publisher']),
            'journal_link' => trim($_POST['journal_link']),
            'acceptance_frequency' => $_POST['acceptance_frequency'],
            'created_by' => $_SESSION['user_id']
        ];
        
        $journal_id = $journal->create($data);
        if ($journal_id) {
            // Redirect after successful creation
            header("Location: journals.php?added=1");
            exit();
        } else {
            $error = "Error creating journal. Please try again.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle update journal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_journal'])) {
    try {
        $journal_id = intval($_POST['journal_id']);
        $data = [
            'journal_name' => trim($_POST['journal_name']),
            'publisher' => trim($_POST['publisher']),
            'journal_link' => trim($_POST['journal_link']),
            'acceptance_frequency' => $_POST['acceptance_frequency']
        ];
        
        if ($journal->update($journal_id, $data)) {
            // Redirect after successful update to prevent modal from reopening
            header("Location: journals.php?updated=1");
            exit();
        } else {
            $error = "Error updating journal. Please try again.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle delete journal
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && hasPermission('admin')) {
    $journal_id = intval($_GET['delete']);
    if ($journal->delete($journal_id)) {
        // Redirect after successful deletion
        header("Location: journals.php?deleted=1");
        exit();
    } else {
        $error = "Error deleting journal. Please try again.";
    }
}

// Handle filters
$filters = [];
if (isset($_GET['publisher']) && !empty($_GET['publisher'])) {
    $filters['publisher'] = $_GET['publisher'];
}
if (isset($_GET['acceptance']) && !empty($_GET['acceptance'])) {
    $filters['acceptance'] = $_GET['acceptance'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get data
$journals = $journal->getAll($filters);
$statistics = $journal->getStatistics();

// Get journal for editing
$edit_journal = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_journal = $journal->getById(intval($_GET['edit']));
}

// Define standard publishers
$standard_publishers = [
    'IEEE', 'Springer', 'Elsevier', 'ACM', 'Wiley', 'Taylor & Francis', 
    'MDPI', 'Nature Publishing Group', 'Oxford University Press', 
    'Cambridge University Press', 'MIT Press', 'American Association for the Advancement of Science', 
    'Other'
];
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Journal Management - Research Apps</title>
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
                                            <span class="text-muted fw-light">Research & Publications /</span> Journal Management
                                        </h5>
                                        <div>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJournalModal">
                                                <i class="bx bx-plus me-1"></i> Add New Journal
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
                                                    <i class="bx bx-book text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Journals</span>
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
                                                    <i class="bx bx-refresh text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Rolling Acceptance</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $rolling = array_filter($statistics['by_acceptance'], function($item) { 
                                                return $item['acceptance_frequency'] == 'Rolling'; 
                                            });
                                            echo !empty($rolling) ? reset($rolling)['count'] : 0;
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
                                                    <i class="bx bx-calendar text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Monthly</span>
                                        <h3 class="card-title mb-2">
                                            <?php 
                                            $monthly = array_filter($statistics['by_acceptance'], function($item) { 
                                                return $item['acceptance_frequency'] == 'Monthly'; 
                                            });
                                            echo !empty($monthly) ? reset($monthly)['count'] : 0;
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
                                                    <i class="bx bx-book-open text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Top Publisher</span>
                                        <h3 class="card-title mb-2" style="font-size: 1rem;">
                                            <?php 
                                            echo !empty($statistics['by_publisher']) ? htmlspecialchars($statistics['by_publisher'][0]['publisher']) : 'N/A';
                                            ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Filter Journals</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" name="search" placeholder="Journal name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Publisher</label>
                                        <select class="form-select" name="publisher">
                                            <option value="">All Publishers</option>
                                            <?php foreach ($standard_publishers as $pub): ?>
                                                <option value="<?php echo $pub; ?>" <?php echo (isset($_GET['publisher']) && $_GET['publisher'] == $pub) ? 'selected' : ''; ?>><?php echo $pub; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Acceptance Frequency</label>
                                        <select class="form-select" name="acceptance">
                                            <option value="">All Frequencies</option>
                                            <option value="Rolling" <?php echo (isset($_GET['acceptance']) && $_GET['acceptance'] == 'Rolling') ? 'selected' : ''; ?>>Rolling</option>
                                            <option value="Monthly" <?php echo (isset($_GET['acceptance']) && $_GET['acceptance'] == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                                            <option value="Quarterly" <?php echo (isset($_GET['acceptance']) && $_GET['acceptance'] == 'Quarterly') ? 'selected' : ''; ?>>Quarterly</option>
                                            <option value="Semi-annually" <?php echo (isset($_GET['acceptance']) && $_GET['acceptance'] == 'Semi-annually') ? 'selected' : ''; ?>>Semi-annually</option>
                                            <option value="Yearly" <?php echo (isset($_GET['acceptance']) && $_GET['acceptance'] == 'Yearly') ? 'selected' : ''; ?>>Yearly</option>
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

                        <!-- Journals List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Journals List</h5>
                                <span class="badge bg-primary"><?php echo count($journals); ?> journals found</span>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Journal Details</th>
                                            <th>Publisher</th>
                                            <th>Acceptance Frequency</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if (empty($journals)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="bx bx-book-open display-4 text-muted"></i>
                                                        <h5 class="mt-2">No journals found</h5>
                                                        <p class="text-muted">Start by adding a new journal.</p>
                                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJournalModal">
                                                            <i class="bx bx-plus me-1"></i> Add New Journal
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($journals as $jour): 
                                                // Publisher badge color
                                                $publisher_class = 'bg-secondary';
                                                if ($jour['publisher'] == 'IEEE') $publisher_class = 'bg-primary';
                                                elseif ($jour['publisher'] == 'Springer') $publisher_class = 'bg-success';
                                                elseif ($jour['publisher'] == 'Elsevier') $publisher_class = 'bg-warning';
                                                elseif ($jour['publisher'] == 'ACM') $publisher_class = 'bg-info';
                                                elseif ($jour['publisher'] == 'Nature Publishing Group') $publisher_class = 'bg-danger';
                                                
                                                // Acceptance frequency badge color
                                                $acceptance_class = 'bg-secondary';
                                                if ($jour['acceptance_frequency'] == 'Rolling') $acceptance_class = 'bg-success';
                                                elseif ($jour['acceptance_frequency'] == 'Monthly') $acceptance_class = 'bg-info';
                                                elseif ($jour['acceptance_frequency'] == 'Quarterly') $acceptance_class = 'bg-warning';
                                                elseif ($jour['acceptance_frequency'] == 'Semi-annually') $acceptance_class = 'bg-primary';
                                                elseif ($jour['acceptance_frequency'] == 'Yearly') $acceptance_class = 'bg-danger';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar flex-shrink-0 me-3">
                                                                <div class="avatar-initial bg-label-primary rounded">
                                                                    <i class="bx bx-book"></i>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($jour['journal_name']); ?></strong>
                                                                <?php if ($jour['journal_link']): ?>
                                                                    <br><a href="<?php echo htmlspecialchars($jour['journal_link']); ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                                                                        <i class="bx bx-link-external me-1"></i>Visit Journal
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $publisher_class; ?>">
                                                            <?php echo htmlspecialchars($jour['publisher']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $acceptance_class; ?>">
                                                            <i class="bx bx-time me-1"></i>
                                                            <?php echo htmlspecialchars($jour['acceptance_frequency']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="?edit=<?php echo $jour['id']; ?>">
                                                                    <i class="bx bx-edit me-1"></i> Edit Journal
                                                                </a>
                                                                <div class="dropdown-divider"></div>
                                                                <?php if (hasPermission('admin')): ?>
                                                                <a class="dropdown-item text-danger" href="?delete=<?php echo $jour['id']; ?>" onclick="return confirm('Are you sure you want to delete this journal?')">
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

    <!-- Add Journal Modal -->
    <div class="modal fade" id="addJournalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Journal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Journal Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="journal_name" required 
                                   placeholder="Enter full journal name...">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Publisher <span class="text-danger">*</span></label>
                                <select class="form-select" name="publisher" required>
                                    <option value="">Select publisher...</option>
                                    <?php foreach ($standard_publishers as $pub): ?>
                                        <option value="<?php echo $pub; ?>"><?php echo $pub; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acceptance Frequency <span class="text-danger">*</span></label>
                                <select class="form-select" name="acceptance_frequency" required>
                                    <option value="">Select frequency...</option>
                                    <option value="Rolling">Rolling</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Quarterly">Quarterly</option>
                                    <option value="Semi-annually">Semi-annually</option>
                                    <option value="Yearly">Yearly</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Journal Link</label>
                            <input type="url" class="form-control" name="journal_link" 
                                   placeholder="https://journal-website.com">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_journal" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Journal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Journal Modal -->
    <?php if ($edit_journal): ?>
    <div class="modal fade" id="editJournalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="journal_id" value="<?php echo $edit_journal['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Journal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Journal Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="journal_name" required 
                                   value="<?php echo htmlspecialchars($edit_journal['journal_name']); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Publisher <span class="text-danger">*</span></label>
                                <select class="form-select" name="publisher" required>
                                    <?php foreach ($standard_publishers as $pub): ?>
                                        <option value="<?php echo $pub; ?>" <?php echo $edit_journal['publisher'] == $pub ? 'selected' : ''; ?>><?php echo $pub; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acceptance Frequency <span class="text-danger">*</span></label>
                                <select class="form-select" name="acceptance_frequency" required>
                                    <option value="Rolling" <?php echo $edit_journal['acceptance_frequency'] == 'Rolling' ? 'selected' : ''; ?>>Rolling</option>
                                    <option value="Monthly" <?php echo $edit_journal['acceptance_frequency'] == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="Quarterly" <?php echo $edit_journal['acceptance_frequency'] == 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                    <option value="Semi-annually" <?php echo $edit_journal['acceptance_frequency'] == 'Semi-annually' ? 'selected' : ''; ?>>Semi-annually</option>
                                    <option value="Yearly" <?php echo $edit_journal['acceptance_frequency'] == 'Yearly' ? 'selected' : ''; ?>>Yearly</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Journal Link</label>
                            <input type="url" class="form-control" name="journal_link" 
                                   value="<?php echo htmlspecialchars($edit_journal['journal_link']); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_journal" class="btn btn-success">
                            <i class="bx bx-check me-1"></i> Update Journal
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
        // Auto-submit form on filter change (only for filter form, not modal forms)
        document.querySelectorAll('.card-body form select[name="publisher"], .card-body form select[name="acceptance"]').forEach(function(select) {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Show edit modal if edit parameter is present
        <?php if ($edit_journal): ?>
        window.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editJournalModal'));
            editModal.show();
        });
        <?php endif; ?>
    </script>
</body>

</html> 