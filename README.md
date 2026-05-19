# NIMR Internal Travel Permit System

A web-based travel permit management system for the **National Institute for Medical Research (NIMR)**, Tanzania. Digitizes the official NIMR-ADM-F002 travel authorization form and enforces the multi-step approval workflow across all organizational units.

## Features

- Multi-step travel request form (Sections A–G, matching NIMR-ADM-F002)
- Role-based approval chain computed automatically at submission time
- Support for all unit types: HQ standalone, HQ directorate, HQ section, and research centres
- Approve, return for revision, or reject at each stage
- Queued email notifications to approvers and requesters
- System Administrator user management, including centre-scoped administrators
- Bilingual interface (English / Swahili)
- Printable permit output

## Tech Stack

- **Backend:** Laravel 12, PHP
- **Frontend:** Blade, Alpine.js, Tailwind CSS, Vite
- **Database:** SQLite (development) / MySQL or PostgreSQL (production)
- **Queue:** Database-backed (Laravel queues)

## Setup

```bash
# Full setup from scratch
composer setup

# Development (server + queue + Vite, concurrently)
composer dev

# Run tests
composer test
```

Individual commands:

```bash
php artisan serve       # Dev server at localhost:8000
php artisan migrate     # Run migrations
npm run dev             # Vite dev server only
npm run build           # Production asset build
```

## Roles

| Role | Description |
|---|---|
| `staff` | Regular employee — creates travel requests |
| `head` | Section head — approves requests from section staff |
| `manager` | Unit manager — approves requests from unit staff |
| `director` | Directorate director — approves requests from managers/heads |
| `centre_manager` | Research centre manager — approves centre requests |
| `director_general` | DG — final authority for most chains |
| `hr` | HR - receives request copies for records |
| `system_admin` | Manages user identity, unit placement, and role assignment |

## Approval Chain

The chain is computed at submission based on the traveller's unit type and role. See `app/Services/ApprovalChainService.php` for the full matrix.

## Environment

Copy `.env.example` to `.env` and set:

- `APP_NAME` — application name shown in the UI
- `APP_URL` — base URL
- `MAIL_MAILER` — mail driver (`log` for development, `smtp` for production)
- `DB_*` — database credentials (if switching from SQLite)

## License

Internal use only — National Institute for Medical Research, Tanzania.
