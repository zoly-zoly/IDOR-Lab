<?php
require_once 'session_helper.php';
$msg = '';
$error = '';

// VULNERABLE: Processes JSON POST payloads but fails to check authorization for the parsed entity ID.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($data['id'])) {
        $invoice_id = intval($data['id']);
        if (isset($_SESSION['idor_db']['invoices'][$invoice_id])) {
            $invoice = $_SESSION['idor_db']['invoices'][$invoice_id];
            
            // Output JSON response directly (replicates modern API endpoint behavior)
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $invoice,
                'is_exploited' => ($invoice['user_id'] != $current_user_id)
            ]);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => "Invoice #{$invoice_id} not found."]);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 3: Medium | IDOR Lab</title>
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
        .status-box { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px; }
        .status-val { font-weight: bold; color: #38bdf8; }
        .invoice-card { background: #0f172a; border-radius: 8px; padding: 20px; border: 1px dashed #334155; margin-top: 20px; display: none; }
        .invoice-field { margin-bottom: 10px; font-size: 0.95rem; }
        .invoice-label { color: #94a3b8; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 3: Medium — JSON Payload IDOR</h1>

        <!-- Active Session Status -->
        <div class="status-box">
            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Current Victim Profile Status</p>
            <div>User: <span class="status-val"><?php echo htmlspecialchars($_SESSION['idor_db']['users'][1]['username']); ?></span></div>
            <div>Your Legitimate Invoice ID: <span class="status-val">1001</span></div>
        </div>

        <p>Many modern web apps route data queries as POST requests transmitting structured JSON strings instead of standard GET urlencoded queries. However, parser modifications are identical: the backend validates the JSON block but fails to check authorization for the extracted entity ID.</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);
$invoice_id = intval($data['id']);

// VULNERABLE: Pulls JSON key directly without validating ownership
$invoice = $db->query("SELECT * FROM invoices WHERE id = " . $invoice_id);
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Use your browser's Developer Tools (Network tab), or submit custom payloads in the form below. Change the query JSON <code>{"id": 1001}</code> to <code>{"id": 1002}</code> (Bob's Invoice) and click "Fetch Invoice via API" to capture cross-tenant data!</p>

        <form onsubmit="fetchInvoiceJSON(event)" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <label for="invoice_id">JSON Payload ID:</label><br>
            <input type="number" id="invoice_id" value="1001" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            <input type="submit" value="Fetch Invoice via API" class="btn">
        </form>

        <div id="invoice-card" class="invoice-card">
            <div class="invoice-field"><span class="invoice-label">Invoice Number:</span> #<span id="field-id"></span></div>
            <div class="invoice-field"><span class="invoice-label">Owner User ID:</span> <span id="field-user"></span></div>
            <div class="invoice-field"><span class="invoice-label">Line Item:</span> <span id="field-item"></span></div>
            <div class="invoice-field"><span class="invoice-label">Price Charged:</span> <span id="field-price" style="color: #facc15; font-weight: bold;"></span></div>
            <div class="invoice-field"><span class="invoice-label">Status:</span> <span id="field-status" style="color: #4ade80; font-weight: bold;"></span></div>
            
            <div id="exploit-msg" style="display:none; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; padding: 10px; border-radius: 6px; color: #34d399; margin-top: 15px; font-weight: bold; text-align: center;">
                🎉 SUCCESS: You bypassed JSON parameters and exfiltrated another user's invoice!
            </div>
        </div>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>

    <script>
        function fetchInvoiceJSON(e) {
            e.preventDefault();
            const idVal = parseInt(document.getElementById('invoice_id').value);
            
            fetch('level3.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: idVal })
            })
            .then(res => res.json())
            .then(data => {
                const card = document.getElementById('invoice-card');
                const exploitMsg = document.getElementById('exploit-msg');
                
                if (data.success) {
                    document.getElementById('field-id').innerText = data.data.id;
                    document.getElementById('field-user').innerText = data.data.user_id;
                    document.getElementById('field-item').innerText = data.data.item;
                    document.getElementById('field-price').innerText = data.data.price;
                    document.getElementById('field-status').innerText = data.data.status;
                    
                    card.style.display = 'block';
                    if (data.is_exploited) {
                        exploitMsg.style.display = 'block';
                    } else {
                        exploitMsg.style.display = 'none';
                    }
                } else {
                    alert(data.error);
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
