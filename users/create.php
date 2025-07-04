<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../includes/auth.php';

requireAdmin();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error_message = '';
$success_message = '';

// Get departments and organizations for dropdowns
$departments = $user->getDepartments()->fetchAll(PDO::FETCH_ASSOC);
$organizations = $user->getOrganizations()->fetchAll(PDO::FETCH_ASSOC);

// Get RBM users for dropdown
$rbm_users = $user->getRBMUsers()->fetchAll(PDO::FETCH_ASSOC);

// Get all users for primary contact dropdown
$contact_users = $user->getAllUsersForContact()->fetchAll(PDO::FETCH_ASSOC);

if ($_POST) {
    // Validate input
    $user_type = trim($_POST['user_type']);
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($user_type) || empty($full_name) || empty($username) || empty($password)) {
        $error_message = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check if username already exists
        $user->username = $username;
        if ($user->usernameExists()) {
            $error_message = "Username already exists. Please choose a different username.";
        } else {
            // Set user properties
            $user->user_type = $user_type;
            $user->full_name = $full_name;
            $user->username = $username;
            $user->password = $password;
            $user->status = 'active';

            // Set type-specific fields
            if ($user_type == 'admin') {
                $user->department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
                $user->specialization = null;
                $user->organization_id = null;$user->mou_signed = false;
                $user->mou_drive_link = null;
                $user->contact_no = null;
                $user->email_id = null;
                $user->address = null;
                $user->primary_contact_id = null;
                $user->councillor_rbm_id = null;
                $user->branch = null;
            } elseif ($user_type == 'mentor') {
                $user->department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
                // Handle specializations array from multi-select
                $specializations = isset($_POST['specializations']) && is_array($_POST['specializations']) 
                    ? $_POST['specializations'] : array();
                $user->specialization = !empty($specializations) ? implode(', ', $specializations) : null;
                // Handle organization for mentors (can be existing or new)
                if (!empty($_POST['organization_id']) && $_POST['organization_id'] !== 'other') {
                    // Existing organization selected
                    $user->organization_id = intval($_POST['organization_id']);
                } elseif ($_POST['organization_id'] === 'other' && !empty($_POST['new_organization_name'])) {
                    // New organization needs to be created
                    $new_org_name = trim($_POST['new_organization_name']);
                    
                    // Check if organization already exists
                    $check_org_query = "SELECT id FROM organizations WHERE name = :name";
                    $check_org_stmt = $db->prepare($check_org_query);
                    $check_org_stmt->bindParam(':name', $new_org_name);
                    $check_org_stmt->execute();
                    
                    if ($existing_org = $check_org_stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Organization already exists, use it
                        $user->organization_id = $existing_org['id'];
                    } else {
                        // Create new organization
                        $create_org_query = "INSERT INTO organizations (name) VALUES (:name)";
                        $create_org_stmt = $db->prepare($create_org_query);
                        $create_org_stmt->bindParam(':name', $new_org_name);
                        
                        if ($create_org_stmt->execute()) {
                            $user->organization_id = $db->lastInsertId();
                        } else {
                            $error_message = "Error creating new organization. Please try again.";
                        }
                    }} else {
                    // No organization selected
                    $user->organization_id = null;}
                $user->mou_signed = false;
                $user->mou_drive_link = null;
                $user->contact_no = null;
                $user->email_id = null;
                $user->address = null;
                $user->primary_contact_id = null;
                $user->councillor_rbm_id = null;
                $user->branch = null;
            } elseif ($user_type == 'councillor') {
                $user->department_id = null;
                $user->specialization = null;
                
                // Handle organization for councillors (can be existing or new)
                if (!empty($_POST['organization_id']) && $_POST['organization_id'] !== 'other') {
                    // Existing organization selected
                    $user->organization_id = intval($_POST['organization_id']);
                } elseif ($_POST['organization_id'] === 'other' && !empty($_POST['new_organization_name'])) {
                    // New organization needs to be created
                    $new_org_name = trim($_POST['new_organization_name']);
                    
                    // Check if organization already exists
                    $check_org_query = "SELECT id FROM organizations WHERE name = :name";
                    $check_org_stmt = $db->prepare($check_org_query);
                    $check_org_stmt->bindParam(':name', $new_org_name);
                    $check_org_stmt->execute();
                    
                    if ($existing_org = $check_org_stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Organization already exists, use it
                        $user->organization_id = $existing_org['id'];
                    } else {
                        // Create new organization
                        $create_org_query = "INSERT INTO organizations (name) VALUES (:name)";
                        $create_org_stmt = $db->prepare($create_org_query);
                        $create_org_stmt->bindParam(':name', $new_org_name);
                        
                        if ($create_org_stmt->execute()) {
                            $user->organization_id = $db->lastInsertId();
                        } else {
                            $error_message = "Error creating new organization. Please try again.";
                        }
                    }
                } else {
                    // No organization selected
                    $user->organization_id = null;
                }
                
                $user->mou_signed = isset($_POST['mou_signed']) && $_POST['mou_signed'] == '1';
                $user->mou_drive_link = $user->mou_signed ? trim($_POST['mou_drive_link']) : null;
                $user->contact_no = !empty($_POST['contact_no']) ? trim($_POST['contact_no']) : null;
                $user->email_id = !empty($_POST['email_id']) ? trim($_POST['email_id']) : null;
                $user->address = !empty($_POST['address']) ? trim($_POST['address']) : null;
                $user->primary_contact_id = !empty($_POST['primary_contact_id']) ? intval($_POST['primary_contact_id']) : null;
                $user->councillor_rbm_id = !empty($_POST['councillor_rbm_id']) ? intval($_POST['councillor_rbm_id']) : null;
                $user->branch = null;
            } elseif ($user_type == 'rbm') {
                $user->department_id = null;
                $user->specialization = null;
                $user->organization_id = null;
                $user->mou_signed = false;
                $user->mou_drive_link = null;
                $user->contact_no = null;
                $user->email_id = null;
                $user->address = null;
                $user->primary_contact_id = null;
                $user->councillor_rbm_id = null;
                $user->branch = trim($_POST['branch']);
            }

            // Create user
            if ($user->create()) {
                $success_message = "User created successfully!";
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Error creating user. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Create User - Research Apps</title>
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
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">User Management /</span> Create User
                        </h4>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Create User Form -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <h5 class="card-header">Create New User</h5>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="row">
                                                <!-- User Type Selection -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="user_type" class="form-label">User Type <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="user_type" name="user_type" required onchange="toggleUserTypeFields()">
                                                        <option value="">Select User Type</option>
                                                        <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                        <option value="mentor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'mentor') ? 'selected' : ''; ?>>Mentor</option>
                                                        <option value="councillor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'councillor') ? 'selected' : ''; ?>>Councillor</option>
                                                        <option value="rbm" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'rbm') ? 'selected' : ''; ?>>RBM (Research Branch Manager)</option>
                                                    </select>
                                                </div>

                                                <!-- Full Name -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Username -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                                </div>

                                                <!-- Password -->
                                                <div class="col-md-3 mb-3">
                                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control" id="password" name="password" required>
                                                </div>

                                                <!-- Confirm Password -->
                                                <div class="col-md-3 mb-3">
                                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                </div>
                                            </div>

                                            <!-- Admin/Mentor Fields -->
                                            <div id="admin_mentor_fields" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="department_id" class="form-label">Department</label>
                                                        <select class="form-select" id="department_id" name="department_id">
                                                            <option value="">Select Department</option>
                                                            <?php foreach ($departments as $dept): ?>
                                                                <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($dept['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Mentor Only Fields -->
                                            <div id="mentor_fields" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="specialization" class="form-label">Specializations</label>
                                                        <select id="specialization-select" class="form-select" multiple placeholder="Select specializations..." name="specializations[]">
                                                            <option value="Machine Learning">Machine Learning</option>
                                                            <option value="Artificial Intelligence">Artificial Intelligence</option>
                                                            <option value="Data Science">Data Science</option>
                                                            <option value="Web Development">Web Development</option>
                                                            <option value="Mobile App Development">Mobile App Development</option>
                                                            <option value="Software Engineering">Software Engineering</option>
                                                            <option value="Database Management">Database Management</option>
                                                            <option value="Cloud Computing">Cloud Computing</option>
                                                            <option value="Cybersecurity">Cybersecurity</option>
                                                            <option value="DevOps">DevOps</option>
                                                            <option value="UI/UX Design">UI/UX Design</option>
                                                            <option value="Computer Vision">Computer Vision</option>
                                                            <option value="Natural Language Processing">Natural Language Processing</option>
                                                            <option value="Robotics">Robotics</option>
                                                            <option value="IoT">Internet of Things (IoT)</option>
                                                            <option value="Blockchain">Blockchain Technology</option>
                                                            <option value="Game Development">Game Development</option>
                                                            <option value="Virtual Reality">Virtual Reality</option>
                                                            <option value="Augmented Reality">Augmented Reality</option>
                                                            <option value="Quantum Computing">Quantum Computing</option>
                                                            <option value="Bioinformatics">Bioinformatics</option>
                                                            <option value="Health Informatics">Health Informatics</option>
                                                            <option value="Environmental Technology">Environmental Technology</option>
                                                            <option value="Renewable Energy">Renewable Energy</option>
                                                            <option value="Electronics">Electronics</option>
                                                            <option value="Signal Processing">Signal Processing</option>
                                                            <option value="Network Security">Network Security</option>
                                                            <option value="Digital Marketing">Digital Marketing</option>
                                                            <option value="Project Management">Project Management</option>
                                                            <option value="Research Methodology">Research Methodology</option>
                                                            <option value="Academic Writing">Academic Writing</option>
                                                            <option value="Statistical Analysis">Statistical Analysis</option>
                                                            <option value="Mathematics">Mathematics</option>
                                                            <option value="Physics">Physics</option>
                                                            <option value="Chemistry">Chemistry</option>
                                                            <option value="Biology">Biology</option>
                                                            <option value="Biomedical Engineering">Biomedical Engineering</option>
                                                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                                                            <option value="Electrical Engineering">Electrical Engineering</option>
                                                            <option value="Civil Engineering">Civil Engineering</option>
                                                            <option value="Environmental Science">Environmental Science</option>
                                                            <option value="Materials Science">Materials Science</option>
                                                            <option value="Nanotechnology">Nanotechnology</option>
                                                            <option value="Space Technology">Space Technology</option>
                                                            <option value="Education Technology">Education Technology</option>
                                                            <option value="Social Innovation">Social Innovation</option>
                                                            <option value="Digital Healthcare">Digital Healthcare</option>
                                                            <option value="Business Analysis">Business Analysis</option>
                                                            <option value="Finance">Finance</option>
                                                            <option value="Healthcare Management">Healthcare Management</option>
                                                            <option value="Education">Education</option>
                                                        </select>
                                                        <div class="form-text">Select your areas of expertise and specialization</div>
                                                    </div>
                                                                                                        <div class="col-md-6 mb-3">
                                                        <label for="organization_id" class="form-label">Organization</label>
                                                        <select class="form-select" id="organization_id" name="organization_id" onchange="toggleNewOrganizationField()">
                                                            <option value="">Select Organization</option>
                                                            <?php foreach ($organizations as $org): ?>
                                                                <option value="<?php echo $org['id']; ?>" <?php echo (isset($_POST['organization_id']) && $_POST['organization_id'] == $org['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($org['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                            <option value="other">+ Add New Organization</option>
                                                        </select>
                                                        <div class="form-text">Select your organization from the list or add a new one</div>
                                                    </div>
                                                </div>
                                                <div class="row" id="new_organization_field" style="display: none;">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="new_organization_name" class="form-label">New Organization Name</label>
                                                        <input type="text" class="form-control" id="new_organization_name" name="new_organization_name" placeholder="Enter new organization name">
                                                        <div class="form-text">Enter the name of the new organization to add</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Councillor Only Fields -->
                                            <div id="councillor_fields" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="organization_id_councillor" class="form-label">Organization</label>
                                                        <select class="form-select" id="organization_id_councillor" name="organization_id" onchange="toggleNewOrganizationFieldCouncillor()">
                                                            <option value="">Select Organization</option>
                                                            <?php foreach ($organizations as $org): ?>
                                                                <option value="<?php echo $org['id']; ?>" <?php echo (isset($_POST['organization_id']) && $_POST['organization_id'] == $org['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($org['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                            <option value="other">+ Add New Organization</option>
                                                        </select>
                                                        <div class="form-text">Select your organization from the list or add a new one</div>
                                                    </div>
                                                </div>
                                                <div class="row" id="new_organization_field_councillor" style="display: none;">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="new_organization_name_councillor" class="form-label">New Organization Name</label>
                                                        <input type="text" class="form-control" id="new_organization_name_councillor" name="new_organization_name" placeholder="Enter new organization name">
                                                        <div class="form-text">Enter the name of the new organization to add</div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">MOU Signed</label>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="mou_signed" name="mou_signed" value="1" <?php echo (isset($_POST['mou_signed']) && $_POST['mou_signed'] == '1') ? 'checked' : ''; ?> onchange="toggleMOULink()">
                                                            <label class="form-check-label" for="mou_signed">
                                                                MOU has been signed
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row" id="mou_link_field" style="display: none;">
                                                    <div class="col-md-12 mb-3">
                                                        <label for="mou_drive_link" class="form-label">MOU Drive Link</label>
                                                        <input type="url" class="form-control" id="mou_drive_link" name="mou_drive_link" value="<?php echo isset($_POST['mou_drive_link']) ? htmlspecialchars($_POST['mou_drive_link']) : ''; ?>" placeholder="https://drive.google.com/...">
                                                        <div class="form-text">Provide the Google Drive link to the signed MOU document</div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Additional Councillor Fields -->
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="contact_no" class="form-label">Contact No</label>
                                                        <input type="tel" class="form-control" id="contact_no" name="contact_no" value="<?php echo isset($_POST['contact_no']) ? htmlspecialchars($_POST['contact_no']) : ''; ?>" placeholder="+1234567890">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="email_id" class="form-label">Email ID</label>
                                                        <input type="email" class="form-control" id="email_id" name="email_id" value="<?php echo isset($_POST['email_id']) ? htmlspecialchars($_POST['email_id']) : ''; ?>" placeholder="councillor@example.com">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label for="address" class="form-label">Address</label>
                                                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter full address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="primary_contact_id" class="form-label">Primary Contact</label>
                                                        <select class="form-select" id="primary_contact_id" name="primary_contact_id">
                                                            <option value="">Select Primary Contact</option>
                                                            <?php foreach ($contact_users as $contact): ?>
                                                                <option value="<?php echo $contact['id']; ?>" <?php echo (isset($_POST['primary_contact_id']) && $_POST['primary_contact_id'] == $contact['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($contact['full_name']); ?> (<?php echo ucfirst($contact['user_type']); ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Select the primary contact person for this councillor</div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="councillor_rbm_id" class="form-label">Assigned RBM</label>
                                                        <select class="form-select" id="councillor_rbm_id" name="councillor_rbm_id">
                                                            <option value="">Select RBM</option>
                                                            <?php foreach ($rbm_users as $rbm): ?>
                                                                <option value="<?php echo $rbm['id']; ?>" <?php echo (isset($_POST['councillor_rbm_id']) && $_POST['councillor_rbm_id'] == $rbm['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($rbm['full_name']); ?> - <?php echo htmlspecialchars($rbm['branch']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Assign an RBM to oversee this councillor's activities</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- RBM Only Fields -->
                                            <div id="rbm_fields" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label for="branch" class="form-label">Research Branch <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="branch" name="branch" value="<?php echo isset($_POST['branch']) ? htmlspecialchars($_POST['branch']) : ''; ?>" placeholder="e.g., Computer Science Research, Biomedical Research">
                                                        <div class="form-text">Specify the research branch or department that this RBM manages</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary me-2">Create User</button>
                                                <a href="/users/list.php" class="btn btn-outline-secondary">Cancel</a>
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

    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <script src="../Apps/assets/js/main.js"></script>

    <script>
        let specializationTomSelect = null;

        function toggleUserTypeFields() {
            const userType = document.getElementById('user_type').value;
            const adminMentorFields = document.getElementById('admin_mentor_fields');
            const mentorFields = document.getElementById('mentor_fields');
            const councillorFields = document.getElementById('councillor_fields');
            const rbmFields = document.getElementById('rbm_fields');

            // Hide all fields first
            adminMentorFields.style.display = 'none';
            mentorFields.style.display = 'none';
            councillorFields.style.display = 'none';
            rbmFields.style.display = 'none';

            // Show relevant fields based on user type
            if (userType === 'admin' || userType === 'mentor') {
                adminMentorFields.style.display = 'block';
                if (userType === 'mentor') {
                    mentorFields.style.display = 'block';
                    
                    // Initialize Tom Select for specializations when mentor fields are shown
                    if (!specializationTomSelect && document.getElementById('specialization-select')) {
                        setTimeout(() => {
                            specializationTomSelect = new TomSelect("#specialization-select", {
                                plugins: {
                                    remove_button: {
                                        title: "Remove this specialization",
                                    }
                                },
                                allowEmptyOption: false,
                                placeholder: "Select specializations...",
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
                        }, 100);
                    }
                }
            } else if (userType === 'councillor') {
                councillorFields.style.display = 'block';
                toggleMOULink(); // Check MOU checkbox state
            } else if (userType === 'rbm') {
                rbmFields.style.display = 'block';
            }
        }

        function toggleMOULink() {
            const mouSigned = document.getElementById('mou_signed').checked;
            const mouLinkField = document.getElementById('mou_link_field');
            
            if (mouSigned) {
                mouLinkField.style.display = 'block';
            } else {
                mouLinkField.style.display = 'none';
                document.getElementById('mou_drive_link').value = '';
            }
        }

        function toggleNewOrganizationField() {
            const organizationSelect = document.getElementById('organization_id');
            const newOrgField = document.getElementById('new_organization_field');
            
            if (organizationSelect.value === 'other') {
                newOrgField.style.display = 'block';
                document.getElementById('new_organization_name').required = true;
            } else {
                newOrgField.style.display = 'none';
                document.getElementById('new_organization_name').required = false;
                document.getElementById('new_organization_name').value = '';
            }
        }

        function toggleNewOrganizationFieldCouncillor() {
            const organizationSelect = document.getElementById('organization_id_councillor');
            const newOrgField = document.getElementById('new_organization_field_councillor');
            
            if (organizationSelect.value === 'other') {
                newOrgField.style.display = 'block';
                document.getElementById('new_organization_name_councillor').required = true;
            } else {
                newOrgField.style.display = 'none';
                document.getElementById('new_organization_name_councillor').required = false;
                document.getElementById('new_organization_name_councillor').value = '';
            }
        }

        // Initialize form based on current selection
        document.addEventListener('DOMContentLoaded', function() {
            toggleUserTypeFields();
            <?php if (isset($_POST['mou_signed']) && $_POST['mou_signed'] == '1'): ?>
            toggleMOULink();
            <?php endif; ?>
        });
    </script>
</body>
</html>