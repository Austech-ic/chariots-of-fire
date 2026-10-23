# Flaming Chariot Outreach

Ministry website, staff CMS and member portal for Flaming Chariot Outreach,
built with **Laravel 13**, **Filament 5** (admin), Blade + Tailwind CSS 4.
Designed to install on ordinary **shared hosting** (cPanel) as well as a VPS.

| Area | What it does |
| --- | --- |
| Public website | Light (default) and dark themes, cinematic homepage slideshow, about/mandates/founder, live streaming, sermon library (audio, video, YouTube), programmes with registration & calendar files, gallery, counselling, giving, contact, newsletter — multilingual |
| Staff CMS (`/admin`) | Draft → review → scheduled → published workflow, media library, homepage manager, sermons, livestreams, events, galleries, confidential counselling, appointments, donations & reconciliation, newsletter consent, members, translations, settings, audit log |
| Member portal (`/member`) | Verified accounts, appointments, event registrations, giving history, member-only resources, SMS/WhatsApp/newsletter preferences, account/data requests |

## Quick start (development)

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# local defaults: SQLite + test payment gateway
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/; s/^APP_ENV=.*/APP_ENV=local/; s/^APP_DEBUG=.*/APP_DEBUG=true/; s/^APP_INSTALLED=.*/APP_INSTALLED=true/; s/^PAYMENT_PROVIDER=.*/PAYMENT_PROVIDER=test/' .env
touch database/database.sqlite
php artisan migrate --seed
php artisan db:seed --class=DemoContentSeeder   # optional sample content
php artisan fco:create-admin you@example.org     # prompts for a password
php artisan serve
```

Open http://localhost:8000 and http://localhost:8000/admin. Run
`php artisan schedule:work` in another terminal to process queued email and
image jobs.

## Production

- **Shared hosting:** follow [docs/SHARED_HOSTING.md](docs/SHARED_HOSTING.md).
  `scripts/build-release.sh` produces an upload-ready zip; a web installer at
  `/install` does the rest; one cron entry runs all background work.
  Updates: `scripts/build-release.sh <version> --update`, extract over the site,
  then *System status* → *Run database updates* / *Clear caches*.
- **Architecture, security model and PRD traceability:** [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).
- **Staff guide to the CMS:** [docs/STAFF_GUIDE.md](docs/STAFF_GUIDE.md).

## Tests

```bash
php artisan test
```

The suite (93 tests) maps to the PRD acceptance cases FCO-001 … FCO-025 and
the security cases in §10.2, and passes on SQLite, MySQL/MariaDB and
PostgreSQL. CI runs it on PHP 8.3/8.4 and MySQL 8, audits dependencies and
checks that committed frontend assets are up to date.

## Useful commands

| Command | Purpose |
| --- | --- |
| `php artisan fco:create-admin email --role=super-admin` | Create/promote a staff account securely |
| `php artisan fco:backup [--media]` / `fco:restore file.zip` | Encrypted portable backup / restore |
| `php artisan fco:reconcile-donations` | Verify pending/paid donations with the provider |
| `php artisan fco:generate-occurrences` | Expand recurring events |
| `php artisan fco:sync-streams` | Refresh live status from YouTube |
| `php artisan fco:apply-retention` | Apply the personal-data retention schedule |
