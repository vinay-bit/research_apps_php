<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/TimeSheet.php';
require_once '../classes/Project.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Check if user is a mentor
if ($_SESSION['user_type'] !== 'mentor' && $_SESSION['user_type'] !== 'admin') {
    header("Location: /dashboard.php");
    exit();
}

$timesheet = new TimeSheet();
$project = new Project();

// Get current month/year or from URL parameters
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_project = isset($_GET['project']) ? intval($_GET['project']) : null;

// Get mentor's projects
$mentor_projects = $timesheet->getMentorProjects($_SESSION['user_id']);

// Get calendar data
$calendar_data = $timesheet->getCalendarData($_SESSION['user_id'], $current_year, $current_month, $selected_project);

// Get activities for dropdown
$activities = $timesheet->getActivities();

// Get settings
$settings = $timesheet->getSettings();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_entries':
                $date = $_POST['date'];
                $entries = $timesheet->getEntriesByDate($_SESSION['user_id'], $date, $selected_project);
                echo json_encode(['success' => true, 'data' => $entries]);
                exit;
                
            case 'add_entry':
                $timesheet->project_id = $_POST['project_id'];
                $timesheet->mentor_id = $_SESSION['user_id'];
                $timesheet->entry_date = $_POST['entry_date'];
                $timesheet->start_time = $_POST['start_time'];
                $timesheet->end_time = $_POST['end_time'];
                $timesheet->activity_id = $_POST['activity_id'];
                $timesheet->task_description = $_POST['task_description'];
                $timesheet->notes = $_POST['notes'] ?? '';
                
                if ($timesheet->create()) {
                    echo json_encode(['success' => true, 'message' => 'Time entry added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add time entry']);
                }
                exit;
                
            case 'update_entry':
                $timesheet->id = $_POST['entry_id'];
                $timesheet->entry_date = $_POST['entry_date'];
                $timesheet->start_time = $_POST['start_time'];
                $timesheet->end_time = $_POST['end_time'];
                $timesheet->activity_id = $_POST['activity_id'];
                $timesheet->task_description = $_POST['task_description'];
                $timesheet->notes = $_POST['notes'] ?? '';
                
                if ($timesheet->update()) {
                    echo json_encode(['success' => true, 'message' => 'Time entry updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update time entry']);
                }
                exit;
                
            case 'delete_entry':
                $entry_id = $_POST['entry_id'];
                if ($timesheet->delete($entry_id)) {
                    echo json_encode(['success' => true, 'message' => 'Time entry deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete time entry']);
                }
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Generate calendar
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$first_day_of_week = date('w', $first_day);
$last_day = mktime(0, 0, 0, $current_month, $days_in_month, $current_year);

