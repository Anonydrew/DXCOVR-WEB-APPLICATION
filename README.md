# DXCOVR website and CMS

The public website for DXCOVR — software, AI, embedded systems, AR/VR/WebXR, 3D and design — together with a private, database-driven content management system for the team.

- **Public site:** home, services, work (case studies), products, insights, careers with online applications, about, founder profile, contact, search, legal pages, sitemap, RSS feed.
- **Admin (`/admin`):** articles with drafts, review, scheduling and previews; services, portfolio and products; pages and homepage sections; media library; careers and applications; enquiries and newsletter; navigation, settings, SEO and redirects; users, roles and an audit log.

Deployment to production (including Hostinger) is covered in **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)**.

---

## Stack

| Layer | Choice |
| --- | --- |
| Framework | Laravel 13 (PHP 8.3+) |
| Database | MySQL 8 / MariaDB 10.6+ in production; SQLite for local development and tests |
| Front end | Blade, Tailwind CSS 4, Vite 8. Public site uses small vanilla JS (no `eval`, strict CSP); the admin uses Alpine.js, Tiptap (rich text) and SortableJS |
| Auth | Laravel Fortify (sign-in, password reset, TOTP two-factor). No public registration |
| Authorisation | spatie/laravel-permission roles + permissions, enforced by route middleware and policies |
| Images | Laravel's Image API (Intervention/GD): EXIF-stripped re-encoding and WebP renditions |
| Rich text safety | Symfony HtmlSanitizer allow-list (iframes limited to YouTube/Vimeo) |
| Fonts | Space Grotesk, Inter, IBM Plex Mono — self-hosted via Fontsource (SIL OFL) |

## Local development

Requirements: PHP 8.3+ with `pdo_sqlite`, `gd`, `intl`, `fileinfo`, `zip`; Composer 2; Node 20+.

```bash
composer install
cp .env.example .env            # then set APP_ENV=local, APP_DEBUG=true, APP_URL=http://localhost:8000,
                                # DB_CONNECTION=sqlite, MAIL_MAILER=log, SESSION_SECURE_COOKIE=false
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # roles, permissions, starter content and, locally, an admin from LOCAL_ADMIN_* in .env
php artisan db:seed --class=DemoContentSeeder   # optional, local only: clearly labelled demo articles/projects/job
php artisan storage:link
php artisan dxcovr:create-admin # alternative: prompts for name, email and a password (the only option outside local)
npm install && npm run build    # or `npm run dev` while working on the front end
php artisan serve
```

Sign in at <http://localhost:8000/admin/login>.

### Useful commands

| Command | Purpose |
| --- | --- |
| `php artisan dxcovr:create-admin` | Create an administrator (`--send-reset-link` emails a set-up link instead of prompting for a password) |
| `php artisan dxcovr:sync-permissions` | Create missing permissions/roles after changing `app/Support/Permissions.php` (`--reset-roles` restores default role permissions) |
| `php artisan content:publish-scheduled` | Publish scheduled content that is due (runs every minute via the scheduler) |
| `php artisan careers:close-expired` | Close job listings whose deadline has passed |
| `php artisan applications:purge-expired [--dry-run]` | Delete applications and CVs past the retention period |
| `php artisan test` | Run the test suite |
| `vendor/bin/pint` | Format PHP code |

## Architecture notes

- **Publication workflow** — `Draft → In review → Scheduled → Published → Archived`. Status is never mass-assignable; it changes only through `App\Services\PublicationService`, which validates completeness (e.g. excerpt, category, image alt text), records audit entries and is driven by explicit buttons. Saving a form never publishes. Scheduled items are published by the scheduler.
- **Pages and sections** — Core pages (home, about, services, work, products, insights, careers, contact) are composed of predefined section types defined in `app/Support/Sections/SectionRegistry.php`. Editors change validated content only; layouts live in `resources/views/site/sections`. Sections whose source data is empty (no case studies, no testimonials…) are hidden automatically rather than showing placeholders.
- **Honest content** — The seeders contain no clients, testimonials, statistics or case studies. Legal pages are seeded as **drafts** for jurisdictional review; navigation hides links to unpublished pages automatically.
- **Media** — Public media lives on `MEDIA_DISK` (default `public`). File types are detected from content; SVG and executables are rejected; deletion is blocked while media is referenced anywhere (fields, galleries, sections, rich text).
- **Recruitment data** — CVs are stored on the private `local` disk under random names and are only streamed through an authorised, audited admin route. Applications are purged automatically after the configured retention period.
- **Submissions** — Enquiries and applications are saved before any email is queued, so a mail outage never loses a submission. Public forms use a honeypot, a signed minimum completion time, rate limiting and an optional Cloudflare Turnstile adapter.

## Roles

| Role | Summary |
| --- | --- |
| Super Administrator | Everything, including users and roles |
| Administrator | All content, careers, inbox and settings; cannot manage Super Administrators |
| Editor | Creates, reviews and publishes content; manages media, pages, navigation and SEO |
| Author | Writes own articles and submits them for review; cannot publish |
| Careers Manager | Job listings, applications (including CVs) and exports |
| Read-only Analyst | Read-only access to content and enquiry volumes; no applicant data |

Custom roles can be created in **Admin → Roles & permissions**.

## Tests

```bash
php artisan test
```

The suite (feature and unit tests) covers public rendering, SEO output, sitemap/robots, authentication, 2FA, role restrictions, the article workflow and scheduler, all CMS modules, media validation, careers and private CV access, enquiries, CSRF, rate limiting, spam protection and the security helpers.
