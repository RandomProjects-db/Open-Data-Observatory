# Server setup

How the production server was built on 14 Nov 2026. Commands are as run, with
secrets replaced. Lessons learned are marked **Note**.

## 1. Instance and network

- AWS Lightsail, Frankfurt (eu-central-1a), Ubuntu 24.04 LTS, $12/month plan (2 GB RAM).
- Static IPv4 `18.199.129.168`; dual-stack networking.
- Lightsail firewall: TCP 22 (SSH), 80 and 443, IPv4 and IPv6. Nothing else; MariaDB is local only.
- Automatic daily snapshots enabled.
- DNS: Namecheap A record `observatory.raiuz.site` to the static IP. Existing records for the root
  domain, `www`, `api` and email were left unchanged.

**Note:** the Lightsail Drupal (Bitnami) blueprint is marked "no longer maintained", so a clean
Ubuntu image was used and the stack installed from Ubuntu packages, which receive security updates.

## 2. Packages

```bash
sudo apt-get install -y nginx mariadb-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd \
  php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-opcache php8.3-intl php8.3-apcu \
  unzip git curl certbot python3-certbot-nginx
```

Composer installed from getcomposer.org with the installer's SHA-384 checksum verified first.

A 2 GB swap file was added because Composer can exceed 2 GB of memory.

## 3. Database

Random passwords are generated on the server and stored only in `~/odo-secrets.env` (mode 600).

```sql
CREATE DATABASE odo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'odo'@'localhost' IDENTIFIED BY '<DB_PASS>';
GRANT ALL PRIVILEGES ON odo.* TO 'odo'@'localhost';
```

## 4. Drupal

```bash
cd /var/www/odo
composer create-project drupal/recommended-project:^11 .
composer require drush/drush
vendor/bin/drush site:install standard --db-url=mysql://odo:<DB_PASS>@localhost/odo \
  --site-name="Open Data Observatory" --account-name=odo-admin
```

Added to `settings.php`: config sync directory outside the web root (`../config/sync`), private
file path, and `trusted_host_patterns` limited to `observatory.raiuz.site`.

## 5. File permissions

| Path | Owner:group | Mode | Why |
|---|---|---|---|
| Code | `ubuntu:www-data` | dirs 750, files 640 | Web server can read, not write |
| `web/sites/default/settings.php` | `ubuntu:www-data` | 440 | Read-only, contains secrets |
| `web/sites/default/files`, `private` | `ubuntu:www-data` | 2775 / 664 | Drupal writes uploads and generated files |
| All directories | | setgid | New files inherit group `www-data` |

**Note:** two outages came from permissions. Files created with `umask 077`, and later files
copied with `rsync`, could not be read by PHP-FPM, so pages returned 404 and then 500. The setgid
bit and `scripts/smoke-test.sh` after every change prevent and catch this.

## 6. Nginx

Server block `/etc/nginx/sites-available/odo` follows Drupal's recommended Nginx rules:
front controller routing, PHP only through `index.php` and `update.php`, image style
generation, 30-day caching for static assets, and denials for dotfiles, `settings*.php`,
`composer.json/lock`, YAML, Twig and other source files.

HTTPS from Certbot (`certbot --nginx --redirect`), so HTTP redirects to HTTPS. Certificates
renew automatically through `certbot.timer`.

**Note:** Nginx 1.24 does not support the `http2 on;` directive (added in 1.25.1). HTTP/2 is
enabled on the `listen 443 ssl http2;` lines instead.

## 7. Git and deploy key

The server pushes to GitHub with a repository-scoped deploy key (`~/.ssh/odo_deploy`, write
access to this repository only). Before each commit a check confirms that neither database
nor admin password appears in staged files.

## 8. Audit tooling (not served to visitors)

Node.js 22 (checksum-verified tarball in `/opt/node`), Google Chrome and Lighthouse in
`/opt/lighthouse`, used only by `scripts/lighthouse.sh`.
