<?php
require_once '../includes/auth.php';
require_once '../classes/Publication.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$publication = new Publication();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_publication'])) {
    // Set publication properties
    $publication->project_id = $_POST['project_id'];
    $publication->paper_title = $_POST['paper_title'];
    $publication->venue_type = $_POST['venue_type'];
    
    // Conference fields
    $publication->conference_acceptance_date = !empty($_POST['conference_acceptance_date']) ? $_POST['conference_acceptance_date'] : null;
    $publication->conference_reviewer_comments = $_POST['conference_reviewer_comments'];
    $publication->conference_presentation_date = !empty($_POST['conference_presentation_date']) ? $_POST['conference_presentation_date'] : null;
    $publication->conference_camera_ready_submission_date = !empty($_POST['conference_camera_ready_submission_date']) ? $_POST['conference_camera_ready_submission_date'] : null;
    $publication->conference_copyright_submission_date = !empty($_POST['conference_copyright_submission_date']) ? $_POST['conference_copyright_submission_date'] : null;
    $publication->conference_doi_link = $_POST['conference_doi_link'];
    $publication->conference_publisher = $_POST['conference_publisher'];
    
    // Journal fields  
    $publication->journal_acceptance_date = !empty($_POST['journal_acceptance_date']) ? $_POST['journal_acceptance_date'] : null;
    $publication->journal_reviewer_comments = $_POST['journal_reviewer_comments'];
    $publication->journal_link = $_POST['journal_link'];
    $publication->journal_publishing_date = !empty($_POST['journal_publishing_date']) ? $_POST['journal_publishing_date'] : null;
    $publication->journal_doi_link = $_POST['journal_doi_link'];
    $publication->journal_publisher = $_POST['journal_publisher'];
    
    if ($publication->create()) {
        // Associate students if selected
        if (!empty($_POST['student_ids'])) {
            $publication->associateStudents($_POST['student_ids']);
        }
        
        // Associate mentors if selected
        if (!empty($_POST['mentor_ids'])) {
            $publication->associateMentors($_POST['mentor_ids'], $_POST['lead_mentor_id'] ?? null);
        }
        
        $success_message = "Publication created successfully! Publication ID: " . $publication->publication_id;
        
        // Redirect to list after 2 seconds
        header("refresh:2;url=list.php");
    } else {
        $error_message = "Error creating publication. Please try again.";
    }
}

