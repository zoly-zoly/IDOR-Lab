<?php
require_once 'session_helper.php';
$msg = '';
$error = '';

$user_id = isset($_POST['id']) ? intval($_POST['id']) : 1;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// VULNERABLE: State-changing write operation.
// The backend permits updating the 'secret_note' parameter for whatever 'id' is supplied.
// It fails to check if the 'id' of the user record being updated is owned by the current active user (ID 1).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_note') {
    $new_note = isset($_POST['note']) ? $_POST['note'] : '';
    
    if (isset($_SESSION['idor_db']['users'][$user_id])) {
        $_SESSION['idor_db']['users'][$user_id]['secret_note'] = $new_note;
        $msg = "Success: User record successfully updated!";
    } else {
        $error = "Error: User ID #" . htmlspecialchars($user_id) . " does not exist.";
    }
}

// Fetch current logged-in user profile status (Alice - ID 1)
$alice_profile = $_SESSION['idor_db']['users'][1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 6: High | IDOR Lab</title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; color: #f1f5f9; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
        .box { background: #1e293b; padding: 30px; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-top: 5px solid #ef4444; }
        h1 { color: #ef4444; margin-top: 0; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #f43f5e; font-family: monospace; }
        pre { background: #0f172a; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #334155; }
        .btn { display: inline-block; background-color: #ef4444; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; border: none; cursor: pointer; }
        .btn:hover { background-color: #dc2626; }
        a.back { color: #94a3b8; text-decoration: none; display: inline-block; margin-top: 20px; }
        a.back:hover { color: #f1f5f9; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid #10b981; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; }
        .status-box { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px; }
        .status-val { font-weight: bold; color: #38bdf8; }
        .note-card { background: #0f172a; border-radius: 8px; padding: 15px; border: 1px dashed #334155; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 6: High — Write-Based IDOR</h1>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Current System Session (Alice)</p>
            <div>User: <span class="status-val"><?php echo htmlspecialchars($alice_profile['username']); ?> (ID: 1)</span></div>
            <div class="note-card">
                <strong>Your Current Secret Note:</strong><br>
                <span style="color: #cbd5e1; font-style: italic;"><?php echo htmlspecialchars($alice_profile['secret_note']); ?></span>
            </div>
        </div>

        <p>Insecure Direct Object Reference vulnerabilities do not only apply to reading cross-tenant data. They are equally critical on **state-changing write operations** (updates, deletes, creations). If a backend accepts a POST parameter to identify which database record to update but fails to verify if you own it, you can modify any user's profile or account details!</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$user_id = intval($_POST['id']);
$new_note = $_POST['note'];

// VULNERABLE: Direct database write update based on user-supplied 'id'
$db->query("UPDATE users SET secret_note = '{$new_note}' WHERE id = " . $user_id);
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Overoverwrite Bob's private secret note (Bob's User ID is <code>2</code>) or the Admin's secret note (User ID is <code>100</code>). Intercept and spoof the <code>id</code> form parameter below to write data directly into Bob's profile!</p>

        <form action="level6.php" method="POST" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <input type="hidden" name="action" value="update_note">
            
            <label for="id">Target User ID (Form Field):</label><br>
            <input type="number" id="id" name="id" value="1" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br><br>
            
            <label for="note">Secret Note Content:</label><br>
            <textarea id="note" name="note" rows="3" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required>Remember to buy milk.</textarea><br>
            
            <input type="submit" value="Update Secret Note" class="btn">
        </form>

        <!-- Display Bob or Admin's note if it has been updated from default -->
        <?php 
        $bob_note = $_SESSION['idor_db']['users'][2]['secret_note'];
        if ($bob_note !== 'Bob private note: Attacker target - Keep password database backup in /backup/db.sql'): 
        ?>
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; padding: 10px; border-radius: 6px; color: #34d399; margin-top: 20px; font-weight: bold; text-align: center;">
                🎉 SUCCESS: You successfully updated Bob's secret note! New Note: <br>
                <span style="color: #facc15; font-style: italic;">"<?php echo htmlspecialchars($bob_note); ?>"</span>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
