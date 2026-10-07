# IDOR Deep-Dive: Theoretical Mastery & Access Control Architecture

Welcome to the official Wiki for the **IDOR Masterclass Lab Suite**. This wiki is designed to be an advanced architectural reference guide, shifting the focus from "solving the lab" to **why object references fail on the server** and how to design bulletproof, industry-standard access control models.

---

## 🔬 Part 1: The Core Mechanism of broken Access Control

At its heart, Insecure Direct Object Reference (IDOR) is a sub-category of **Broken Access Control** (ranked #1 in the OWASP Top 10). It occurs because of a fundamental design mistake: **confusing Identification with Authorization.**

### 1. Identification vs. Authorization
*   **Identification:** The user tells the server *what* object they want to load (e.g., `id=1002`). This is a locator.
*   **Authorization:** The server checks *if* the authenticated session has permission to load that object (e.g., "Does User ID 1 own Invoice ID 1002?").

When an application uses direct user inputs (parameters, JSON keys, headers) to select a database row without a secondary authorization query check, **it trusts the client to declare boundaries.** An attacker simply changes the identifier to ride across account barriers.

### 2. Why Obscurity (Hashing) Fails (Level 2)
Many developers try to fix IDOR by replacing sequential database IDs (`1`, `2`) with UUIDs, GUIDs, or cryptographic hashes (e.g. `doc_5e4d3c`).
*   While this makes brute-forcing harder, **it does not solve the underlying authorization failure.**
*   If those hashes are leaked in public profile URLs, search indexes, HTTP referrers, or client-side JavaScript arrays, any caller who grabs the hash can access the resource, because the backend still blindly trusts the parameter value.

---

## 🎯 Part 2: Advanced IDOR Exploit Archetypes

### 1. HTTP Parameter Pollution (HPP) Bypass (Level 5)
In modern cloud infrastructures, incoming requests pass through multiple network layers (WAFs, Gateways, Reverse Proxies) before hitting the execution backend (like Node.js or PHP).
*   **The Discrepancy:** Different software parsers treat duplicate query keys differently:
    *   **First Parameter Priority:** ASP.NET, Node.js (some parsers) read the *first* value.
    *   **Last Parameter Priority:** PHP, Python (some parsers) read the *last* value.
    *   **Array Parsing:** Node.js (`qs`), Ruby on Rails convert duplicates into arrays.
*   **The Exploit:** If an auth proxy validates the first parameter (`id=1001` - Alice) but PHP executes the last parameter (`id=1002` - Bob), the validation check is bypassed and the malicious action executes.

### 2. Nested REST API Failures (Level 8)
In hierarchical databases, resources are nested: `/users/{user_id}/documents/{document_id}`.
*   **The Flaw:** Developers often write an authorization check verifying that `session_user_id === user_id` (checking if Alice owns Alice's folder).
*   **The Leak:** However, when querying the SQL database, the developer's SQL statement looks like:
    `SELECT * FROM documents WHERE id = :document_id`
    Because the SQL statement doesn't bind the query to the parent user (e.g., `WHERE id = :document_id AND user_id = :session_user_id`), an attacker keeps their own `user_id` but changes `document_id` to Bob's document, bypassing the gateway checks.

### 3. Mass Assignment / Property Injection (Level 9)
In modern MVC frameworks (Laravel, Rails, Spring), developers use ORM (Object-Relational Mapping) engines to save forms directly:
`User::update($id, $request->all())`
*   **The Flaw:** If the ORM binds the request map directly to the model, an attacker can append unexpected keys (such as `role=admin` or `is_premium=true`) to their standard profile update payload. The backend merges this into the database row, resulting in privilege escalation.

---

## 🛡️ Part 3: Secure Access Control Architecture

To eliminate IDOR across an entire organization, developers must implement **Indirect Reference Maps** or **Strict Row-Level Authorization**.

```
                  ┌────────────────────────────────────────┐
                  │          Inbound Entity Request        │
                  └───────────────────┬────────────────────┘
                                      │
                                      ▼
                        [ Session Auth Verification ]
                                      │
                                      ▼
                       [ Database Query Bind Check ]
                        (Bind query to Active Session ID)
                        e.g., WHERE id = :id AND user_id = :session_user_id
                                      │
                                      ├─► (Reject if 0 rows returned)
                                      ▼
                           [ Display / Modify Object ]
```

### 1. Strict Query Binding (The Standard)
Never query objects based on user-supplied IDs alone. Always bind the database fetch statement to the active user's session ID:

```php
// SECURE: Enforces row-level session boundaries natively
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id AND user_id = :session_user_id");
$stmt->execute([
    'id' => $invoice_id,
    'session_user_id' => $_SESSION['user_id']
]);
$invoice = $stmt->fetch();
```
*If an attacker requests Bob's invoice ID, the query returns `0` records because Bob's invoice does not match Alice's `user_id` constraint.*

### 2. Indirect Reference Mapping
If you must expose references, map internal database keys to temporary, session-scoped random tokens:
*   Instead of displaying `id=1001`, the application generates a temporary mapping: `abc-xyz-789` -> `1001` stored strictly in the user's session cache.
*   When the user requests `abc-xyz-789`, the server looks up the map in their private session memory. If the key is not in their session cache, the request is rejected immediately.

### 3. Strict Mass Assignment Protection
Always enforce a strict **Allowed Field Whitelist** (e.g., using `fillable` or strong parameters) when merging client maps into database models, completely ignoring un-whitelisted fields like `role`, `is_admin`, or `privileges`.

---
*Documentation curated by **Zoly** for the advancement of secure application architecture.*
