# Flaming Chariot Outreach — notes for contributors and AI agents

Laravel 13 + Filament 5 app that must keep working on **shared hosting**. Read
`docs/ARCHITECTURE.md` before larger changes.

## Commands
- Tests: `php artisan test` (SQLite in memory; must also pass on MySQL — CI runs both).
- Assets: `npm run build` and **commit `public/build`** (hosts have no Node.js).
- Release zip: `scripts/build-release.sh <version>`.

## Rules
- Shared-hosting constraints: no long-running processes (queue is drained by the
  scheduler), no symlinks (media via `MediaController`), no shell tools at runtime,
  no `env()` outside `config/`.
- Every Filament resource extends `App\Filament\Resources\BaseResource` and sets a
  `$permission`; confidential/financial models also have a policy in `app/Policies`.
  Filament closures receive arguments **by name** (`$query`, `$record`, `$data`, `$get`).
- Counselling content (narrative, contact data, notes) must never appear in emails,
  logs, audit metadata, exports or notifications — logistics only.
- Donations change state only through `DonationService` after provider verification.
- Bookings go through `BookingService` (transaction + counsellor row lock).
- Editorial content uses `*_translations` tables via `HasTranslations`; render with
  `$model->tr('field')` and link with `lroute()` / `localize_url()`.
- Store money as integer minor units (`App\Support\Money`), instants in UTC,
  display in `config('app.display_timezone')` (Africa/Lagos).
- Public pages: no inline scripts or event handlers (CSP); vanilla JS in `resources/js/app.js`.
- Never invent ministry facts (biographies, history); use clearly marked placeholders.
