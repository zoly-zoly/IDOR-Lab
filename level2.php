<?php
require_once 'session_helper.php';
$msg = '';
$error = '';
$doc_data = null;

// Simulate non-numeric request query parameter
$doc_id = isset($_GET['doc']) ? $_GET['doc'] : 'doc_99a8b1';

// VULNERABLE: The server loads the document based on the hash 'doc_id',
// but fails to verify if the document's 'user_id' matches Alice (ID 1).
if (isset($_SESSION['idor_db']['documents'][$doc_id])) {
    $doc_data = $_SESSION['idor_db']['documents'][$doc_id];
} else {
    $error = "Error: Document ID '" . htmlspecialchars($doc_id) . "' not found in the database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Level 2: Low | IDOR Lab</title>
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
        .doc-card { background: #0f172a; border-radius: 8px; padding: 20px; border: 1px dashed #334155; margin-top: 20px; }
        .doc-title { font-size: 1.2rem; font-weight: bold; color: #38bdf8; margin-bottom: 10px; border-bottom: 1px solid #1e293b; padding-bottom: 10px;}
        .doc-content { font-family: monospace; color: #cbd5e1; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Level 2: Low — Non-Numeric Hash Lookup</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <p>The developer realized that sequential numeric IDs are easy to scan and brute-force. They replaced them with non-predictable document keys (like <code>doc_99a8b1</code>). However, **security through obscurity is not security**; they forgot to implement actual authorization checks, and Bob's private document key was leaked elsewhere in public API metadata.</p>
        
        <h3>Source Code Snippet:</h3>
        <pre><code>&lt;?php
$doc_id = $_GET['doc'];
// VULNERABLE: Obscurity is NOT security. No session ownership verification!
$document = $db->query("SELECT * FROM documents WHERE hash_id = '{$doc_id}'");
?&gt;</code></pre>

        <h3>Your Goal:</h3>
        <p>Your private document key is <code>doc_99a8b1</code>. You intercepted Bob's leaked key from his public profile: <code>doc_5e4d3c</code>. Submit Bob's key in the input form below to exploit this IDOR and exfiltrate Bob's zero-gravity engine design files!</p>

        <form action="level2.php" method="GET" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155;">
            <label for="doc">Document Key:</label><br>
            <input type="text" id="doc" name="doc" value="<?php echo htmlspecialchars($doc_id); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white;" required><br>
            <input type="submit" value="Fetch Document" class="btn">
        </form>

        <?php if ($doc_data): ?>
            <div class="doc-card">
                <div class="doc-title">📂 <?php echo htmlspecialchars($doc_data['title']); ?></div>
                <div class="doc-content"><?php echo htmlspecialchars($doc_data['content']); ?></div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 15px;">Owner User ID: <?php echo htmlspecialchars($doc_data['user_id']); ?></div>
                
                <?php if ($doc_data['user_id'] != $current_user_id): ?>
                    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; padding: 10px; border-radius: 6px; color: #34d399; margin-top: 15px; font-weight: bold; text-align: center;">
                        🎉 SUCCESS: You bypassed the hash obscurity filter and leaked Bob's confidential files!
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="back">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>
