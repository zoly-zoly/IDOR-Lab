<?php
require_once 'session_helper.php';
$msg = '';
$error = '';
$backup_data = '';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$action = isset($_GET['action']) ? $_GET['action'] : 'view_key';

// VULNERABLE: Logical Priority Flaw & IDOR Chaining.
// 1. The standard endpoint 'view_key' is strictly secured:
if ($action === 'view_key') {
    if ($user_id !== $current_user_id) {
        $error = "Access Denied: You (User ID 1) are not authorized to view User ID " . htmlspecialchars($user_id) . "'s private API keys!";
    } else {
        $key_data = $_SESSION['idor_db']['users'][$current_user_id]['apikey'];
    }
} 
// 2. However, the secondary profile backup endpoint 'backup_profile' has NO authorization checks,
// allowing any user to download/dump any other user's complete account records (including API keys!).
else if ($action === 'backup_profile') {
    if (isset($_SESSION['idor_db']['users'][$user_id])) {
        $backup_data = json_encode($_SESSION['idor_db']['users'][$user_id], JSON_PRETTY_PRINT);
    } else {
        $error = "Error: User ID #" . htmlspecialchars($user_id) . " does not exist.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 10: Critical | IDOR Lab</title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; color: #f1f5f9; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
        .box { background: #1e293b; padding: 30px; border-radius: 12px; max-width: 650px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-top: 5px solid #8b5cf6; }
        h1 { color: #8b5cf6; margin-top: 0; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #f43f5e; font-family: monospace; }
        pre { background: #0f172a; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #334155; }
        .btn { display: inline-block; background-color: #8b5cf6; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; border: none; cursor: pointer; }
        .btn:hover { background-color: #7c3aed; }
        a.back { color: #94a3b8; text-decoration: none; display: inline-block; margin-top: 20px; }
        a.back:hover { color: #f1f5f9; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; }
        .status-box { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px; }
        .status-val { font-weight: bold; color: #38bdf8; }
        .result-card { background: #0f172a; border-radius: 8px; padding: 20px; border: 1px dashed #334155; margin-top: 20px; }
        .alert-critical { background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid #8b5cf6; padding: 15px; border-radius: 6px; font-weight: bold; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 10: Critical — API Key Hijacking Chain</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Current Session Status</p>
            <div>User Session ID: <span class="status-val"><?php echo $current_user_id; ?> (Alice)</span></div>
            <?php if (isset($key_data)): ?>
                <div>Your Legitimate API Key: <span class="status-val" style="color: #4ade80; font-family: monospace;"><?php echo htmlspecialchars($key_data); ?></span></div>
            <?php endif; ?>
        </div>

        <p>This is the "Final Boss" scenario showing how a secondary feature can expose primary administrative secrets. While the main endpoint (<code>action=view_key</code>) strictly validates session identity, the secondary backup endpoint (<code>action=backup_profile</code>) lacks authorization, enabling an attacker to dump any user's profile database row—including their high-privilege keys!</p>
        
        <h3>The Logic Gap:</h3>
        <pre><code>URL 1: level10.php?action=view_key&id=2 (Bob)
- Gatekeeper: Checks if 2 === $_SESSION['user_id'] (1) -> [REJECT]

URL 2: level10.php?action=backup_profile&id=2 (Bob)
- Gatekeeper: Forgot to add the ownership validation check! -> [PASS & LEAK]</code></pre>

        <h3>Your Goal:</h3>
        <p>Hijack Bob's private administrative API key (User ID: <code>2</code>). Bypass the main key viewer restrictions by triggering a backup dump on Bob's ID!</p>

        <!-- Standard viewer trigger -->
        <form action="level10.php" method="GET" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <input type="hidden" name="action" value="view_key">
            <label for="id">Query API Key (Legitimate Flow):</label><br>
            <input type="number" id="id" name="id" value="<?php echo htmlspecialchars($user_id); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            <input type="submit" value="View Profile API Key" class="btn" style="background-color: #475569;">
        </form>

        <!-- Backup trigger (exploit point) -->
        <form action="level10.php" method="GET" style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #334155;">
            <input type="hidden" name="action" value="backup_profile">
            <label for="id_backup">Backup User Profile (Audit Download Utility):</label><br>
            <input type="number" id="id_backup" name="id" value="2" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            <input type="submit" value="Dump Account Backup JSON" class="btn">
        </form>

        <?php if (!empty($backup_data)): ?>
            <div class="result-card">
                <p style="margin: 0 0 5px 0; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;">JSON Database Dump Content</p>
                <pre><code style="color: #a78bfa;"><?php echo htmlspecialchars($backup_data); ?></code></pre>
                
                <?php if ($user_id == 2): ?>
                    <div class="alert-critical">
                        🎉 SUCCESS: You hijacked Bob's administrative credentials (API-BOB-5e4d3c2b) via Chained IDOR!
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
