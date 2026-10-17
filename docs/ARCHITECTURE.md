# Architecture, security model and PRD traceability

## Stack

| Concern | Choice | Notes |
| --- | --- | --- |
| Framework | Laravel 13 (PHP 8.3+) | Modular monolith: public site, CMS and portal share one app and database |
| Admin UI | Filament 5 | `app/Filament`, permission-guarded resources, built-in authenticator-app 2FA |
| Public UI | Blade + Tailwind CSS 4 + ~200 lines of vanilla JS | No JS framework; works without JavaScript; strict CSP (`script-src 'self'` + nonce) |
| Database | MySQL/MariaDB (default), PostgreSQL or SQLite | Tested on all three |
| Queue | `database` driver, processed by the scheduler | No long-running worker needed on shared hosting |
| Sessions / cache | `file` | Swap to Redis on a VPS |
| Media | Private `media` disk (`storage/app/media`) served by `MediaController` | No `public/storage` symlink required; private files never web-reachable |
| Images | Intervention Image (GD) → WebP `sm/md/lg` variants | EXIF/GPS stripped by re-encoding |

## Code map

```
app/
  Console/Commands/      scheduled jobs: publish, reconcile, reminders, occurrences, retention, backup/restore
  Enums/ContentStatus    Draft / In review / Scheduled / Published / Archived
  Filament/              CMS resources, Fields helpers, site-settings page, dashboard widget
  Http/Controllers/      public site, member portal (Member/), installer, webhooks, read-only API v1
  Http/Middleware/       SetLocale, SecurityHeaders (CSP), EnsureAccountActive, RedirectIfNotInstalled
  Jobs/                  media processing, receipts, newsletter sync, SMS/WhatsApp delivery
  Models/                Eloquent models; Concerns/ = HasTranslations, Publishable, Auditable, HasRevisions
  Notifications/         email notifications (logistics only — never counselling content)
  Policies/              confidential/financial authorization (counselling, appointments, donations)
  Services/
    Booking/             AvailabilityService (slot calculation), BookingService (transactional booking)
    Media/               MediaLibrary (content-sniffed validation, server-side names)
    Messaging/           SMS/WhatsApp drivers: log, Termii, Twilio, WhatsApp Cloud API
    Payments/            PaymentGateway contract; Paystack, Flutterwave, Test; DonationService
    Streaming/           StreamProvider contract; YouTube, Vimeo, allow-listed embed URL
  Support/               Locales, Settings, Audit, Money, Permissions catalogue, Installer
config/fco.php           all application settings (env-driven)
```

## Key design decisions

**Translations.** Editorial content lives in `<entity>_translations` tables
with a unique `(parent_id, locale)` constraint and an *approved* flag.
`$model->tr('title')` returns the approved translation for the current locale
and falls back to the default language. Interface strings use Laravel's
`__()` (add `lang/<code>.json`). The default language has no URL prefix;
other enabled languages use `/{locale}/…`, with `hreflang` alternates and a
sitemap per language. Translation completeness is shown in CMS tables.

**Publishing workflow.** All publishable content shares the `Publishable`
trait. Staff without `content.publish` can only save *Draft* or *In review*
(enforced in the model, not just the form). The default-language version is
mandatory. Every save keeps a JSON revision; create/update/delete is audited
(field names only, never values).

**Media visibility.** Uploads are private. When content referencing an asset
becomes publicly visible (including scheduled content going live), the asset
is marked public. Members-only sermons and member resources stay private and
are streamed only to authorised users.

**Streaming.** Providers implement `StreamProvider`. Public embeds use
`youtube-nocookie.com`. The header shows *Watch Live* only when the stream's
status is `live`, set either by an administrator or by the YouTube Data API
(server-side key, cached, quota-aware) — never guessed.

**Payments.** `DonationService` creates a pending donation and a separate
`payment_attempt` with its own provider reference, then redirects to the
provider's hosted checkout (no card data touches the site). Callbacks and
webhooks only *trigger* a server-to-server verification; the result is
applied inside a transaction with row locks, so duplicate webhooks, retries
and races produce exactly one state transition. Amount/currency mismatches
are flagged, never marked paid. A scheduled reconciliation verifies pending
donations, abandons stale ones and cross-checks recent paid ones. Receipts are
idempotent. Refunds need `donations.refund` and are audited.

**Booking.** `AvailabilityService` derives slots from weekly rules, leave and
blocked periods, existing appointments plus buffers, minimum notice and
booking horizon, in the counsellor's time zone (stored in UTC, displayed in
Africa/Lagos). `BookingService` locks the counsellor row
(`SELECT … FOR UPDATE`) and re-checks availability inside the transaction, so
two visitors can never confirm the same slot. Path A (request → coordinator
offer → visitor accepts) and Path B (self-service, auto-confirm or pending
approval per service) are both supported; staff overrides outside availability
are audited. Visitors manage bookings via unguessable emailed tokens.

