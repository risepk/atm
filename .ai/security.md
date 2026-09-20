# Security Architecture & Hardening Standards

## Multi-Tenant Authorization & IDOR Mitigation
- Every account belongs to an owner (`accounts.user_id`).
- All controllers query accounts and transactions with ownership enforcement:
  ```php
  if (!$this->isAdmin() && (int)$account['user_id'] !== (int)$sessionUserId) {
      throw new Exception("Unauthorized access to account #{$accountId}.");
  }
  ```
- URL parameter manipulation cannot access or modify records belonging to other users.

## Session Security & Authentication
- Passwords are encrypted with `password_hash(..., PASSWORD_DEFAULT)` using bcrypt.
- Password input preserves leading/trailing spaces verbatim to protect complex passphrases.
- Upon successful authentication, `session_regenerate_id(true)` is executed immediately to protect against session fixation.
- Sessions store only minimal identifying tokens: `user_id`, `username`, `full_name`, `role`.

## CSRF Protection
- All non-GET requests require a CSRF token passed via `_csrf_token` POST field or `X-CSRF-Token` header.
- Handled globally by the `csrf` route middleware.
- Logout is strictly `POST /logout` protected by CSRF.

## XSS Mitigation & Input Sanitization
- All dynamic HTML output is wrapped in `e()`:
  ```php
  function e(?string $str): string {
      return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
  ```
- Strips script tags, img tags with onerror handlers, and unescaped SVG attributes.

## Prepared Statements
- All database queries use PDO prepared statements with parameter binding:
  ```php
  $this->db->fetchAll("SELECT * FROM transactions WHERE account_id = ? AND status = ?", [$accountId, 'posted']);
  ```
- Raw SQL string concatenation is strictly banned.
