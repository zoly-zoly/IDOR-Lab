<?php
// MOCK DATABASE & SESSION HELPER FOR LOCAL IDOR LAB
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set up a dynamic mock database stored inside the PHP Session (no SQL server required)
if (!isset($_SESSION['idor_db'])) {
    $_SESSION['idor_db'] = [
        'users' => [
            1 => [
                'id' => 1,
                'username' => 'alice',
                'email' => 'alice@securesite.local',
                'role' => 'user',
                'apikey' => 'API-ALICE-99a8b7c6',
                'secret_note' => 'Alice private note: Remember to buy milk.'
            ],
            2 => [
                'id' => 2,
                'username' => 'bob',
                'email' => 'bob@securesite.local',
                'role' => 'user',
                'apikey' => 'API-BOB-5e4d3c2b',
                'secret_note' => 'Bob private note: Attacker target - Keep password database backup in /backup/db.sql'
            ],
            100 => [
                'id' => 100,
                'username' => 'admin_user',
                'email' => 'admin@securesite.local',
                'role' => 'admin',
                'apikey' => 'API-ADMIN-1a2b3c4d',
                'secret_note' => 'Admin secret note: Master keys are stored in Vault-99.'
            ]
        ],
        'invoices' => [
            1001 => ['id' => 1001, 'user_id' => 1, 'item' => 'Basic Web Hosting', 'price' => '$15.00', 'status' => 'Paid'],
            1002 => ['id' => 1002, 'user_id' => 2, 'item' => 'Enterprise Security Pentest', 'price' => '$8,500.00', 'status' => 'Paid'],
            1003 => ['id' => 1003, 'user_id' => 100, 'item' => 'Administrative Consulting', 'price' => '$12,000.00', 'status' => 'Pending']
        ],
        'documents' => [
            'doc_99a8b1' => ['id' => 'doc_99a8b1', 'user_id' => 1, 'title' => 'Alice Resume', 'content' => 'Alice resume content: Junior Web Developer.'],
            'doc_5e4d3c' => ['id' => 'doc_5e4d3c', 'user_id' => 2, 'title' => 'Bob Confidential Patent', 'content' => 'Bob private patent detail: Secret zero-gravity engine designs.'],
            'doc_admin1' => ['id' => 'doc_admin1', 'user_id' => 100, 'title' => 'System Password Policy', 'content' => 'Strict administrative guidelines for local domain controls.']
        ]
    ];
}

// Current Logged-In User
$current_user_id = 1; // Alice is the logged-in victim

// Reset db to clean state
if (isset($_GET['reset_session'])) {
    unset($_SESSION['idor_db']);
    header("Location: index.php");
    exit();
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$lab_root = $base_url . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>