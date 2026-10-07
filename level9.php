<?php
require_once 'session_helper.php';
$msg = '';
$error = '';

// VULNERABLE: Mass Assignment / Property Injection.
// The backend takes the raw user-supplied POST parameters and merges them directly into the session user database record
// using array_merge(). It fails to restrict the keys to a strict whitelist of fields (e.g., username, email only).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        
        // VULNERABLE: Direct parameter merging! Allows overwriting administrative fields like 'role' or 'apikey' or 'id'.
        $_SESSION['idor_db']['users'][1] = array_merge($_SESSION['idor_db']['users'][1], $_POST);
        $msg = "Success: Profile updated successfully!";
    }
}

// Fetch current logged-in user details (Alice - ID 1)
$alice_profile = $_SESSION['idor_db']['users'][1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 9: Critical | IDOR Lab</title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; color: #f1f5f9; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
        .box { background: #1e293b; padding: 30px; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-top: 5px solid #8b5cf6; }
        h1 { color: #8b5cf6; margin-top: 0; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #f43f5e; font-family: monospace; }
        pre { background: #0f172a; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #334155; }
        .btn { display: inline-block; background-color: #8b5cf6; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; border: none; cursor: pointer; }
        .btn:hover { background-color: #7c3aed; }
        a.back { color: #94a3b8; text-decoration: none; display: inline-block; margin-top: 20px; }
        a.back:hover { color: #f1f5f9; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid #10b981; }
        .status-box { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px; }
        .status-val { font-weight: bold; color: #38bdf8; }
        .alert-critical { background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid #8b5cf6; padding: 15px; border-radius: 6px; font-weight: bold; text-align: center; margin-top: 20px;}
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 9: Critical — Mass Assignment / Property Injection</h1>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Your Current Session Database Record (Alice)</p>
            <div>User: <span class="status-val"><?php echo htmlspecialchars($alice_profile['username']); ?> (ID: <?php echo $alice_profile['id']; ?>)</span></div>
            <div>Email: <span class="status-val"><?php echo htmlspecialchars($alice_profile['email']); ?></span></div>
            <div>Your Account Role Privilege: <span class="status-val" style="color: #ef4444;"><?php echo strtoupper($alice_profile['role']); ?></span></div>
        </div>

        <p>Mass Assignment (or Parameter Injection) is closely related to IDOR. When a user updates their profile, the backend takes the raw associative parameters array and merges it directly into the active record. Because the developer didn't enforce a strict **Allowed Field Whitelist**, you can inject variables they never intended you to edit!</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$updated_fields = $_POST; // Takes all POST variables directly

// VULNERABLE: Blindly merges untrusted inputs into the active DB user row
$user_record = array_merge($user_record, $updated_fields);
$db->update("users", $user_record, "id = " . $user_id);
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Elevate Alice's account privilege role from <code>USER</code> to <code>ADMIN</code>. Inject a hidden parameter named <code>role</code> with value <code>admin</code> into your profile update request form! (You can do this by using browser DevTools or writing a custom POST payload).</p>

        <form action="level9.php" method="POST" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <input type="hidden" name="action" value="update_profile">
            
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($alice_profile['username']); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br><br>
            
            <label for="email">Email Address:</label><br>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($alice_profile['email']); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br><br>
            
            <!-- Hint field hidden to simulate easy bypass -->
            <div style="background: rgba(245, 158, 11, 0.1); border: 1px dashed #f59e0b; padding: 15px; border-radius: 6px; color: #facc15; font-size: 0.9rem; margin-bottom: 15px;">
                💡 <strong>Hacker Playground Hint:</strong> Un-comment or add this input field inside the form to inject the property:<br>
                <code>&lt;input type="text" name="role" value="admin" style="width:100%; ..."&gt;</code>
            </div>
            
            <!-- Challenge injection trigger (user can edit this) -->
            <label for="extra">Add Custom/Injected Field (Property Name):</label><br>
            <input type="text" id="extra_name" placeholder="E.g., role" style="width: 48%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;">
            <input type="text" id="extra_val" placeholder="E.g., admin" style="width: 48%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;"><br>
            
            <input type="submit" value="Update Profile Parameters" class="btn" onclick="injectMassAssignment(event)">
        </form>

        <?php if ($alice_profile['role'] === 'admin'): ?>
            <div class="alert-critical">
                🎉 CRITICAL SUCCESS: You successfully injected administrative properties to elevate your local account privileges to ADMIN!
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>

    <script>
        function injectMassAssignment(e) {
            e.preventDefault();
            const form = document.querySelector('form');
            const propName = document.getElementById('extra_name').value;
            const propVal = document.getElementById('extra_val').value;
            
            // Create a dynamic input element and append it to the form
            if (propName && propVal) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = propName;
                hiddenInput.value = propVal;
                form.appendChild(hiddenInput);
            }
            
            form.submit();
        }
    </script>
</body>
</html>
