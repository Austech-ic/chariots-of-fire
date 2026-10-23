# Installing on shared hosting (cPanel, DirectAdmin, Plesk)

The application is a standard Laravel 13 app built to run on ordinary shared
hosting: no SSH, Node.js, Redis or long-running processes are required.
Everything else (VPS, managed Laravel hosting) is a simpler variant of this.

## 1. Check the hosting plan

| Requirement | Where to check / fix |
| --- | --- |
| PHP **8.3 or newer** | cPanel → *Select PHP Version* / *MultiPHP Manager* |
| Extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `gd`, `intl`, `zip`, `curl` | Same screen → *Extensions* tab |
| MySQL 8 / MariaDB 10.6+ (PostgreSQL also works) | cPanel → *MySQL Databases* |
| Cron jobs | cPanel → *Cron Jobs* |
| HTTPS certificate | cPanel → *SSL/TLS Status* (AutoSSL / Let's Encrypt) |
| Upload limit ≥ 64 MB (for sermon audio) | *Select PHP Version* → *Options*: `upload_max_filesize`, `post_max_size` |
| Outbound HTTPS to your payment, email and messaging providers | Ask the host if unsure |

The installer re-checks PHP and extensions and tells you exactly what is missing.

## 2. Build or download the release zip

On any computer with PHP, Composer and Node.js:

```bash
git clone https://github.com/Austech-ic/chariots-of-fire.git && cd chariots-of-fire
scripts/build-release.sh 1.0.0      # → release/flaming-chariot-1.0.0.zip
```

The zip already contains `vendor/` and the compiled CSS/JS, so the server
never needs Composer or Node.js.

## 3. Upload

1. In *File Manager*, upload the zip **above** `public_html` (for example to
   `/home/USER/flaming-chariot`) and extract it there.
2. Point the website at the `public` folder — pick the first option your host allows:
   - **Recommended:** *Domains* → edit the domain → set *Document Root* to
     `/home/USER/flaming-chariot/public`.
   - **Addon/sub-domain:** create it with that document root directly.
   - **Cannot change the document root:** extract into `public_html` itself. The
     included root `.htaccess` routes everything into `public/` and blocks every
     application folder and secret file. (Requires Apache `mod_rewrite`, which
     virtually all shared hosts have.)
3. Make sure `storage/` and `bootstrap/cache/` are writable (permissions `775`;
   the installer warns if not).

## 4. Create the database

cPanel → *MySQL Databases*: create a database and a user, then *Add User To
Database* with **ALL PRIVILEGES**. Note the names — cPanel prefixes them,
e.g. `user_fco` and `user_fcoadmin`.

## 5. Run the web installer

Visit your site. On first load the app creates `.env` with a fresh random key
and redirects to **`/install`**. Enter:

- the website address (`https://…` once SSL is active),
- the database details from step 4,
- the sender email address (create the mailbox first), and
- the first administrator account (strong password, 12+ characters).

Optionally tick *Load sample content* to preview the design; it is clearly
labelled and should be replaced before launch.

The installer runs the migrations, seeds roles/languages/reference data,
creates the administrator and then **locks itself** (`/install` returns 404).

> **No web installer wanted?** With SSH: `cp .env.example .env`, edit it,
> then `php artisan key:generate && php artisan migrate --force && php artisan db:seed --force && php artisan fco:create-admin you@example.org`.
> Set `APP_INSTALLED=true` in `.env`.

## 6. Add the one cron job (required)

cPanel → *Cron Jobs* → *Once Per Minute*:

```
* * * * * /usr/local/bin/php /home/USER/flaming-chariot/artisan schedule:run >> /dev/null 2>&1
```

(Use the PHP path your host documents, e.g. `/opt/cpanel/ea-php83/root/usr/bin/php`.)

This single entry drives everything that runs in the background:

| Task | Frequency |
| --- | --- |
| Queue: emails, receipts, image processing, newsletter sync, SMS/WhatsApp | every minute (`queue:work --stop-when-empty`) |
| Publish scheduled content | every minute |
| Live-stream status from YouTube (if API key set) | every 2 minutes |
| Donation reconciliation with the payment provider | every 15 minutes |
| Appointment reminders | every 15 minutes |
| Recurring event dates | daily |
| Encrypted backup | daily 03:15 |
| Data-retention clean-up | daily |

On a VPS you may instead run `php artisan queue:work` under Supervisor; keep the cron line for the scheduler.

## 7. Configure integrations in `.env`

Edit `.env` in *File Manager* (it is never web-accessible). Every option is
documented in `.env.example`. Key items:

**Email (required)** — use the SMTP details of the mailbox from step 5 or a
transactional email service:

```
MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain.org
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=office@your-domain.org
MAIL_PASSWORD=…
```

**Payments** — choose one approved provider:

```
PAYMENT_PROVIDER=paystack
PAYSTACK_SECRET_KEY=sk_live_…
PAYMENT_FALLBACK_EMAIL=giving@your-domain.org   # receipt address for anonymous gifts
```

In the provider dashboard set the webhook URL to
`https://your-domain.org/webhooks/payments/paystack` (or `/flutterwave`; for
Flutterwave also set `FLUTTERWAVE_WEBHOOK_HASH` to the *secret hash* you
configure there). Donations are only marked paid after server-side
verification, and the reconciliation job catches any missed webhooks.

**YouTube** (optional) — `YOUTUBE_API_KEY` enables automatic live detection;
embeds work without it.

**SMS / WhatsApp** (optional) — `SMS_DRIVER=termii|twilio`,
`WHATSAPP_DRIVER=whatsapp_cloud|twilio` plus the matching credentials.
WhatsApp business-initiated messages require Meta-approved templates mapped in
`config/fco.php`. Only people who opted in receive messages.

**Backups** — set `BACKUP_PASSWORD` so nightly backups are AES-256 encrypted.

After editing `.env`, nothing needs restarting. If you later run
`php artisan config:cache`, run it again after every `.env` change.

## 8. Go-live checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`
- [ ] SSL active; uncomment the *Force HTTPS* lines in `public/.htaccess`; `SESSION_SECURE_COOKIE=true`
- [ ] Cron job running (CMS dashboard → no stuck emails; `storage/logs` free of errors)
- [ ] Test email delivered (password reset on your own account)
- [ ] Payment provider in live mode, webhook URL set, a real small donation verified and refunded
- [ ] Every administrator has enabled two-factor authentication (CMS → profile). Optionally enforce with `REQUIRE_STAFF_MFA=true`
- [ ] Sample content removed; privacy notice, about page and founder biography approved and published
- [ ] Off-server backup routine in place (download `storage/app/backups/*.zip` or use the host's backup feature) and one restore tested

## Updating

Updates come as `flaming-chariot-<version>-update.zip`
(`scripts/build-release.sh <version> --update`). It contains no `.env` and no
`storage/` folder, so extracting it cannot overwrite your settings, uploads,
logs or backups. No SSH is needed:

1. **Back up first:** CMS → *System* → *System status* → **Back up now**
   (or `php artisan fco:backup --media`, or the host's backup tool). Also keep
   a copy of `.env`.
2. In *File Manager*, upload the update zip into the folder that holds the site
   (the one containing `.env`, `app/` and `public/`) and **Extract**, allowing it
   to overwrite existing files.
3. Sign in to the CMS and open *System* → *System status*:
   - If it shows **Run database updates**, click it (super administrators only).
   - Click **Clear caches**.
4. Check the health list on the same page and the public website.

Via SSH the equivalent is `php artisan down`, extract, `php artisan migrate
--force`, `php artisan optimize:clear`, `php artisan up`.

The *System status* page also shows whether the cron job is running, queued
work, email/payment configuration, HTTPS, debug mode and the latest backup,
and can send a test email or process the queue on demand.

## Backups and restore

`php artisan fco:backup [--media]` writes `storage/app/backups/fco-backup-*.zip`
(data-only SQL + optional media; 14 kept by default). It needs no `mysqldump`.

To restore onto a fresh server: install the same release, then
`php artisan fco:restore path/to/fco-backup-….zip` (same `BACKUP_PASSWORD`).
Without SSH, extract `database.sql` with the password and import it with
phpMyAdmin into a database that has been migrated by the installer.

Initial targets from the PRD: recovery within 8 hours, at most 24 hours of
data loss. Increase backup frequency (edit `routes/console.php`) if donation
or counselling volume needs a tighter recovery point.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| 500 error on first visit | `storage/` or `bootstrap/cache/` not writable, or PHP < 8.3 |
| "419 Page expired" on forms | `SESSION_SECURE_COOKIE=true` while the site is still on plain http |
| Emails never arrive | Cron job missing (queue not processed) or wrong SMTP settings; see `storage/logs/` |
| Uploaded images show a dark placeholder | Processing pending: check the cron job; failed items can be retried in *Media library* |
| Donations stay "pending" | Webhook URL/secret wrong; reconciliation will still verify them every 15 minutes |
| Page shows raw files / directory listing | Document root not pointing at `public/` and `mod_rewrite` disabled |
