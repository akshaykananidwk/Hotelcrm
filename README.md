# HotelCRM ERP — Hotel Management ERP + PMS + Channel Manager + Booking Engine

A modular, multi-hotel (SaaS-ready) Hotel Management platform built with **pure PHP 8**, **MySQL**, **Bootstrap 5** and a custom MVC framework — **no Laravel, Node, Python, or other frameworks**. It ships a Property Management System, CRM, GST billing, an OTA Channel Manager, a website booking engine, a REST API, and pluggable integration points for payments, WhatsApp, email and MikroTik guest Wi-Fi.

> Default login after seeding: **admin@hotelcrm.test / Admin@123**

---

## Highlights

- **Custom PHP MVC** — router, PDO data layer (prepared statements everywhere), views, services.
- **Security by construction** — bcrypt password hashing, CSRF tokens, XSS escaping, prepared statements, rate limiting, brute-force lockout, secure file uploads, audit + login logs.
- **RBAC** — Super Admin, Hotel Owner, Manager, Receptionist, Accountant, Housekeeping.
- **Multi-hotel / multi-branch** — every business table is hotel-scoped.
- **Pluggable integrations** — add a payment gateway, OTA connector or notification channel by implementing an interface and registering it; the core never changes.
- **REST API** with bearer-token auth + a public booking-engine API.
- **Async Channel Manager** with a retrying job queue.
- **Dependency-free PDF invoices** and a pure-PHP SMTP client.

---

## Requirements

- PHP **8.1+** with `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `openssl`
- MySQL **5.7+** / MariaDB **10.3+**
- Apache (with `mod_rewrite`) or Nginx

---

## Quick Start

### Option A — Installation Wizard (recommended)

1. Point your web root at the project directory.
2. Visit `http://your-host/install/` and follow the steps (requirements check → DB credentials → admin account).
3. Delete the `/install` directory when finished.

### Option B — CLI

```bash
cp config/config.sample.php config/config.php   # edit DB credentials
php cli/migrate.php --seed                       # create schema + sample data
php -S localhost:8000 index.php                  # dev server
```

Open `http://localhost:8000` and sign in.

### Background worker (Channel Manager queue)

```bash
* * * * * php /path/to/cli/worker.php >> storage/logs/worker.log 2>&1
```

---

## Folder Structure

```
Hotelcrm/
├── index.php               # front controller
├── bootstrap.php           # autoloader + config + kernel boot
├── routes.php              # route table (web + REST + public)
├── config/                 # config.sample.php (copy to config.php)
├── app/
│   ├── Core/               # framework: Router, Database, Auth, View, Csrf, ...
│   ├── Controllers/        # web + Api/ controllers
│   ├── Models/             # data models (extend Core\Model)
│   ├── Services/           # BookingService, ReportService, PdfService, BackupService
│   │   ├── Notification/   # channels: WhatsApp, Email, InApp + NotificationManager
│   │   ├── Payment/        # PaymentGateway interface + Razorpay/PayU/PhonePe/Cash
│   │   ├── OTA/            # OtaConnector interface + Booking.com/generic + ChannelManager
│   │   └── Mikrotik/       # RouterOS API client for guest Wi-Fi
│   ├── Middleware/         # AuthMiddleware, ApiAuthMiddleware
│   ├── Views/              # PHP templates (Bootstrap 5 admin theme)
│   └── Helpers/            # global template helpers
├── database/               # schema.sql, seed.sql, migrations/
├── cli/                    # migrate.php, worker.php
├── install/                # web installation wizard
├── public/                 # css / js / uploads
├── storage/                # logs / cache / backups
├── tests/                  # framework logic tests (no external deps)
└── docs/                   # API.md, DEPLOYMENT.md, ER-DIAGRAM.md
```

---

## Modules

| # | Module | Status |
|---|--------|--------|
| 1 | Hotel management (hotels, floors, room types, rooms, amenities, inventory) | ✅ |
| 2 | Reservations (walk-in, website, OTA, corporate, group; calendar) | ✅ |
| 3 | Front office (check-in/out, room change, KYC upload, guest timeline) | ✅ |
| 4 | Housekeeping (statuses, tasks, assignment, lost & found) | ✅ |
| 5 | Billing (GST invoices, POS lines, payments, refunds, PDF, email) | ✅ |
| 6 | CRM (guest DB, loyalty, history, feedback) | ✅ |
| 7 | OTA Channel Manager (async queue + connectors) | ✅ (connectors need partner creds) |
| 7b | **OTA web automation** (MakeMyTrip/Goibibo without API — cURL session or real headless browser) | ✅ (see `docs/AUTOMATION.md`) |
| 8 | Website booking engine (public API + promos) | ✅ |
| 9 | Payment gateways (Razorpay, PayU, PhonePe, Cash) | ✅ (keys in settings) |
| 10 | WhatsApp automation (bulk.akdwk.in gateway) | ✅ |
| 11 | Email automation (SMTP + PDF attachments) | ✅ |
| 12 | MikroTik guest Wi-Fi (RouterOS API) | ✅ (router creds in settings) |
| 13 | Reports (occupancy, ADR, RevPAR, GST, ... ; CSV/Excel/PDF) | ✅ |
| 14 | Dashboard (KPIs, charts, arrivals/departures) | ✅ |
| 15 | Notification center (unified engine) | ✅ |
| 16 | REST API layer (token auth) | ✅ |
| 17 | Admin settings (SMTP, WhatsApp, OTA, payments, MikroTik, API keys, backup) | ✅ |
| 18 | Security (hashing, CSRF, XSS, rate limit, uploads, logs) | ✅ |
| 19 | Performance (pagination, caching, queue, prepared statements) | ✅ |
| 20 | Dev tooling (schema, migrations, installer, docs, tests) | ✅ |

> Where an OTA / payment gateway requires partner approval, the integration point and configuration screen exist; add credentials in **Settings** once granted. See `docs/` for details.

---

## Extending

- **New payment gateway:** implement `App\Services\Payment\PaymentGateway`, register it in `PaymentManager::GATEWAYS`.
- **New OTA (API):** extend `App\Services\OTA\AbstractOtaConnector`, register it in `ChannelManager`.
- **New OTA (no API):** extend `App\Services\OTA\WebAutomationConnector` with default flows, register it in `ChannelManager`; operators configure selectors in Settings. See `docs/AUTOMATION.md`.
- **New notification channel:** implement `App\Services\Notification\NotificationChannel`, add it to `NotificationManager::channelFor()`.

## Tests

```bash
php tests/run.php
```

## License

Proprietary — © the project owner. See `docs/DEPLOYMENT.md` for production hardening.
