# ✅ Secure Version — Implementation Writeup

> This is the fully hardened version of the PHP web application. Every vulnerability identified in the [insecure version](../insecure/) has been mitigated using industry-standard techniques. This document explains what was done, why, and how.

---

## What Was Added / Changed

| Category | Changes Made |
|----------|-------------|
| SQL | All queries converted to prepared statements |
| XSS | `htmlspecialchars()` on all output, DOM XSS feature removed |
| Passwords | `password_hash(PASSWORD_BCRYPT)` + `password_verify()` |
| CSRF | Custom `csrf.php` library with `hash_equals()` token validation |
| Sessions | `session_regenerate_id(true)` + 4 secure cookie flags |
| Auth | Google OAuth 2.0 added (`login.php`) |
| Transport | TLS 1.2/1.3 only, HSTS, security headers |
| Secrets | `.env` file outside web root via `env_loader.php` |
| Password Reset | 4-step OTP flow with email enumeration protection |
| New Features | `profile.php`, `navbar.php`, `csrf.php`, `env_loader.php` |

---

## Fix 1 — SQL Injection → Prepared Statements

**Files:** `index.php`, `register.php`, `welcome.php`, `controllerUserData.php`

### The Problem

Raw string interpolation allowed attackers to break out of the SQL string context and inject arbitrary SQL commands.

### The Fix

Every SQL query across the entire codebase was converted to use `mysqli_prepare()` with `mysqli_stmt_bind_param()`. The database engine receives the query structure and the data values separately — user input can never be interpreted as SQL syntax.

```php
// BEFORE — injectable
$sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
$result = mysqli_query($conn, $sql);

// AFTER — safe
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

The `?` placeholder is a typed parameter slot. The `"s"` in `bind_param` declares it as a string. No amount of quoting, commenting, or SQL syntax in the value can affect the query structure.

---

## Fix 2 — XSS → Output Encoding

**Files:** `welcome.php`, `index.php`, `register.php`

### The Problem

User-supplied data retrieved from the database was echoed directly into HTML. Any stored script tag would execute in every visitor's browser.

### The Fix

`htmlspecialchars()` applied to **every** piece of data before it is echoed into the HTML response. This converts the five HTML-special characters to their entity equivalents:

```
<  →  &lt;
>  →  &gt;
"  →  &quot;
'  →  &#039;
&  →  &amp;
```

```php
// BEFORE — executes stored scripts
echo $row['name'];
echo $row['comment'];

// AFTER — renders scripts as literal text
echo htmlspecialchars($row['name'],    ENT_QUOTES, 'UTF-8');
echo htmlspecialchars($row['comment'], ENT_QUOTES, 'UTF-8');
```

The DOM-Based XSS (Quick Search feature) was **removed entirely** — the only safe fix for `innerHTML` assignment is to not use it. The database search was refactored to use a server-side prepared query with escaped output.

The `?test` reflected XSS parameter was also removed — unvalidated GET parameters are never echoed to output in the secure version.

---

## Fix 3 — Plaintext Passwords → BCrypt

**File:** `register.php`, `index.php`

### The Problem

Passwords were stored as raw strings in the database. Any breach — SQL injection, backup leak, or direct DB access — exposed every user's plaintext password immediately.

### The Fix

`password_hash()` with `PASSWORD_BCRYPT` algorithm:

```php
// BEFORE — plaintext storage
$sql = "INSERT INTO users ... VALUES ('$password')";

