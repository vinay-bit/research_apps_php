<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Student.php';
require_once '../classes/User.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /research_apps/login.php');
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize classes
$student = new Student($db);
$user = new User($db);

// Get RBM users for dropdown
$rbm_stmt = $user->getRBMUsers();
$rbm_users = [];
while ($row = $rbm_stmt->fetch(PDO::FETCH_ASSOC)) {
    $rbm_users[] = $row;
}

// Get counselors for dropdown
$counselor_stmt = $student->getCounselors();
$counselors = [];
while ($row = $counselor_stmt->fetch(PDO::FETCH_ASSOC)) {
    $counselors[] = $row;
}

// Get boards for dropdown
$board_stmt = $student->getBoards();
$boards = [];
while ($row = $board_stmt->fetch(PDO::FETCH_ASSOC)) {
    $boards[] = $row;
}

// Handle form submission
if ($_POST) {
    // Validate required fields
    $errors = [];
    
    if (empty(trim($_POST['full_name']))) {
        $errors[] = "Student full name is required.";
    }
    
    // Handle custom board
    $board_id = null;
    if (!empty($_POST['board_id'])) {
        if ($_POST['board_id'] === 'other' && !empty($_POST['custom_board'])) {
            // Add new board
            $new_board_id = $student->addBoard(trim($_POST['custom_board']));
            if ($new_board_id) {
                $board_id = $new_board_id;
            }
        } else {
            $board_id = intval($_POST['board_id']);
        }
    }
    
    // Set student properties
    if (empty($errors)) {
        $student->full_name = trim($_POST['full_name']);
        $student->affiliation = !empty($_POST['affiliation']) ? trim($_POST['affiliation']) : null;
        $student->grade = !empty($_POST['grade']) ? trim($_POST['grade']) : null;
        $student->counselor_id = !empty($_POST['counselor_id']) ? intval($_POST['counselor_id']) : null;
        $student->rbm_id = !empty($_POST['rbm_id']) ? intval($_POST['rbm_id']) : null;
        $student->board_id = $board_id;
        $student->contact_no = !empty($_POST['contact_no']) ? trim($_POST['contact_no']) : null;
        $student->email_address = !empty($_POST['email_address']) ? trim($_POST['email_address']) : null;
        $student->application_year = !empty($_POST['application_year']) ? intval($_POST['application_year']) : null;
        
        // Validate email format if provided
        if (!empty($student->email_address) && !filter_var($student->email_address, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        // Create student if no errors
        if (empty($errors)) {
            if ($student->create()) {
                $success_message = "Student created successfully! Student ID: " . $student->student_id;
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Error creating student. Please try again.";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

$current_user = getCurrentUser();
$page_title = "Add Student";
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo $page_title; ?> - Research Apps</title>
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

    <!-- Custom OMOTEC Theme -->
    <link rel="stylesheet" href="../Apps/assets/css/omotec-theme.css" />

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
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Student Management /</span> Add Student
                        </h4>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Create Student Form -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <h5 class="card-header">Add New Student</h5>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="row">
                                                <!-- Full Name -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                                                </div>

                                                <!-- Email Address -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="email_address" class="form-label">Email Address</label>
                                                    <input type="email" class="form-control" id="email_address" name="email_address" 
                                                           value="<?php echo isset($_POST['email_address']) ? htmlspecialchars($_POST['email_address']) : ''; ?>" 
                                                           placeholder="student@example.com">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Contact No -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="contact_no" class="form-label">Contact No</label>
                                                    <input type="tel" class="form-control" id="contact_no" name="contact_no" 
                                                           value="<?php echo isset($_POST['contact_no']) ? htmlspecialchars($_POST['contact_no']) : ''; ?>" 
                                                           placeholder="+1234567890">
                                                </div>

                                                <!-- Affiliation -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="affiliation" class="form-label">Affiliation</label>
                                                    <input type="text" class="form-control" id="affiliation" name="affiliation" 
                                                           value="<?php echo isset($_POST['affiliation']) ? htmlspecialchars($_POST['affiliation']) : ''; ?>" 
                                                           placeholder="University, School, or Organization">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Grade -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="grade" class="form-label">Grade</label>
                                                    <select class="form-select" id="grade" name="grade">
                                                        <option value="">Select Grade</option>
                                                        <option value="7" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '7') ? 'selected' : ''; ?>>Grade 7</option>
                                                        <option value="8" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '8') ? 'selected' : ''; ?>>Grade 8</option>
                                                        <option value="9" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '9') ? 'selected' : ''; ?>>Grade 9</option>
                                                        <option value="10" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '10') ? 'selected' : ''; ?>>Grade 10</option>
                                                        <option value="11" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                                                        <option value="12" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                                                        <option value="undergraduate" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                                                        <option value="graduate" <?php echo (isset($_POST['grade']) && $_POST['grade'] == 'graduate') ? 'selected' : ''; ?>>Graduate</option>
                                                    </select>
                                                </div>

                                                <!-- Board -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="board_id" class="form-label">Board</label>
                                                    <select class="form-select" id="board_id" name="board_id" onchange="toggleCustomBoard()">
                                                        <option value="">Select Board</option>
                                                        <?php foreach ($boards as $board): 
                                                            $selected = (isset($_POST['board_id']) && $_POST['board_id'] == $board['id']) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?php echo $board['id']; ?>" <?php echo $selected; ?>>
                                                                <?php echo htmlspecialchars($board['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                        <option value="other" <?php echo (isset($_POST['board_id']) && $_POST['board_id'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Custom Board (hidden by default) -->
                                            <div class="row" id="custom_board_row" style="display: none;">
                                                <div class="col-md-6 mb-3">
                                                    <label for="custom_board" class="form-label">Custom Board Name</label>
                                                    <input type="text" class="form-control" id="custom_board" name="custom_board" 
                                                           value="<?php echo isset($_POST['custom_board']) ? htmlspecialchars($_POST['custom_board']) : ''; ?>"
                                                           placeholder="Enter board name">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Counselor -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="counselor_id" class="form-label">Counselor</label>
                                                    <select class="form-select" id="counselor_id" name="counselor_id">
                                                        <option value="">Select Counselor</option>
                                                        <?php foreach ($counselors as $counselor): 
                                                            $selected = (isset($_POST['counselor_id']) && $_POST['counselor_id'] == $counselor['id']) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?php echo $counselor['id']; ?>" <?php echo $selected; ?>>
                                                                <?php echo htmlspecialchars($counselor['full_name'] . ' - ' . $counselor['organization_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <!-- RBM Assignment -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="rbm_id" class="form-label">RBM (Research Branch Manager)</label>
                                                    <select class="form-select" id="rbm_id" name="rbm_id">
                                                        <option value="">Select RBM</option>
                                                        <?php foreach ($rbm_users as $rbm): ?>
                                                            <option value="<?php echo $rbm['id']; ?>" <?php echo (isset($_POST['rbm_id']) && $_POST['rbm_id'] == $rbm['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($rbm['full_name']); ?> - <?php echo htmlspecialchars($rbm['branch']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Application Year -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="application_year" class="form-label">Application Year</label>
                                                    <select class="form-select" id="application_year" name="application_year">
                                                        <option value="">Select Year</option>
                                                        <?php
                                                        $current_year = date('Y');
                                                        for ($year = $current_year; $year >= $current_year - 10; $year--) {
                                                            $selected = (isset($_POST['application_year']) && $_POST['application_year'] == $year) ? 'selected' : '';
                                                            echo "<option value=\"$year\" $selected>$year</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="alert alert-info">
                                                        <h6 class="alert-heading mb-1">Note:</h6>
                                                        <ul class="mb-0">
                                                            <li><strong>Student ID</strong> will be automatically generated (Format: STU[Year][Number])</li>
                                                            <li>Only <strong>Full Name</strong> is required - all other fields are optional</li>
                                                            <li>Students can be assigned to counselors and RBM users for management</li>
                                                            <li>If your board is not listed, select "Other" to add a custom board</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary me-2">Create Student</button>
                                                <a href="/research_apps/students/list.php" class="btn btn-outline-secondary">Cancel</a>
                                            </div>
                                        </form>
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
    <script src="../Apps/assets/js/main.js"></script>
    
    <script>
        function toggleCustomBoard() {
            const boardSelect = document.getElementById('board_id');
            const customBoardRow = document.getElementById('custom_board_row');
            const customBoardInput = document.getElementById('custom_board');
            
            if (boardSelect.value === 'other') {
                customBoardRow.style.display = 'block';
                customBoardInput.required = true;
            } else {
                customBoardRow.style.display = 'none';
                customBoardInput.required = false;
                customBoardInput.value = '';
            }
        }
        
        // Check on page load if "other" is selected
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomBoard();
        });
    </script>
</body>
</html>