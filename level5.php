<?php
require_once 'session_helper.php';
$msg = '';
$error = '';
$invoice_data = null;

// Extracted variables
$final_id = isset($_GET['id']) ? intval($_GET['id']) : 1001;

// Simulate an external security middleware parsing the FIRST 'id' parameter in the query string
// E.g., validation proxy checks 'id=1001' (Alice - allowed), but PHP executes the last parameter 'id=1002' (Bob)
preg_match('/id=([^&]+)/', $_SERVER['QUERY_STRING'], $matches);
$first_id = isset($matches[1]) ? intval($matches[1]) : 1001;

// Gatekeeper check: we only allow access if the validated ID belongs to Alice (ID 1001)
if ($first_id === 1001) {
    // PASS: The security proxy verified that the ID is 1001, which belongs to Alice.
    // Execution: PHP parses the QUERY_STRING natively and populates $_GET['id'] with the LAST occurrence!
    if (isset($_SESSION['idor_db']['invoices'][$final_id])) {
        $invoice_data = $_SESSION['idor_db']['invoices'][$final_id];
    } else {
        $error = "Error: Invoice #" . htmlspecialchars($final_id) . " not found.";
    }
} else {
    $error = "Access Denied: Security gateway blocked request. You cannot access Invoice ID #" . htmlspecialchars($first_id) . "!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 5: High | IDOR Lab</title>
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
        <h1>Level 5: High — HTTP Parameter Pollution</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Current System Status</p>
            <div>User: <span class="status-val"><?php echo htmlspecialchars($_SESSION['idor_db']['users'][1]['username']); ?></span></div>
            <div>Your Legitimate Invoice ID: <span class="status-val">1001</span></div>
        </div>

        <p>In high-security infrastructures, requests pass through pre-validation gateways (WAFs, Auth Proxies) before hitting the execution backend. A parser discrepancy between these layers enables **HTTP Parameter Pollution (HPP)**: the gateway validates the first parameter, but the backend executes the last parameter.</p>
        
        <h3>Parser Discrepancy Map:</h3>
        <pre><code>URL: level5.php?id=1001&id=1002

1. Gatekeeper WAF (validates first):
   id = 1001 -> Owner is Alice -> [PASS VALIDATION]

2. PHP Backend (executes last):
   $_GET['id'] = 1002 -> Bob's Invoice -> [EXECUTED]</code></pre>

        <h3>Your Goal:</h3>
        <p>Access Bob's invoice (<code>1002</code>) or the Admin's invoice (<code>1003</code>). Force-feed multiple <code>id</code> parameters in your request query to satisfy the security proxy while pulling Bob's records on execution!</p>

        <form action="level5.php" method="GET" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <label for="id">Query URL Tester (Copy & Paste / Edit):</label><br>
            <input type="text" name="query_preview" value="level5.php?id=1001&id=1002" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #1e293b; color: #38bdf8; font-family: monospace; margin-bottom: 15px;" readonly>
            
            <input type="submit" value="Launch HPP Payload" class="btn" onclick="e => e.preventDefault(); window.location.href='level5.php?id=1001&id=1002'; return false;">
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
                        🎉 SUCCESS: You successfully exploited HTTP Parameter Pollution to bypass authentication proxy validation!
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
