<?php
require_once 'session_helper.php';
$msg = '';
$error = '';
$invoice_data = null;

// Simulate numeric request query parameter
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 1001;

// VULNERABLE: Direct reference validation flaw.
// The backend fetches the requested invoice based strictly on the user-controlled 'id' parameter.
// It fails to check if the 'user_id' associated with the invoice belongs to the current logged-in user (Alice - ID 1).
if (isset($_SESSION['idor_db']['invoices'][$invoice_id])) {
    $invoice_data = $_SESSION['idor_db']['invoices'][$invoice_id];
} else {
    $error = "Error: Invoice #" . htmlspecialchars($invoice_id) . " not found in the database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 1: Low | IDOR Lab</title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; color: #f1f5f9; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
        .box { background: #1e293b; padding: 30px; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-top: 5px solid #10b981; }
        h1 { color: #10b981; margin-top: 0; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #f43f5e; font-family: monospace; }
        pre { background: #0f172a; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #334155; }
        .btn { display: inline-block; background-color: #10b981; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; border: none; cursor: pointer; }
        .btn:hover { background-color: #059669; }
        a.back { color: #94a3b8; text-decoration: none; display: inline-block; margin-top: 20px; }
        a.back:hover { color: #f1f5f9; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; }
        .invoice-card { background: #0f172a; border-radius: 8px; padding: 20px; border: 1px dashed #334155; margin-top: 20px; }
        .invoice-field { margin-bottom: 10px; font-size: 0.95rem; }
        .invoice-label { color: #94a3b8; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 1: Low — Numeric Parameter IDOR</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <p>The application allows users to retrieve their billing invoices. When a user requests an invoice, the server fetches the record from the database based strictly on the <code>id</code> parameter. However, it fails to perform an authorization check to verify if the requested invoice belongs to the logged-in user.</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$invoice_id = intval($_GET['id']);
// VULNERABLE: Fetches invoice directly without verifying owner (user_id)!
$invoice = $db->query("SELECT * FROM invoices WHERE id = " . $invoice_id);
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Access another tenant's private billing invoice. Your legitimate invoice ID is <code>1001</code>. Bob's private invoice ID is <code>1002</code>, and the Admin's is <code>1003</code>. Spoof the <code>id</code> query parameter in the URL or the input box below to exfiltrate Bob's invoice data!</p>

        <form action="level1.php" method="GET" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <label for="id">Invoice ID:</label><br>
            <input type="number" id="id" name="id" value="<?php echo htmlspecialchars($invoice_id); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            <input type="submit" value="Fetch Invoice" class="btn">
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
                        🎉 SUCCESS: You accessed another user's invoice via IDOR!
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
