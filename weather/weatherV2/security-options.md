# Kaslo Dashboard — Security Options

Five approaches to block unauthorized access, ordered from simplest to most robust. **Nothing is implemented yet** — this is a decision document.

---

## Option 1 — HTTP Basic Auth (.htaccess)
**Effort:** 5 min · **Strength:** ★★★☆☆

The web server challenges every visitor with a native browser username/password prompt before serving any page or file. No code changes to the dashboard or PHP.

Setup — add `.htaccess` to your site root:
```
AuthType Basic
AuthName "Kaslo"
AuthUserFile /home/yourusername/.htpasswd
Require valid-user
```
Generate the password file via SSH: `htpasswd -c ~/.htpasswd kaslo`

Pros: Zero code changes. Blocks everything including todo.php and todo.json. Browser caches the session.
Cons: Ugly native browser prompt. Credentials are Base64 over the wire — REQUIRES HTTPS (free via Let's Encrypt). Single shared password only.

---

## Option 2 — PIN Modal in JavaScript (Client-Side Only)
**Effort:** 1 hr · **Strength:** ★☆☆☆☆

A full-screen overlay on the dashboard that requires a PIN before showing anything. Correct PIN is stored in localStorage so it only prompts once per device.

Pros: No server config needed. Works on any static host.
Cons: The HTML source is publicly readable — a developer can bypass it in 30 seconds via DevTools. Does NOT protect todo.php or todo.json. Protects against casual visitors only — not suitable if the todo list contains sensitive information.

---

## Option 3 — PHP Session Login Page
**Effort:** 2–3 hrs · **Strength:** ★★★★☆

A login.php validates a password and sets a session cookie. Every protected page checks the session at the top and redirects to login.php if unauthenticated. Sessions expire after configurable inactivity.

Pros: Server-enforced. Protects all endpoints. Session expiry is configurable. Can support multiple user accounts.
Cons: All .html files must be renamed to .php. Slightly more work than Option 1, but much nicer experience.

---

## Option 4 — IP Allowlist (.htaccess)
**Effort:** 15 min · **Strength:** ★★★★☆ when IPs are stable

Deny all traffic except from your known IP ranges:
```
Order Deny,Allow
Deny from all
Allow from 24.64.0.0/16
Allow from 198.51.100.42
```

Pros: No passwords or logins. Transparent once set up. Very hard to bypass.
Cons: Breaks whenever your IP changes (travel, mobile, ISP reassignment). Best combined with Option 1 as a second layer, not used alone.

---

## Option 5 — Cloudflare Access (Zero-Trust)
**Effort:** 30–60 min · **Strength:** ★★★★★

Point your DNS to Cloudflare (free), then enable Cloudflare Access in their Zero Trust dashboard. Visitors authenticate via one-time email code or Google/GitHub OAuth before Cloudflare proxies the request to your server at all.

Pros: Enterprise-grade. Works from any IP or device. Free for up to 50 users. Detailed access logs. No server code changes needed.
Cons: DNS must be managed through Cloudflare. Adds a dependency on a third-party service.

---

## Recommendation

| Situation | Best Choice |
|---|---|
| Just want something working today | Option 1 — Basic Auth + HTTPS |
| Static host, no PHP | Option 2 — JS PIN (casual only) |
| Nicer login form, multi-user | Option 3 — PHP sessions |
| Fixed home IP | Option 4 + Option 1 combined |
| Traveling, multiple devices | Option 5 — Cloudflare Access |

**Practical pick for this dashboard:** Start with Option 1. It takes 5 minutes, is genuinely secure when paired with HTTPS, and requires zero code changes. Upgrade to Option 3 later if you want a custom login page.
