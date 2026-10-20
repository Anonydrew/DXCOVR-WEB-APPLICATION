# Deploying the DXCOVR website

This guide covers a first deployment, routine updates, backups and troubleshooting. It is written for Hostinger shared/cloud hosting with SSH access, and applies equally to any Linux host running Apache/LiteSpeed or Nginx.

> **Safety first:** never run `migrate:fresh`, `migrate:reset`, `db:wipe` or `DemoContentSeeder` on production. Back up the database and `storage/` before every update.

---

## 1. Requirements

| Item | Requirement |
| --- | --- |
| PHP | **8.3 or newer** (select it in hPanel → Advanced → PHP Configuration) |
| PHP extensions | `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd` (with WebP support), `intl`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip` |
| PHP settings | `memory_limit` ≥ 256M (image processing), `upload_max_filesize` ≥ 55M and `post_max_size` ≥ 60M (video uploads; lower is fine if you only upload images), `max_execution_time` ≥ 60 |
| Database | MySQL 8.0+ or MariaDB 10.6+ (utf8mb4) |
| Composer | 2.x (available over SSH on Hostinger as `composer`) |
| Node.js | 20+ **on your own machine** to build front-end assets (shared hosting usually has no Node) |
| HTTPS | Required. Secure session cookies are enabled by default |
| Cron | One cron entry running every minute |

## 2. Directory layout and document root

The web server must serve **only** the `public/` directory. The project root (containing `.env`, `storage/`, `vendor/` and source code) must never be web accessible.

Recommended layout on Hostinger:

```
/home/USER/domains/example.com/
├── dxcovr/            ← the full project (this repository)
│   ├── public/
│   ├── storage/
│   └── .env
└── public_html → dxcovr/public   (symbolic link)
```

Create the link over SSH (after moving any existing `public_html` contents out of the way):

```bash
cd ~/domains/example.com
mv public_html public_html.bak        # keep until the new site is verified
ln -s ~/domains/example.com/dxcovr/public public_html
```

If your plan lets you change the website root directory in hPanel, pointing it at `dxcovr/public` is equivalent. **Do not** upload the whole project into `public_html`.

On Nginx, set `root /path/to/dxcovr/public;` and route requests to `index.php` (`try_files $uri $uri/ /index.php?$query_string;`). Nginx ignores `.htaccess`, so also add the header that Apache sets for uploaded SVGs:

```nginx
location ~* ^/storage/.*\.svg$ {
    add_header Content-Security-Policy "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox";
    add_header X-Content-Type-Options "nosniff";
}
```

## 3. First deployment

### 3.1 Build front-end assets locally

```bash
npm ci
npm run build          # creates public/build (ignored by git)
```

Upload the generated `public/build` directory along with the code (or build in CI and include it in your release archive).

### 3.2 Upload the code

Either `git clone` the repository into `~/domains/example.com/dxcovr` over SSH, or upload a release archive (excluding `node_modules`, `.env`, `storage/logs/*` and `database/database.sqlite`).

### 3.3 Install dependencies

```bash
cd ~/domains/example.com/dxcovr
composer install --no-dev --optimize-autoloader
```

### 3.4 Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at least:

| Variable | Notes |
| --- | --- |
| `APP_URL` | `https://www.your-domain` (no trailing slash) |
| `APP_ENV=production`, `APP_DEBUG=false` | Never enable debug in production |
| `DB_*` | Database created in hPanel → Databases |
| `MAIL_*` | SMTP details (Hostinger email: host `smtp.hostinger.com`, port 465, scheme `smtps`) |
| `MAIL_FROM_ADDRESS` | An address on your domain |
| `NOTIFICATION_FALLBACK_EMAIL` | Receives enquiry/application alerts until recipients are set in the admin |
| `SESSION_SECURE_COOKIE=true` | Requires HTTPS |
| `SECURITY_HSTS` | Set `true` once HTTPS is confirmed on all subdomains |
| `CAPTCHA_DRIVER` / `TURNSTILE_*` | Optional Cloudflare Turnstile keys |

Keep `.env` permissions restrictive: `chmod 600 .env`.

### 3.5 Database, permissions and starter content

```bash
php artisan migrate --force
php artisan db:seed --force          # roles, permissions and starter content; never overwrites existing records
php artisan storage:link             # exposes storage/app/public at /storage
```

`db:seed` is safe to run again later: every record is created only if missing. It creates **no user accounts**.

If `storage:link` is not permitted, create the link manually: `ln -s ../storage/app/public public/storage`.

### 3.6 Create the first administrator

```bash
php artisan dxcovr:create-admin
```

You are prompted for a name, email and password (12+ characters, upper- and lower-case letters and a number; checked against known breaches in production). Alternatively, once mail works:

```bash
php artisan dxcovr:create-admin --name="Anthony Jnr Ahiable" --email=you@your-domain --send-reset-link
```

There is no default password and no public registration. After signing in at `/admin/login`, enable two-factor authentication under **Account & security**, then invite other staff from **Admin → Users** (they receive a link to choose their own password).

### 3.7 Optimise

```bash
php artisan optimize        # caches config, routes, views and events
```

Re-run `php artisan optimize` after any change to `.env` or code. (`php artisan optimize:clear` clears the caches.)

### 3.8 File permissions

```bash
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

The web server user must be able to write to `storage/` and `bootstrap/cache/`. No other directory needs to be writable.

### 3.9 Scheduler and queue (cron)

Add **one** cron job (hPanel → Advanced → Cron Jobs), running every minute:

```
* * * * * cd /home/USER/domains/example.com/dxcovr && php artisan schedule:run >> /dev/null 2>&1
```

Use the full PHP path if required, e.g. `/usr/bin/php` or `/opt/alt/php83/usr/bin/php`.

The scheduler runs:

| Task | Frequency |
| --- | --- |
| `content:publish-scheduled` — publishes scheduled articles, pages, services, projects, products and jobs | every minute |
| `queue:work --stop-when-empty` — sends queued emails (notifications) | every minute |
| `careers:close-expired` — closes listings after their deadline | every 15 minutes |
| `applications:purge-expired` — deletes applications/CVs past the retention period | daily 02:30 |
| `auth:clear-resets` — removes expired password reset tokens | daily |

If you run a dedicated worker (VPS with Supervisor: `php artisan queue:work --tries=3`), set `QUEUE_WORK_VIA_SCHEDULER=false`.

Check the schedule with `php artisan schedule:list`.

### 3.10 Before launch — content checklist

1. **Settings → Contact & social:** add the public email, phone, address and WhatsApp number (left blank by default so nothing fabricated is shown).
2. **Settings → Notifications:** set who receives enquiry, application and onboarding-questionnaire emails.
3. **Pages:** review the Privacy policy, Cookie policy, Terms of use and Accessibility statement (seeded as drafts with bracketed placeholders), have them checked for Ghanaian law (including the Data Protection Act, 2012 (Act 843)), then publish. Footer links appear automatically once they are published.
4. **Products → DXCOVR Clinic ERP:** confirm the product status (seeded as *In development*), add modules, features and real screenshots.
5. **Founder profile:** add a portrait, verified experience and links.
6. **Portfolio:** add case studies when they are cleared for disclosure; the homepage section appears automatically.
7. **Settings → SEO:** upload a default social image (1200 × 630) and organisation logo.
8. **Pages → Homepage → Hero:** upload a landscape background photo (at least 2400 px wide, subject on the right). Add client or partner logos only with written permission, as transparent PNGs (SVG uploads are not accepted; logos are displayed as white silhouettes). Until then the strip shows DXCOVR's disciplines. The proof cards are seeded with facts about DXCOVR itself. Replace them with client results only when the figures can be evidenced and the client has agreed to be named.
9. **Pages → Homepage → Spotlight carousel:** add real photography or product screenshots to the cards (portrait images around 800 × 1200 work best). Cards without an image use DXCOVR artwork.
10. **Pages → Services and Pages → Insights → Hero:** upload a wide background photo for each (at least 2400 px). Until then a DXCOVR artwork background is shown.
11. **Pages → Homepage → Clients:** add clients only with their written permission to be named, each with a transparent PNG logo and a line about the work delivered. The section stays hidden while it is empty.

## 4. Verifying production

- `https://your-domain/up` returns HTTP 200.
- Home, services, a service page, work, products, insights, careers and contact load without errors.
- `/sitemap.xml` lists only published content; `/robots.txt` disallows `/admin`.
- `/admin` redirects to sign-in; `/.env`, `/storage/../.env` and `/composer.json` return 403/404.
- Submit the contact form: the enquiry appears in **Admin → Enquiries** and the notification email arrives within a minute (queue via cron).
- Response headers include `Content-Security-Policy`, `X-Frame-Options: SAMEORIGIN` and `X-Content-Type-Options: nosniff`.
- `storage/logs/laravel-*.log` contains no errors.

## 5. Updating

```bash
cd ~/domains/example.com/dxcovr
php artisan down --retry=60                 # optional maintenance mode
# back up the database and storage/ first (see §6)
git pull                                     # or upload the new release (including public/build)
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan dxcovr:sync-permissions          # adds any new permissions; never removes custom roles
php artisan media:refresh-variants           # only when image rendition settings changed; rebuilds resized copies
php artisan optimize
php artisan queue:restart
php artisan up
```

### Rollback

Migrations in this project are additive. To roll back a release: restore the previous code (and its `public/build`), run `composer install --no-dev`, then `php artisan optimize`. If a migration must be undone, restore the database backup taken before the update rather than running `migrate:rollback` on live data.

## 6. Backups and recovery

Back up, at least daily, and keep copies off the server:

| What | How |
| --- | --- |
| Database | Hostinger automatic backups, plus `mysqldump --single-transaction dxcovr > dxcovr-$(date +%F).sql` |
| Public media | `storage/app/public/` |
| Private recruitment files | `storage/app/private/applications/` — contains personal data: store encrypted and restrict access |
| Client onboarding uploads | `storage/app/private/onboarding/` — client files from questionnaires: store encrypted and restrict access |
| Configuration | `.env` — store securely (password manager or secrets vault), never in git |

Test a restore periodically on a staging copy. Deleted applications are removed from backups only when those backups expire, so align backup retention with the recruitment retention period in your privacy policy.

## 7. Object storage (optional)

To store public media on S3-compatible storage (AWS S3, Cloudflare R2, DigitalOcean Spaces):

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

Set `MEDIA_DISK=s3` and the `AWS_*` variables (plus `AWS_URL`/`AWS_ENDPOINT` for non-AWS providers). Keep `CV_DISK=local` or use a **separate, private** bucket for CVs. Existing files must be copied to the bucket before switching.

## 8. Logs and troubleshooting

| Symptom | Check |
| --- | --- |
| HTTP 500 | `storage/logs/laravel-*.log`; permissions on `storage/` and `bootstrap/cache/`; run `php artisan optimize:clear` |
| "Vite manifest not found" | `public/build` was not uploaded — run `npm run build` locally and upload it |
| Images/media 404 | `public/storage` link missing — `php artisan storage:link` |
| Emails not arriving | Cron not running (`php artisan schedule:list`, check the queue: `php artisan queue:failed`); verify `MAIL_*`; retry with `php artisan queue:retry all` |
| Scheduled posts not publishing | Cron not running or wrong PHP path; run `php artisan content:publish-scheduled` manually to test |
| Uploads fail for large files | Raise `upload_max_filesize` / `post_max_size` in PHP configuration |
| Behind Cloudflare or a load balancer | Trust the proxy in `bootstrap/app.php`: `$middleware->trustProxies(at: '*');` so HTTPS and client IPs are detected correctly |
| Locked out | `php artisan dxcovr:create-admin` creates a new Super Administrator; reset a password with “Forgot password” |

## 9. Security notes and known limitations

- Security controls are covered by automated tests (authorisation, CSRF, rate limiting, upload validation, sanitisation, private CV access), but this is not a substitute for an independent security review before handling sensitive data at scale.
- The admin Content Security Policy permits `'unsafe-eval'` because Alpine.js evaluates inline expressions; the public site does not.
- Rate limits use the configured cache store (database by default). Behind a proxy, configure trusted proxies so limits apply per visitor rather than per proxy.
- Newsletter sending is not built in: confirmed subscribers are exported as CSV for your email provider.
- No analytics or third-party trackers are installed. If you add any, update the cookie policy and consent handling accordingly.
