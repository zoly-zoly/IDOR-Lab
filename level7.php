<?php
require_once 'session_helper.php';
$msg = '';
$error = '';
$user_data = null;

// VULNERABLE: Trusted client-supplied headers.
// The backend reads a custom HTTP header 'X-User-ID' to declare who is making the request.
// It fails to check if the caller is authorized to use this ID.
$request_headers = getallheaders();
$user_id = isset($request_headers['X-User-ID']) ? intval($request_headers['X-User-ID']) : 1;

if (isset($_SESSION['idor_db']['users'][$user_id])) {
    $user_data = $_SESSION['idor_db']['users'][$user_id];
} else {
    $error = "Error: User ID #" . htmlspecialchars($user_id) . " not found in database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 7: High | IDOR Lab</title>
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
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; }
        .status-box { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px; }
        .status-val { font-weight: bold; color: #38bdf8; }
        .user-card { background: #0f172a; border-radius: 8px; padding: 20px; border: 1px dashed #334155; margin-top: 20px; }
        .user-field { margin-bottom: 10px; font-size: 0.95rem; }
        .user-label { color: #94a3b8; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 7: High — Header-Based IDOR</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Current System Headers parsed</p>
            <div>User Identified in Request: <span class="status-val"><?php echo htmlspecialchars($user_id); ?></span></div>
            <div>Your Custom HTTP Header (parsed): <span class="status-val" style="color: #facc15;">X-User-ID: <?php echo htmlspecialchars($user_id); ?></span></div>
        </div>

        <p>In modern microservice architectures, an API Gateway sits in front of backend microservices. The gateway handles user authentication and passes the user ID to the internal microservice via custom headers (e.g., <code>X-User-ID: 1</code>). If the backend trusts this header without validation, and the gateway is misconfigured (allowing external users to inject their own headers), a critical identity spoofing IDOR is created!</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$headers = getallheaders();
// VULNERABLE: Trusts client-supplied custom header blindly
$user_id = intval($headers['X-User-ID']);
$user_profile = $db->query("SELECT * FROM users WHERE id = " . $user_id);
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Access the Admin's private user record (User ID: <code>100</code>). Send a request to this page containing the custom HTTP header <code>X-User-ID: 100</code> using your browser's console, extension (e.g. ModHeader), or the custom request box below!</p>

        <form onsubmit="sendHeaderRequest(event)" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <label for="header_val">Custom X-User-ID Header Value:</label><br>
            <input type="number" id="header_val" value="1" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            <input type="submit" value="Send Request with Custom Header" class="btn">
        </form>

        <div id="user-card" class="user-card" style="display:none;">
            <div class="user-field"><span class="user-label">Username:</span> <span id="field-username" style="color: #38bdf8; font-weight: bold;"></span></div>
            <div class="user-field"><span class="user-label">Email:</span> <span id="field-email"></span></div>
            <div class="user-field"><span class="user-label">Role Privilege:</span> <span id="field-role" style="color: #ef4444; font-weight: bold;"></span></div>
            <div class="user-field"><span class="user-label">Private Secret Note:</span> <span id="field-note" style="color: #cbd5e1; font-style: italic;"></span></div>
            
            <div id="exploit-msg" style="display:none; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; padding: 10px; border-radius: 6px; color: #34d399; margin-top: 15px; font-weight: bold; text-align: center;">
                🎉 SUCCESS: You successfully spoofed the API Gateway X-User-ID header to leak Administrative data!
            </div>
        </div>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>

    <script>
        function sendHeaderRequest(e) {
            e.preventDefault();
            const headerVal = document.getElementById('header_val').value;
            
            fetch('level7.php', {
                method: 'GET',
                headers: {
                    'X-User-ID': headerVal
                }
            })
            .then(res => res.text())
            .then(htmlStr => {
                // Parse returned HTML to extract the PHP generated user card dynamically
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlStr, 'text/html');
                
                // If there's an error displayed in the returned page, handle it
                const errorAlert = doc.querySelector('.alert-danger');
                if (errorAlert) {
                    alert(errorAlert.innerText);
                    document.getElementById('user-card').style.display = 'none';
                    return;
                }
                
                // Show updated status in current page
                const userCard = document.getElementById('user-card');
                const exploitMsg = document.getElementById('exploit-msg');
                
                // Read from parsed DOM
                const statusVals = doc.querySelectorAll('.status-val');
                if (statusVals.length > 0) {
                     document.querySelector('.status-val').innerText = statusVals[0].innerText;
                }
                
                // Retrieve user details if valid page returned
                // Since php executes on server, we can fetch the state of Bob or Admin by getting values from database directly
                // To keep client completely local and lightweight without nested api, we fetch from our local php server state via JSON-like extraction
                // We fetch Bob's or Admin's details from the session DB by sending a standard AJAX query
                fetch('level7_helper.php?fetch_id=' + headerVal)
                .then(res => res.json())
                .then(user => {
                    if (user.success) {
                        document.getElementById('field-username').innerText = user.data.username;
                        document.getElementById('field-email').innerText = user.data.email;
                        document.getElementById('field-role').innerText = user.data.role.toUpperCase();
                        document.getElementById('field-note').innerText = user.data.secret_note;
                        
                        userCard.style.display = 'block';
                        if (user.is_exploited) {
                            exploitMsg.style.display = 'block';
                        } else {
                            exploitMsg.style.display = 'none';
                        }
                    }
                });
            });
        }
    </script>
</body>
</html>
