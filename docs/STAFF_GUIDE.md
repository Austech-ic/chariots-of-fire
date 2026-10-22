# Staff guide to the CMS

Sign in at **/admin**. On first sign-in open your profile (top-right avatar)
and enable **two-factor authentication** with an authenticator app.

Visitors see the website in **light mode** by default and can switch to dark
mode with the moon/sun button in the header. The CMS also opens in light mode;
change it from your user menu (top right).

## Roles

| Role | Can do |
| --- | --- |
| Content editor | Pages, homepage, mandates, sermons, events, galleries, media, translations — saves as draft or *In review* |
| Publisher | Everything an editor can, plus publish, schedule and archive |
| Counselling coordinator | Read and assign all counselling requests, propose times, manage appointments, services and availability |
| Counsellor | Read only the requests and appointments assigned to them |
| Finance administrator | View donations, reconcile, export, refund |
| Communications | Newsletter subscribers and contact enquiries |
| Administrator | Website, members, settings, staff accounts, audit log — **no** counselling or finance access |
| Super administrator | Everything, including roles |

## Publishing content

1. Create the record and write the **English** version (required). Add other
   languages with *Add a translation*; untick *Approved* for drafts.
2. Choose images from the media picker or upload one with **+**. Always fill
   in *Alternative text*. Media stays private until the content is published.
3. Set *Status*: Draft → In review → **Scheduled** (with a date) or **Published**.
4. Every save is kept as a revision and recorded in the audit log.

**Homepage:** *Homepage slideshow* (at least three approved slides with wide
photographs), *Homepage sections* (drag to reorder, toggle visibility, edit text).

**Sermons:** paste a YouTube link under *YouTube / video links* — no need to
upload a copy. Upload audio files under *Uploaded audio…* and tick *Allow
download* only for content cleared for download. *Members only* hides the
sermon from the public.

**Live streams:** create the broadcast with its YouTube link. At start time use
**Mark as live** (or choose *Check the provider automatically* when a YouTube
API key is configured). Use **End broadcast** afterwards and link the
recording sermon.

**Events:** set start/end times in Lagos time, choose in-person/online/hybrid,
enable registration and capacity if needed. For a weekly programme use
*Repeats* and *Generate upcoming dates*. Postponing or cancelling shows a
notice on the event page.

**Gallery:** create the album, save, then use **Bulk upload photos** (up to
100 at a time). Drag photos to reorder; add captions and alt text.

## Counselling (confidential)

- New requests appear under *Counselling → Requests* with a badge. Opening a
  request is logged.
- **Assign counsellor**, then **Propose a time**: the visitor receives an email
  link to accept or ask for another time. Use *Override availability* only
  when a time outside normal hours was agreed — it is logged.
- Add **notes** (encrypted) and **Close** the request when finished.
- *Appointments*: confirm pending bookings, reschedule, cancel, mark completed
  or no-show. Visitors receive logistics only, never the content of their request.
- *Counsellors & availability*: weekly hours, leave and blocked time per counsellor.
- Never copy counselling details into email, WhatsApp or documents outside the CMS.

## Giving

- Donations are created by the website and **cannot be edited**. A donation is
  *Paid* only after the payment provider confirmed it.
- *Verify with provider* re-checks a pending or failed donation.
- *Reconciled: mismatch* means the provider reports a different amount —
  investigate before acknowledging the gift.
- *Refund* (finance role) sends the refund through the provider and records it.
- *Export CSV* for bookkeeping (logged in the audit log).

## Members and communications

- *Members*: suspend/reactivate accounts (suspension signs them out), grant
  access to restricted member resources.
- *Account requests*: deactivation/deletion/data requests from members — act on
  them and mark completed.
- *Newsletter subscribers*: consent records with source and date. *Suppress*
  permanently blocks an address. Export active subscribers for the email platform.
- *Contact enquiries*: assign and update status.

## System

- *Site settings*: name, tagline, contact details, social links, counselling and
  giving text, donation presets and currencies.
- *Languages*: enable a language to publish it under `/<code>/…`; content
  without an approved translation falls back to English.
- *Staff accounts*: new staff receive an email to set their own password.
- *Audit log* / *Webhook log*: who did what, and payment notifications received.
