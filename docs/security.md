# Security

## Secrets

| Secret | Where | Never in |
|---|---|---|
| Database passwords (production, staging) | `~/odo-secrets.env` on the server (mode 600) and each `settings.php` (mode 440) | Git, logs, chat |
| Drupal admin password | `~/odo-secrets.env` | Git, logs, chat |
| Hash salt | `settings.php` | Git |
| GitHub deploy key | `~/.ssh/odo_deploy` on the server (write access to this repository only) | Anywhere else |
| SSH key to the server | Owner's laptop `~/.ssh/odo-server.pem` (mode 400) | Server, Git |

Safeguards:
- `.gitignore` excludes `settings*.php`, `services.yml`, `.env` files, uploads and private files.
- Before each commit, staged files are scanned for the actual database and admin passwords.
- CI fails if any `settings.php` is tracked.

## Network

- Lightsail firewall allows only TCP 22, 80 and 443. MariaDB listens on localhost only.
- All HTTP traffic redirects to HTTPS.
- `trusted_host_patterns` rejects requests for any other hostname (prevents host header attacks).

## Web server rules

Nginx denies or returns 404 for: dotfiles (except `.well-known`), `settings*.php`,
`composer.json` and `composer.lock`, YAML, Twig and other source files, PHP files inside
`files/`, and private file paths. Verified with requests to `/sites/default/settings.php` and
`/composer.json` (both 404).

## Application

- Admin account `odo-admin` with a random password of about 24 characters; editors use the limited
  Content editor role.
- Only the importer creates datasets; editors can correct but not create or delete them.
- Importer inputs are allow-listed and URL-encoded; external responses are validated before saving.
- Report and dataset HTML from the importer escapes external text with `htmlspecialchars` /
  `Html::escape`.

## Updates

| Layer | How |
|---|---|
| Ubuntu packages | `unattended-upgrades` (security updates, daily) |
| Drupal core and modules | `composer outdated "drupal/*"` and `composer audit` weekly; Drupal security releases on Wednesdays; procedure in the deploy runbook |
| Certificates | `certbot.timer` |

Status on 15 Nov 2026: all direct dependencies up to date; `composer audit` reports no advisories.

## Known gaps

- SSH is open to any IP address (Lightsail browser SSH also works); restrict to a fixed IP if available.
- No web application firewall or rate limiting on login (Drupal's flood control applies).
- Backups are on the same server; Lightsail snapshots are the off-server copy.
- No external uptime monitor yet.
