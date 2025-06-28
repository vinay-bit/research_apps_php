<?php
session_start();
require_once '../includes/auth.php';
require_once '../classes/Publication.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['project_id']) || !isset($_GET['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$project_id = intval($_GET['project_id']);
$type = $_GET['type'];

$publication = new Publication();

header('Content-Type: application/json');

try {
    if ($type === 'students') {
        $students = $publication->getProjectStudents($project_id);
        echo json_encode($students);
    } elseif ($type === 'mentors') {
        $mentors = $publication->getProjectMentors($project_id);
        echo json_encode($mentors);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type parameter']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?> 