// Get projects for dropdown
$projects = $publication->getProjects();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Create Publication - Research Apps</title>
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

    <!-- Tom Select Bootstrap 5 theme CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet" />

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
                        
                        <!-- Header -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <span class="text-muted fw-light">Publication Management /</span> Create New Publication
                                        </h5>
                                        <a href="/publications/list.php" class="btn btn-secondary">
                                            <i class="bx bx-arrow-back me-1"></i> Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                                                <label class="form-label">Paper Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="paper_title" required 
                                                       placeholder="Enter the paper title">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Venue Type <span class="text-danger">*</span></label>
                                                <select class="form-select" name="venue_type" id="venue_type" required onchange="toggleVenueFields()">
                                                    <option value="">Select Venue Type</option>
                                                    <option value="Conference">Conference</option>
                                                    <option value="Journal">Journal</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                                <select class="form-select" name="project_id" id="project_id" required onchange="loadProjectData()">
                                                    <option value="">Select Project</option>
                                                    <?php while ($project = $projects->fetch(PDO::FETCH_ASSOC)): ?>
                                                        <option value="<?php echo $project['id']; ?>">
                                                            <?php echo htmlspecialchars($project['project_name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Students and Mentors -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Associated Students & Mentors</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Students</label>
                                                <select id="students-select" class="form-select" multiple placeholder="Select students..." name="student_ids[]" disabled>
                                                    <option value="">Select a project first to load students</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Mentors</label>
                                                <select id="mentors-select" class="form-select" multiple placeholder="Select mentors..." name="mentor_ids[]" disabled>
                                                    <option value="">Select a project first to load mentors</option>
                                                </select>
                                                <input type="hidden" name="lead_mentor_id" id="lead_mentor_id">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conference Fields -->
                            <div class="col-12">
                                <div class="card mb-4" id="conference-fields" style="display: none;">
                                    <div class="card-header">
                                        <h6 class="mb-0">Conference Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Acceptance Date</label>
                                                <input type="date" class="form-control" name="conference_acceptance_date">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Presentation Date</label>
                                                <input type="date" class="form-control" name="conference_presentation_date">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Camera Ready Submission Date</label>
                                                <input type="date" class="form-control" name="conference_camera_ready_submission_date">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Copyright Submission Date</label>
                                                <input type="date" class="form-control" name="conference_copyright_submission_date">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" class="form-control" name="conference_publisher" placeholder="e.g., IEEE, ACM">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">DOI Link</label>
                                                <input type="url" class="form-control" name="conference_doi_link" placeholder="https://doi.org/...">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Reviewer Comments</label>
                                                <textarea class="form-control" name="conference_reviewer_comments" rows="4" 
                                                          placeholder="Enter reviewer comments and feedback..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Journal Fields -->
                            <div class="col-12">
                                <div class="card mb-4" id="journal-fields" style="display: none;">
                                    <div class="card-header">
                                        <h6 class="mb-0">Journal Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Acceptance Date</label>
                                                <input type="date" class="form-control" name="journal_acceptance_date">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Publishing Date</label>
                                                <input type="date" class="form-control" name="journal_publishing_date">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" class="form-control" name="journal_publisher" placeholder="e.g., Elsevier, Springer">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Journal Link</label>
                                                <input type="url" class="form-control" name="journal_link" placeholder="https://journal-website.com/...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">DOI Link</label>
                                                <input type="url" class="form-control" name="journal_doi_link" placeholder="https://doi.org/...">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Reviewer Comments</label>
                                                <textarea class="form-control" name="journal_reviewer_comments" rows="4" 
                                                          placeholder="Enter reviewer comments and feedback..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <a href="/publications/list.php" class="btn btn-secondary">
                                                <i class="bx bx-x me-1"></i> Cancel
                                            </a>
                                            <button type="submit" name="create_publication" class="btn btn-primary">
                                                <i class="bx bx-check me-1"></i> Create Publication
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

    <!-- Core JS -->
    <script src="../Apps/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../Apps/assets/vendor/libs/popper/popper.js"></script>
    <script src="../Apps/assets/vendor/js/bootstrap.js"></script>
    <script src="../Apps/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../Apps/assets/vendor/js/menu.js"></script>

    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <!-- Main JS -->
    <script src="../Apps/assets/js/main.js"></script>

    <script>
        // Initialize Tom Select instances
        let studentsSelect = null;
        let mentorsSelect = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Tom Select for students
            studentsSelect = new TomSelect("#students-select", {
                plugins: {
                    remove_button: {
                        title: "Remove this student",
                    }
                },
                allowEmptyOption: false,
                placeholder: "Select students...",
                dropdownDirection: "auto",
                maxItems: null,
                onInitialize() {
                    this.control_input.addEventListener("focus", () =>
                        this.wrapper.classList.add("is-focused")
                    );
                    this.control_input.addEventListener("blur", () =>
                        this.wrapper.classList.remove("is-focused")
                    );
                }
            });

            // Initialize Tom Select for mentors
            mentorsSelect = new TomSelect("#mentors-select", {
                plugins: {
                    remove_button: {
                        title: "Remove this mentor",
                    }
                },
                allowEmptyOption: false,
                placeholder: "Select mentors...",
                dropdownDirection: "auto",
                maxItems: null,
                onInitialize() {
                    this.control_input.addEventListener("focus", () =>
                        this.wrapper.classList.add("is-focused")
                    );
                    this.control_input.addEventListener("blur", () =>
                        this.wrapper.classList.remove("is-focused")
                    );
                }
            });
        });

        function toggleVenueFields() {
            const venueType = document.getElementById('venue_type').value;
            const conferenceFields = document.getElementById('conference-fields');
            const journalFields = document.getElementById('journal-fields');
            
            if (venueType === 'Conference') {
                conferenceFields.style.display = 'block';
                journalFields.style.display = 'none';
            } else if (venueType === 'Journal') {
                conferenceFields.style.display = 'none';
                journalFields.style.display = 'block';
            } else {
                conferenceFields.style.display = 'none';
                journalFields.style.display = 'none';
            }
        }

        function loadProjectData() {
            const projectId = document.getElementById('project_id').value;
            
            if (!projectId) {
                // Clear and disable selects
                if (studentsSelect) {
                    studentsSelect.clear();
                    studentsSelect.clearOptions();
                    studentsSelect.disable();
                }
                if (mentorsSelect) {
                    mentorsSelect.clear();
                    mentorsSelect.clearOptions();
                    mentorsSelect.disable();
                }
                document.getElementById('lead_mentor_id').value = '';
                return;
            }
            
            // Load students
            fetch(`get_project_data.php?project_id=${projectId}&type=students`)
                .then(response => response.json())
                .then(data => {
                    if (studentsSelect) {
                        studentsSelect.clear();
                        studentsSelect.clearOptions();
                        
                        if (data.length > 0) {
                            data.forEach(student => {
                                studentsSelect.addOption({
                                    value: student.id,
                                    text: `${student.full_name} (${student.student_id})`
                                });
                            });
                            studentsSelect.enable();
                        } else {
                            studentsSelect.addOption({
                                value: '',
                                text: 'No students assigned to this project'
                            });
                            studentsSelect.disable();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    if (studentsSelect) {
                        studentsSelect.clear();
                        studentsSelect.clearOptions();
                        studentsSelect.addOption({
                            value: '',
                            text: 'Error loading students'
                        });
                        studentsSelect.disable();
                    }
                });
            
            // Load mentors
            fetch(`get_project_data.php?project_id=${projectId}&type=mentors`)
                .then(response => response.json())
                .then(data => {
                    if (mentorsSelect) {
                        mentorsSelect.clear();
                        mentorsSelect.clearOptions();
                        
                        if (data.length > 0) {
                            data.forEach(mentor => {
                                mentorsSelect.addOption({
                                    value: mentor.id,
                                    text: mentor.full_name
                                });
                            });
                            mentorsSelect.enable();
                            
                            // Set the first mentor as lead mentor
                            document.getElementById('lead_mentor_id').value = data[0].id;
                        } else {
                            mentorsSelect.addOption({
                                value: '',
                                text: 'No mentors assigned to this project'
                            });
                            mentorsSelect.disable();
                            document.getElementById('lead_mentor_id').value = '';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading mentors:', error);
                    if (mentorsSelect) {
                        mentorsSelect.clear();
                        mentorsSelect.clearOptions();
                        mentorsSelect.addOption({
                            value: '',
                            text: 'Error loading mentors'
                        });
                        mentorsSelect.disable();
                    }
                    document.getElementById('lead_mentor_id').value = '';
                });
        }
    </script>
</body>

</html> 