// AFTER — bcrypt hash storage
$hash = password_hash($password, PASSWORD_BCRYPT);
// Store $hash, never $password
```

BCrypt properties that make it appropriate for password storage:
- **Salted automatically** — each hash includes a unique random salt; rainbow tables are useless
- **Computationally expensive** — default cost factor 10 means ~100ms per hash; brute-force is impractical
- **Self-describing** — hash string encodes the algorithm, cost, and salt (`$2y$10$...`)

Verification uses `password_verify()`:

```php
// Never compare password to hash manually
if (password_verify($submitted_password, $row['password'])) {
    // Login successful
}
```

`password_verify()` is **constant-time** — it always takes the same duration regardless of how many characters match, preventing timing attacks.

---

## Fix 4 — CSRF Tokens

**File:** `csrf.php` (new), all form pages

### The Problem

Every POST form accepted requests from any origin. An attacker could embed a hidden auto-submitting form on a third-party site, and the victim's browser would execute it as the victim.

### The Fix

A dedicated `csrf.php` library provides two functions:

```php
// Generate (once per session) or retrieve token
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate submitted token against session token
function csrf_validate(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    // hash_equals() is constant-time — prevents timing oracle attacks
    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('CSRF validation failed');
    }
}
```

Every protected form includes the token as a hidden field:

```html
<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
```

And every POST handler validates it before processing:

```php
if (isset($_POST['submit'])) {
    csrf_validate();
    // ... rest of form processing
}
```

`random_bytes(32)` produces 256 bits of cryptographically secure random data from the OS entropy pool. `hash_equals()` performs constant-time comparison — an attacker cannot guess the token by measuring response time differences.

---

## Fix 5 — Session Hardening

**Files:** `php.ini`, `index.php`, `login.php`

### The Problem

Sessions had no security properties: cookies transmitted over HTTP, readable by JavaScript, no same-site restrictions, and no ID regeneration on login (session fixation risk).

### The Fix

**php.ini:**
```ini
; Only send cookie over HTTPS — cannot be intercepted on HTTP
session.cookie_secure     = On

; JavaScript cannot access the session cookie — XSS cannot steal it
session.cookie_httponly   = On

; Browser refuses to send cookie on cross-site requests — CSRF mitigation
session.cookie_samesite   = Strict

; Server will not accept externally provided session IDs — prevents fixation
session.use_strict_mode   = 1
```

**On every successful login:**
```php
// Destroy old session ID and issue new one
// true = delete old session file; prevents fixation
session_regenerate_id(true);

$_SESSION['username'] = $row['username'];
$_SESSION['user_id']  = $row['id'];
```

This means even if an attacker pre-plants a session ID (fixation), it is replaced with a new random one the moment the victim authenticates.

---

## Fix 6 — SSL / HTTPS

**File:** `httpd-ssl.conf`

### Problems Found

1. Duplicate SSL directives inside AND outside `<VirtualHost>` — Apache wouldn't start
2. TLS 1.0 and 1.1 enabled — both deprecated with known attacks (BEAST, POODLE)
3. `server.key` required a passphrase — Apache couldn't start automatically
4. No HSTS or security headers

### Fixes Applied

```apache
# Single clean VirtualHost block
<VirtualHost _default_:443>

    # TLS 1.0 and 1.1 disabled
    SSLProtocol -all +TLSv1.2 +TLSv1.3

    # Passphrase-free key for dev environment
    SSLCertificateKeyFile "conf/ssl.key/server-nopass.key"

    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

</VirtualHost>
```

**HTTPS check fix** — the original code used `$_SERVER['HTTPS'] !== 'on'` which fails on XAMPP (which sets `HTTPS` to `'1'`, not `'on'`):

```php
// BEFORE — breaks on XAMPP
if ($_SERVER['HTTPS'] !== 'on') { redirect_to_https(); }

