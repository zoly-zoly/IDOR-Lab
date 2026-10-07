<?php
require_once 'session_helper.php';
header('Content-Type: application/json');

// Helper API endpoint to dynamically retrieve database state for verified front-end rendering
$fetch_id = isset($_GET['fetch_id']) ? intval($_GET['fetch_id']) : 0;

if (isset($_SESSION['idor_db']['users'][$fetch_id])) {
    echo json_encode([
        'success' => true,
        'data' => $_SESSION['idor_db']['users'][$fetch_id],
        'is_exploited' => ($fetch_id !== 1)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Record not found.']);
}
?>