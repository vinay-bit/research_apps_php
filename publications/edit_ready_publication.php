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

// Get publication ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ready_for_publication.php");
    exit();
}

$publication_id = intval($_GET['id']);

// Get publication details
$publication = $readyForPublication->getById($publication_id);
if (!$publication) {
    header("Location: ready_for_publication.php");
    exit();
}

// Get students for this publication
$students = $readyForPublication->getStudentsByPublicationId($publication_id);

// Note: Conference and journal selections have been removed

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Update main publication details
        if (isset($_POST['update_publication'])) {
            $data = [
                'paper_title' => trim($_POST['paper_title']),
                'mentor_affiliation' => trim($_POST['mentor_affiliation']),
                'first_draft_link' => trim($_POST['first_draft_link']),
                'plagiarism_report_link' => trim($_POST['plagiarism_report_link']),
                'ai_detection_link' => trim($_POST['ai_detection_link']),
                'status' => $_POST['status'],
                'notes' => trim($_POST['notes'])
            ];
            
            if ($readyForPublication->update($publication_id, $data)) {
                $message = "Publication details updated successfully!";
                // Reload publication data
                $publication = $readyForPublication->getById($publication_id);
            } else {
                $error = "Error updating publication details. Please try again.";
            }
        }
        
        // Update student details
        if (isset($_POST['update_students'])) {
            $success_count = 0;
            foreach ($_POST['students'] as $student_id => $student_data) {
                $affiliation = trim($student_data['affiliation']);
                $address = trim($student_data['address']);
                
                if ($readyForPublication->updateStudentDetails($student_id, $affiliation, $address)) {
                    $success_count++;
                }
            }
            
            if ($success_count > 0) {
                $message = "Student details updated successfully for {$success_count} student(s)!";
                // Reload student data
                $students = $readyForPublication->getStudentsByPublicationId($publication_id);
            } else {
                $error = "Error updating student details. Please try again.";
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current user info
$current_user = $_SESSION;
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Edit Ready for Publication - Research Apps</title>
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
                                            <span class="text-muted fw-light">Research & Publications / Ready for Publication /</span> Edit Details
                                        </h5>
                                        <div>
                                            <a href="ready_for_publication.php" class="btn btn-secondary">
                                                <i class="bx bx-arrow-back me-1"></i> Back to List
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Publication Details -->
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Publication Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label class="form-label">Paper Title <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="paper_title" value="<?php echo htmlspecialchars($publication['paper_title']); ?>" required>
                                                </div>
                                                

                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status">
                                                        <option value="pending" <?php echo ($publication['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="in_review" <?php echo ($publication['status'] == 'in_review') ? 'selected' : ''; ?>>In Review</option>
                                                        <option value="approved" <?php echo ($publication['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                        <option value="published" <?php echo ($publication['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Mentor Affiliation</label>
                                                    <input type="text" class="form-control" name="mentor_affiliation" value="<?php echo htmlspecialchars($publication['mentor_affiliation']); ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">First Draft Link</label>
                                                    <input type="url" class="form-control" name="first_draft_link" value="<?php echo htmlspecialchars($publication['first_draft_link']); ?>" placeholder="https://drive.google.com/...">
                                                    <div class="form-text">Link to the first draft document</div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Plagiarism Report Link</label>
                                                    <input type="url" class="form-control" name="plagiarism_report_link" value="<?php echo htmlspecialchars($publication['plagiarism_report_link']); ?>" placeholder="https://drive.google.com/...">
                                                    <div class="form-text">Link to the plagiarism check report</div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">AI Detection Link <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control" name="ai_detection_link" value="<?php echo htmlspecialchars($publication['ai_detection_link']); ?>" placeholder="https://drive.google.com/...">
                                                    <div class="form-text">Link to AI detection report (required for approval)</div>
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <label class="form-label">Notes</label>
                                                    <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about this publication..."><?php echo htmlspecialchars($publication['notes']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <button type="submit" name="update_publication" class="btn btn-primary">
                                                    <i class="bx bx-save me-1"></i> Update Publication
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Student Details -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Student Details</h5>
                                        <small class="text-muted">Update student affiliations and addresses for publication</small>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($students)): ?>
                                            <form method="POST">
                                                <?php foreach ($students as $index => $student): ?>
                                                    <div class="card mb-3">
                                                        <div class="card-header">
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                                                    <div class="avatar-initial bg-label-info rounded-circle">
                                                                        <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($student['full_name']); ?></h6>
                                                                    <small class="text-muted">
                                                                        ID: <?php echo htmlspecialchars($student['student_id']); ?> | 
                                                                        Grade: <?php echo htmlspecialchars($student['grade']); ?> |
                                                                        Email: <?php echo htmlspecialchars($student['email_address']); ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Student Affiliation</label>
                                                                    <input type="text" class="form-control" 
                                                                           name="students[<?php echo $student['id']; ?>][affiliation]" 
                                                                           value="<?php echo htmlspecialchars($student['student_affiliation'] ?: $student['original_affiliation']); ?>"
                                                                           placeholder="e.g., Institution, School, Department...">
                                                                    <div class="form-text">
                                                                        Institution or school affiliation for publication
                                                                        <?php if ($student['original_affiliation'] && !$student['student_affiliation']): ?>
                                                                            <br><small class="text-info">Auto-fetched from student record: <?php echo htmlspecialchars($student['original_affiliation']); ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Student Address</label>
                                                                    <textarea class="form-control" 
                                                                              name="students[<?php echo $student['id']; ?>][address]" 
                                                                              rows="2"
                                                                              placeholder="Full address for publication..."><?php echo htmlspecialchars($student['student_address']); ?></textarea>
                                                                    <div class="form-text">Complete address for publication correspondence</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="text-end">
                                                    <button type="submit" name="update_students" class="btn btn-success">
                                                        <i class="bx bx-user-check me-1"></i> Update Student Details
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="bx bx-user-x display-4 text-muted"></i>
                                                <h5 class="mt-2">No Students Assigned</h5>
                                                <p class="text-muted">This publication has no students assigned from the original project.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Project Information Sidebar -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Project Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Project Name</label>
                                            <p class="mb-0"><?php echo htmlspecialchars($publication['project_name']); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Project Code</label>
                                            <p class="mb-0"><?php echo htmlspecialchars($publication['project_code']); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Lead Mentor</label>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs flex-shrink-0 me-2">
                                                    <div class="avatar-initial bg-label-success rounded-circle">
                                                        <?php echo $publication['mentor_name'] ? strtoupper(substr($publication['mentor_name'], 0, 2)) : 'NA'; ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="mb-0"><?php echo htmlspecialchars($publication['mentor_name'] ?? 'Not assigned'); ?></p>
                                                    <?php if ($publication['mentor_specialization']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($publication['mentor_specialization']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Project Status</label>
                                            <p class="mb-0">
                                                <span class="badge bg-info"><?php echo htmlspecialchars($publication['project_status']); ?></span>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Added to Publication List</label>
                                            <p class="mb-0"><?php echo date('M d, Y \a\t g:i A', strtotime($publication['created_at'])); ?></p>
                                        </div>
                                        <?php if ($publication['updated_at'] != $publication['created_at']): ?>
                                            <div class="mb-3">
                                                <label class="form-label text-muted">Last Updated</label>
                                                <p class="mb-0"><?php echo date('M d, Y \a\t g:i A', strtotime($publication['updated_at'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="../projects/view.php?id=<?php echo $publication['project_id']; ?>" class="btn btn-outline-primary">
                                                <i class="bx bx-show me-1"></i> View Original Project
                                            </a>
                                            <a href="ready_for_publication.php" class="btn btn-outline-secondary">
                                                <i class="bx bx-list-ul me-1"></i> Back to Publication List
                                            </a>
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