// AFTER — handles both 'on' and '1'
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    redirect_to_https();
}
```

---

## Fix 7 — Secrets Management

**Files:** `.env`, `env_loader.php`, `config.php`

### The Problem

Database credentials, Google OAuth secrets, and reCAPTCHA keys were all hardcoded in PHP source files committed to version control.

### The Fix

All secrets moved to a `.env` file stored **outside the web root**:

```
C:\xampp\htdocs\.env   ← above the web root, not directly accessible
C:\xampp\htdocs\cw2-secure\  ← application files here
```

`env_loader.php` parses the file and populates `$_ENV`:

```php
function load_env(string $path): void {
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;

        // BUG FIX: original used !strpos($line, '=')
        // strpos returns 0 (falsy) when '=' is at position 0 — valid lines were skipped
        // Correct test: === false (only skip when '=' genuinely absent)
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
```

---

## Fix 8 — Google OAuth 2.0

**File:** `login.php` (new)

The secure version adds Google sign-in as a second authentication method:

```
User → clicks "Sign in with Google"
     → redirected to Google consent screen
     → Google redirects back to login.php?code=AUTH_CODE
     → fetchAccessTokenWithAuthCode($code) exchanges code for tokens
     → Google_Service_Oauth2 retrieves email, name, google_id
     → DB lookup:
         - google_id exists     → log in directly
         - email exists         → link Google ID to existing account
         - neither exists       → create new account
     → session_regenerate_id(true) → redirect to welcome.php
```

**Bug fixed:** The original `login.php` called `session_regenerate_id(true)` on every page load (checking for an 'initiated' session key). When Google redirected back with `?code=`, the fresh request had no 'initiated' key, destroying the stored OAuth state before it could be used. Fix: removed the 'initiated' block; `session_regenerate_id(true)` is called only on confirmed successful login.

---

## Fix 9 — OTP Password Reset

**File:** `controllerUserData.php`

4-step flow with security controls at each stage:

### Step 1 — forgot-password.php
```php
// Email enumeration protection
// Same response whether email exists or not
// Attacker cannot determine valid email addresses
$msg = "If that email exists, a reset code has been sent.";
```

### Step 2 — reset-code.php
```php
// BUG FIX: original was rand(999999, 111111)
// max < min — PHP's rand() returned 999999 every time
// Every single OTP was always 999999
$code = rand(111111, 999999); // min first, max second
```

### Step 3 — new-password.php
```php
// Password strength validation
if (strlen($password) < 8
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[0-9]/', $password)
    || !preg_match('/[\W_]/', $password)) {
    $errors[] = "Password must be 8+ chars with upper, lower, number, and special char.";
}
```

### Step 4 — password-changed.php
```php
// Clear OTP after use — prevents replay attacks
$stmt = mysqli_prepare($conn, "UPDATE users SET code = NULL WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
```

---

## Fix 10 — PHP Hardening

**File:** `php.ini`

```ini
; Never show error messages to users — stack traces leak code paths
display_errors       = Off
display_startup_errors = Off

; Log errors server-side only
log_errors           = On

; Extension syntax fix — old php_ prefix caused loading failure
extension            = openssl    ; was: extension=php_openssl.dll

; Needed for multi-byte character encoding safety
extension            = mbstring

; Session security (see Fix 5)
session.cookie_secure     = On
session.cookie_httponly   = On
session.cookie_samesite   = Strict
session.use_strict_mode   = 1
```

---

## Security Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    HTTPS (TLS 1.3)                          │
│              HSTS · X-Frame-Options · CSP                   │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                    Apache 2.4                                │
│         httpd-ssl.conf · TLSv1.2+1.3 only                  │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                    PHP 8.2                                   │
│   display_errors=Off · session hardening · openssl          │
│                                                             │
│  ┌──────────────────┐     ┌──────────────────────────────┐  │
│  │   csrf.php       │     │    env_loader.php            │  │
│  │  random_bytes()  │     │    .env outside web root     │  │
│  │  hash_equals()   │     │    strpos() === false fix    │  │
│  └──────────────────┘     └──────────────────────────────┘  │
│                                                             │
│  Input → validate → prepared stmt → DB                     │
│  DB    → fetch    → htmlspecialchars → HTML output          │
│                                                             │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                    MariaDB                                   │
│  Parameterised queries only · bcrypt hashes · NULL OTPs     │
└─────────────────────────────────────────────────────────────┘
```

---

## Standards Met

| Standard | Requirement | Implementation |
|---------|-------------|----------------|
| OWASP A03 | Prevent injection | Prepared statements, `htmlspecialchars()` |
| OWASP A02 | Cryptographic failures | bcrypt, TLS 1.3, `random_bytes()` |
| OWASP A07 | Authentication failures | Session hardening, `session_regenerate_id()` |
| OWASP A05 | Security misconfiguration | PHP hardening, Apache SSL config |
| OWASP A01 | Broken access control | Session guards on all protected pages |
| CERT IDS00 | SQL injection | Prepared statements throughout |
| CERT IDS51 | XSS | `htmlspecialchars()` on all output |
| CERT MSC02 | Random numbers | `random_bytes(32)` for CSRF tokens |
| STRIDE | Spoofing | CSRF tokens + session regeneration |
| STRIDE | Tampering | Prepared statements + CSRF |
| STRIDE | Info Disclosure | bcrypt + TLS + .env secrets |

---

See [`/insecure`](../insecure/) for the vulnerable version and working exploit proofs-of-concept.
