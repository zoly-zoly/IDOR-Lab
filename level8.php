<?php
require_once 'session_helper.php';
$msg = '';
$error = '';
$invoice_data = null;

// Simulate REST nested parameters
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 1001;

// VULNERABLE: Nested REST Resource logic flaw.
// 1. First, the server checks if the parent user_id matches the logged-in user (Alice - ID 1).
// This validation succeeds!
// 2. Second, it fetches the invoice strictly based on 'invoice_id' without verifying 
// that the retrieved invoice actually belongs to the verified user_id!
if ($user_id === $current_user_id) {
    if (isset($_SESSION['idor_db']['invoices'][$invoice_id])) {
        $invoice_data = $_SESSION['idor_db']['invoices'][$invoice_id];
    } else {
        $error = "Error: Invoice #" . htmlspecialchars($invoice_id) . " not found in the database.";
    }
} else {
    $error = "Access Denied: The security module blocked access! You (User ID 1) are not authorized to access User ID " . htmlspecialchars($user_id) . "'s root resources folder!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 8: High | IDOR Lab</title>
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
        .invoice-card { background: #0f172a; border-radius: 8px; padding: 20px; border: 1px dashed #334155; margin-top: 20px; }
        .invoice-field { margin-bottom: 10px; font-size: 0.95rem; }
        .invoice-label { color: #94a3b8; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 8: High — REST Nested Resource Flaw</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Current System Status</p>
            <div>User Session ID: <span class="status-val"><?php echo $current_user_id; ?> (Alice)</span></div>
            <div>Your Legitimate Route: <span class="status-val" style="color: #cbd5e1; font-family: monospace;">level8.php?user_id=1&invoice_id=1001</span></div>
        </div>

        <p>In clean REST API routing, resources are often nested (e.g. <code>/api/users/{user_id}/invoices/{invoice_id}</code>). A common logic flaw occurs when the backend validates the parent resource owner (checking if <code>{user_id} === active_session_id</code>), but fails to verify that the target nested sub-resource (<code>{invoice_id}</code>) actually belongs to that parent user, blindly executing the query based on the sub-resource ID alone!</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$user_id = intval($_GET['user_id']);
$invoice_id = intval($_GET['invoice_id']);

// 1. Checks that you are authorized to view user_id (Alice - 1)
if ($user_id === $_SESSION['user_id']) {
    // VULNERABLE: Fetches invoice_id DIRECTLY without linking to user_id!
    $invoice = $db->query("SELECT * FROM invoices WHERE id = " . $invoice_id);
}
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Bypass the parent verification block. Keep <code>user_id=1</code> (your valid ID) to pass the gateway, but change <code>invoice_id</code> in the query to <code>1002</code> (Bob's invoice) or <code>1003</code> (Admin's invoice) to exfiltrate other users' invoices!</p>

        <form action="level8.php" method="GET" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <label for="user_id">Parent User ID:</label><br>
            <input type="number" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br><br>
            
            <label for="invoice_id">Nested Invoice ID:</label><br>
            <input type="number" id="invoice_id" name="invoice_id" value="<?php echo htmlspecialchars($invoice_id); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            
            <input type="submit" value="Request Nested Resource" class="btn">
        </form>

        <?php if ($invoice_data): ?>
            <div class="invoice-card">
                <div class="invoice-field"><span class="invoice-label">Invoice Number:</span> #<?php echo htmlspecialchars($invoice_data['id']); ?></div>
                <div class="invoice-field"><span class="invoice-label">Owner User ID:</span> <?php echo htmlspecialchars($invoice_data['user_id']); ?></div>
                <div class="invoice-field"><span class="invoice-label">Line Item:</span> <?php echo htmlspecialchars($invoice_data['item']); ?></div>
                <div class="invoice-field"><span class="invoice-label">Price Charged:</span> <span style="color: #facc15; font-weight: bold;"><?php echo htmlspecialchars($invoice_data['price']); ?></span></div>
                <div class="invoice-field"><span class="invoice-label">Status:</span> <span style="color: #4ade80; font-weight: bold;"><?php echo htmlspecialchars($invoice_data['status']); ?></span></div>
                
                <?php if ($invoice_data['user_id'] != $current_user_id): ?>
                    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; padding: 10px; border-radius: 6px; color: #34d399; margin-top: 15px; font-weight: bold; text-align: center;">
                        🎉 SUCCESS: You exploited nested resource logic to exfiltrate cross-tenant records!
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
