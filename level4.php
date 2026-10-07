<?php
require_once 'session_helper.php';
$msg = '';
$error = '';
$status_msg = '';

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 1001;

// VULNERABLE: Blind direct reference.
// The server hides sensitive line items or owner names, but still acknowledges 
// the existence, price tier, and billing status of the requested invoice ID.
// This allows attackers to perform bulk metadata harvest / brute-force mapping.
if (isset($_SESSION['idor_db']['invoices'][$invoice_id])) {
    $invoice = $_SESSION['idor_db']['invoices'][$invoice_id];
    $status_msg = "Database Record Verified: Invoice #" . htmlspecialchars($invoice_id) . " is registered. Price: " . htmlspecialchars($invoice['price']) . " | Status: " . htmlspecialchars($invoice['status']);
} else {
    $error = "Record not found: Invoice ID #" . htmlspecialchars($invoice_id) . " does not exist.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 4: Medium | IDOR Lab</title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; color: #f1f5f9; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
        .box { background: #1e293b; padding: 30px; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-top: 5px solid #f59e0b; }
        h1 { color: #f59e0b; margin-top: 0; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #f43f5e; font-family: monospace; }
        pre { background: #0f172a; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #334155; }
        .btn { display: inline-block; background-color: #f59e0b; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; border: none; cursor: pointer; }
        .btn:hover { background-color: #d97706; }
        a.back { color: #94a3b8; text-decoration: none; display: inline-block; margin-top: 20px; }
        a.back:hover { color: #f1f5f9; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; }
        .status-box { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px; }
        .status-val { font-weight: bold; color: #38bdf8; }
        .result-card { background: #0f172a; border-radius: 8px; padding: 20px; border: 1px dashed #334155; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 4: Medium — Blind Exfiltration</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Current Victim Profile Status</p>
            <div>User: <span class="status-val"><?php echo htmlspecialchars($_SESSION['idor_db']['users'][1]['username']); ?></span></div>
            <div>Your Legitimate Invoice ID: <span class="status-val">1001</span></div>
        </div>

        <p>In many real-world systems, vulnerable endpoints do not reflect sensitive database row strings directly on the screen. Instead, they only disclose partial metadata or simple boolean answers (e.g. "Record Exists", "Status: Active"). This is called a **Blind IDOR** but is still a severe information disclosure flaw because attackers can map and harvested complete transaction records.</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$invoice_id = intval($_GET['id']);
$invoice = $db->query("SELECT status, price FROM invoices WHERE id = " . $invoice_id);

// VULNERABLE: Returns record presence and price tier info without validating access!
if ($invoice) {
    echo "Record exists. Status: " . $invoice['status'] . " Price: " . $invoice['price'];
}
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Query Bob's invoice (<code>1002</code>) or the Admin's invoice (<code>1003</code>) in the parameter below. Even though you cannot see the item name, observe how you can confirm their exact transaction pricing tiers and payment statuses!</p>

        <form action="level4.php" method="GET" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <label for="id">Invoice ID:</label><br>
            <input type="number" id="id" name="id" value="<?php echo htmlspecialchars($invoice_id); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            <input type="submit" value="Check Record Status" class="btn">
        </form>

        <?php if (!empty($status_msg)): ?>
            <div class="result-card">
                <p style="margin: 0 0 5px 0; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;">API Server Metadata Output</p>
                <div style="font-family: monospace; color: #cbd5e1;"><?php echo htmlspecialchars($status_msg); ?></div>
                
                <?php if ($invoice_id != 1001): ?>
                    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; padding: 10px; border-radius: 6px; color: #34d399; margin-top: 15px; font-weight: bold; text-align: center;">
                        🎉 SUCCESS: You successfully harvested cross-tenant billing metadata via Blind IDOR!
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