// Organize calendar data by date
$calendar_entries = [];
foreach ($calendar_data as $entry) {
    $date = $entry['entry_date'];
    if (!isset($calendar_entries[$date])) {
        $calendar_entries[$date] = [];
    }
    $calendar_entries[$date][] = $entry;
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../Apps/assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Time Sheet - Research Apps</title>
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
        .calendar-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .calendar-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #dee2e6;
        }
        
        .calendar-day-header {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .calendar-day:hover {
            background: #f8f9fa;
        }
        
        .calendar-day.other-month {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .calendar-day.today {
            background: #e3f2fd;
            border: 2px solid #2196f3;
        }
        
        .calendar-day.has-entries {
            background: #e8f5e8;
        }
        
        .day-number {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .day-entries {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .entry-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }
        
        .total-hours {
            font-size: 0.7rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .modal-lg {
            max-width: 800px;
        }
        
        .time-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .time-input-group input {
            flex: 1;
        }
        
        .activity-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
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
                        <!-- Header -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <span class="text-muted fw-light">Time Sheet /</span> Calendar View
                                        </h5>
                                        <div>
                                            <a href="reports.php" class="btn btn-outline-info me-2">
                                                <i class="bx bx-chart me-1"></i> Reports
                                            </a>
                                            <a href="approvals.php" class="btn btn-outline-warning me-2">
                                                <i class="bx bx-check-circle me-1"></i> Approvals
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Month/Year</label>
                                                <div class="d-flex gap-2">
                                                    <select name="month" class="form-select">
                                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                                            <option value="<?php echo $m; ?>" <?php echo $m == $current_month ? 'selected' : ''; ?>>
                                                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    <select name="year" class="form-select">
                                                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                                            <option value="<?php echo $y; ?>" <?php echo $y == $current_year ? 'selected' : ''; ?>>
                                                                <?php echo $y; ?>
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Project</label>
                                                <select name="project" class="form-select">
                                                    <option value="">All Projects</option>
                                                    <?php foreach ($mentor_projects as $proj): ?>
                                                        <option value="<?php echo $proj['id']; ?>" <?php echo $selected_project == $proj['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($proj['project_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">&nbsp;</label>
                                                <div>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bx bx-filter me-1"></i> Filter
                                                    </button>
                                                    <a href="index.php" class="btn btn-outline-secondary">
                                                        <i class="bx bx-refresh me-1"></i> Reset
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div class="row">
                            <div class="col-12">
                                <div class="calendar-container">
                                    <div class="calendar-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4 class="mb-0">
                                                <?php echo date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?>
                                            </h4>
                                            <div>
                                                <a href="?year=<?php echo $current_year; ?>&month=<?php echo $current_month - 1; ?>&project=<?php echo $selected_project; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bx bx-chevron-left"></i>
                                                </a>
                                                <a href="?year=<?php echo date('Y'); ?>&month=<?php echo date('n'); ?>&project=<?php echo $selected_project; ?>" 
                                                   class="btn btn-outline-secondary btn-sm mx-2">
                                                    Today
                                                </a>
                                                <a href="?year=<?php echo $current_year; ?>&month=<?php echo $current_month + 1; ?>&project=<?php echo $selected_project; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bx bx-chevron-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="calendar-grid">
                                        <!-- Day headers -->
                                        <div class="calendar-day-header">Sun</div>
                                        <div class="calendar-day-header">Mon</div>
                                        <div class="calendar-day-header">Tue</div>
                                        <div class="calendar-day-header">Wed</div>
                                        <div class="calendar-day-header">Thu</div>
                                        <div class="calendar-day-header">Fri</div>
                                        <div class="calendar-day-header">Sat</div>
                                        
                                        <?php
                                        // Previous month days
                                        for ($i = 0; $i < $first_day_of_week; $i++) {
                                            $prev_date = date('Y-m-d', strtotime("-" . ($first_day_of_week - $i) . " days", $first_day));
                                            echo '<div class="calendar-day other-month">';
                                            echo '<div class="day-number">' . date('j', strtotime($prev_date)) . '</div>';
                                            echo '</div>';
                                        }
                                        
                                        // Current month days
                                        for ($day = 1; $day <= $days_in_month; $day++) {
                                            $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                                            $is_today = $current_date === date('Y-m-d');
                                            $has_entries = isset($calendar_entries[$current_date]);
                                            $total_hours = $has_entries ? array_sum(array_column($calendar_entries[$current_date], 'hours_worked')) : 0;
                                            
                                            $day_classes = 'calendar-day';
                                            if ($is_today) $day_classes .= ' today';
                                            if ($has_entries) $day_classes .= ' has-entries';
                                            
                                            echo '<div class="' . $day_classes . '" data-date="' . $current_date . '">';
                                            echo '<div class="day-number">' . $day . '</div>';
                                            
                                            if ($has_entries) {
                                                echo '<div class="day-entries">';
                                                foreach (array_slice($calendar_entries[$current_date], 0, 3) as $entry) {
                                                    echo '<div class="entry-indicator" style="background-color: ' . $entry['color'] . '"></div>';
                                                    echo '<span class="text-truncate d-block">' . htmlspecialchars($entry['activity_name']) . '</span>';
                                                }
                                                if (count($calendar_entries[$current_date]) > 3) {
                                                    echo '<small class="text-muted">+' . (count($calendar_entries[$current_date]) - 3) . ' more</small>';
                                                }
                                                echo '</div>';
                                                echo '<div class="total-hours">' . number_format($total_hours, 1) . 'h</div>';
                                            }
                                            
                                            echo '</div>';
                                        }
                                        
                                        // Next month days
                                        $last_day_of_week = date('w', $last_day);
                                        for ($i = $last_day_of_week; $i < 6; $i++) {
                                            $next_date = date('Y-m-d', strtotime("+" . ($i - $last_day_of_week + 1) . " days", $last_day));
                                            echo '<div class="calendar-day other-month">';
                                            echo '<div class="day-number">' . date('j', strtotime($next_date)) . '</div>';
                                            echo '</div>';
                                        }
                                        ?>
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

        <!-- Drag Target Area To SlideDown Menu To Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Time Entry Modal -->
    <div class="modal fade" id="timeEntryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Time Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="timeEntryForm">
                    <div class="modal-body">
                        <input type="hidden" id="entry_id" name="entry_id">
                        <input type="hidden" id="entry_date" name="entry_date">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Project *</label>
                                    <select class="form-select" id="project_id" name="project_id" required>
                                        <option value="">Select Project</option>
                                        <?php foreach ($mentor_projects as $proj): ?>
                                            <option value="<?php echo $proj['id']; ?>">
                                                <?php echo htmlspecialchars($proj['project_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Activity *</label>
                                    <select class="form-select" id="activity_id" name="activity_id" required>
                                        <option value="">Select Activity</option>
                                        <?php foreach ($activities as $activity): ?>
                                            <option value="<?php echo $activity['id']; ?>" data-color="<?php echo $activity['color']; ?>">
                                                <span class="activity-color" style="background-color: <?php echo $activity['color']; ?>"></span>
                                                <?php echo htmlspecialchars($activity['activity_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time *</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Time *</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Task Description *</label>
                            <textarea class="form-control" id="task_description" name="task_description" rows="3" required 
                                      placeholder="Describe the work performed..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" 
                                      placeholder="Additional notes (optional)..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            Maximum hours per day: <strong><?php echo $settings['max_hours_per_day'] ?? 10; ?> hours</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Entries Modal -->
    <div class="modal fade" id="viewEntriesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Time Entries for <span id="modalDate"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="entriesList">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="addNewEntry">Add New Entry</button>
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
        $(document).ready(function() {
            let selectedDate = '';
            
            // Calendar day click
            $('.calendar-day:not(.other-month)').click(function() {
                selectedDate = $(this).data('date');
                $('#modalDate').text(formatDate(selectedDate));
                $('#entry_date').val(selectedDate);
                loadEntries(selectedDate);
                $('#viewEntriesModal').modal('show');
            });
            
            // Load entries for a date
            function loadEntries(date) {
                $.post('index.php', {
                    action: 'get_entries',
                    date: date
                }, function(response) {
                    if (response.success) {
                        displayEntries(response.data);
                    } else {
                        $('#entriesList').html('<div class="alert alert-danger">Failed to load entries</div>');
                    }
                }, 'json');
            }
            
            // Display entries
            function displayEntries(entries) {
                if (entries.length === 0) {
                    $('#entriesList').html('<div class="text-center text-muted">No entries for this date</div>');
                    return;
                }
                
                let html = '<div class="table-responsive"><table class="table table-hover">';
                html += '<thead><tr><th>Time</th><th>Project</th><th>Activity</th><th>Description</th><th>Hours</th><th>Actions</th></tr></thead><tbody>';
                
                entries.forEach(function(entry) {
                    html += '<tr>';
                    html += '<td>' + entry.start_time + ' - ' + entry.end_time + '</td>';
                    html += '<td>' + entry.project_name + '</td>';
                    html += '<td><span class="activity-color" style="background-color: ' + entry.color + '"></span>' + entry.activity_name + '</td>';
                    html += '<td>' + entry.task_description + '</td>';
                    html += '<td>' + parseFloat(entry.hours_worked).toFixed(1) + 'h</td>';
                    html += '<td>';
                    if (!entry.is_approved) {
                        html += '<button class="btn btn-sm btn-outline-primary edit-entry" data-id="' + entry.id + '">Edit</button> ';
                        html += '<button class="btn btn-sm btn-outline-danger delete-entry" data-id="' + entry.id + '">Delete</button>';
                    } else {
                        html += '<span class="badge bg-success">Approved</span>';
                    }
                    html += '</td></tr>';
                });
                
                html += '</tbody></table></div>';
                $('#entriesList').html(html);
            }
            
            // Add new entry from view modal
            $('#addNewEntry').click(function() {
                $('#viewEntriesModal').modal('hide');
                setTimeout(function() {
                    $('#timeEntryModal').modal('show');
                }, 300);
            });
            
            // Edit entry
            $(document).on('click', '.edit-entry', function() {
                let entryId = $(this).data('id');
                // Load entry data and populate form
                // This would require an additional AJAX call to get entry details
                alert('Edit functionality to be implemented');
            });
            
            // Delete entry
            $(document).on('click', '.delete-entry', function() {
                if (confirm('Are you sure you want to delete this entry?')) {
                    let entryId = $(this).data('id');
                    $.post('index.php', {
                        action: 'delete_entry',
                        entry_id: entryId
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }, 'json');
                }
            });
            
            // Time entry form submission
            $('#timeEntryForm').submit(function(e) {
                e.preventDefault();
                
                let formData = $(this).serialize();
                formData += '&action=add_entry';
                
                $.post('index.php', formData, function(response) {
                    if (response.success) {
                        $('#timeEntryModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json');
            });
            
            // Format date for display
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            }
            
            // Time validation
            $('#end_time').change(function() {
                let startTime = $('#start_time').val();
                let endTime = $(this).val();
                
                if (startTime && endTime && startTime >= endTime) {
                    alert('End time must be after start time');
                    $(this).val('');
                }
            });
        });
    </script>
</body>
</html> 