**Shared hosting.** One cron entry runs the scheduler, which also drains the
queue (`queue:work --stop-when-empty`). Built assets are committed. The web
installer creates `.env`, migrates, seeds and creates the first administrator,
then locks itself. A root `.htaccess` supports hosts that cannot point the
document root at `public/`. Backups need no shell tools.

## Security model (PRD §9)

| Control | Implementation |
| --- | --- |
| Roles & permissions | `roles`, `permissions`, pivots; catalogue in `App\Support\Permissions`; every CMS resource checks a permission in `BaseResource` |
| Separation of duties | General *Administrator* has no counselling or finance permissions; only super-admins can grant roles carrying them or edit roles |
| Counselling confidentiality | Narrative, contact data, availability and notes encrypted at rest (`encrypted` casts, APP_KEY); policy limits access to coordinators and the assigned counsellor; record views audited; staff alerts and visitor emails carry no narrative; no bulk export; excluded from search |
| Member data / IDOR | All portal queries scoped to the signed-in user; resources check grants; appointments accessible only to the owner or token holder |
| Authentication | Email verification, hashed passwords, rate-limited login/reset/forms, authenticator-app 2FA for staff (optionally enforced), suspension signs users out |
| Payments | Hosted checkout only, server verification, HMAC/secret-checked webhooks, idempotent processing, no secrets in logs |
| Uploads | Allow-listed MIME types detected from file contents, extension must match, size limits, random server filenames, private storage, `nosniff` |
| Web | CSRF on all forms (webhooks excepted and signature-checked), escaped output, CSP with nonce, HSTS on HTTPS, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, honeypots on public forms |
| Privacy | Consent records for newsletter and SMS/WhatsApp (per channel and purpose), double opt-in, signed unsubscribe links, immediate opt-out, retention job, account deletion/export requests, locally generated staff avatars |

Application-level encryption uses `APP_KEY`. **Back up `.env` securely**: if
the key is lost, encrypted counselling data cannot be recovered. To rotate the
key, use Laravel's `APP_PREVIOUS_KEYS` so old data stays readable.

Legal applicability of the Nigeria Data Protection Act 2023 must be confirmed
by qualified counsel; the controls above support but do not by themselves
establish compliance.

## PRD acceptance traceability (§10.1)

| ID | Test |
| --- | --- |
| FCO-001/002 | `PublicSiteTest::test_published_slide_appears_within_its_window`, `test_future_slide_stays_hidden_until_start` |
| FCO-003…007 | `SermonTest` (audio, YouTube embed, non-embeddable fallback, permitted download, private media denied) |
| FCO-008/009 | `EventTest::test_events_list_chronologically…`, `test_cancelled_event_shows_notice…` |
| FCO-010 | `EventTest::test_gallery_album_shows_published_images_in_order` |
| FCO-011/012 | `ContactAndNewsletterTest` |
| FCO-013…016 | `CounsellingTest` (encrypted request, booking, double booking, reschedule/cancel) |
| FCO-017…019 | `DonationTest` (pending checkout, failed payment, duplicate webhooks) |
| FCO-020/021 | `ContactAndNewsletterTest` (double opt-in, unsubscribe) |
| FCO-022 | `PublicSiteTest::test_language_switching_with_fallback` |
| FCO-023 | `MemberPortalTest::test_member_only_resource_requires_authentication` |
| FCO-024 | `AdminAccessTest` (editor/administrator/counsellor boundaries) |
| FCO-025 | Manual: `fco:backup` → `fco:restore` round trip verified on SQLite and MySQL; repeat quarterly on the production host |

Security cases §10.2 are covered by `MemberPortalTest`, `AdminAccessTest`
(uploads, permissions), `DonationTest` (invalid signature, forged redirect)
and `ContactAndNewsletterTest` (rate limiting, honeypot).

## Decisions still needed from the ministry (PRD §15)

| Decision | Current default |
| --- | --- |
| Hosting provider and plan | Works on cPanel shared hosting; VPS recommended for higher traffic |
| Payment provider | Paystack or Flutterwave adapter ready; needs an approved merchant account |
| Additional languages | English enabled; French, Yoruba, Igbo, Hausa seeded but disabled |
| SMS / WhatsApp provider | Disabled; Termii, Twilio and WhatsApp Cloud adapters ready |
| Newsletter platform | Consent stored locally; Mailchimp or Brevo sync available |
| Recurring giving | One-time giving implemented; recurring needs provider subscription plans |
| Retention periods | Defaults in `.env` (`RETENTION_*`) pending legal review |
| Content | Founder biography, mandates, privacy notice and photography to be supplied and approved |
