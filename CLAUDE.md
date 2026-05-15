# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full setup from scratch
composer setup

# Development (runs server, queue, logs, and Vite concurrently)
composer dev

# Run tests
composer test

# Individual commands
php artisan serve          # Dev server at localhost:8000
php artisan migrate        # Run migrations
npm run dev                # Vite dev server only
npm run build              # Production asset build
```

## Architecture

**Laravel 12 + Breeze** with SQLite (default), Tailwind CSS, Alpine.js, and Vite.

### Domain Model

The core domain is a multi-step travel permit approval workflow:

- **Unit** — Organizational unit (hq/centre/station) with self-referential parent hierarchy. Each unit owns its own ApprovalRoute.
- **ApprovalRoute** → **ApprovalStep[]** — Defines the ordered approval chain for a unit (e.g., supervisor → director → finance → authorizer). Steps reference a `role_key` that determines which user role must act.
- **TravelRequest** — Created by a requester, bound to a Unit and an ApprovalRoute. Tracks `status` (draft/pending/returned/approved/rejected/cancelled) and `current_step_id` to know where in the workflow it sits. Full form data is serialized in `form_payload` (JSON).
- **ApprovalAction** — Audit log of every approve/reject/return action, linked to the TravelRequest and the specific ApprovalStep acted upon.

### Roles

Defined in `config/travel_permit.php`: `requester`, `supervisor`, `director`, `finance`, `authorizer`, `admin`. Stored on the `users.role_key` column. Approval steps reference these role keys to enforce who can act at each step.

### Key Controllers

| Controller | Responsibility |
|---|---|
| `TravelRequestController` | CRUD + auto-generates request numbers, resolves approval route on store |
| `ApprovalRouteController` | Manages approval chains and their ordered steps |
| `UnitController` | Manages org units; sorts by type (HQ first) then name |
| `DashboardController` | Aggregates stats; handles missing tables gracefully |

### Frontend

Blade templates in `resources/views/`. Reusable UI primitives are in `resources/views/components/` (inputs, buttons, modal, dropdown). Page views are organized under `travel-requests/`, `units/`, `approval-routes/`, `profile/`, `auth/`. The main authenticated layout is `layouts/app.blade.php`.

### Configuration

`config/travel_permit.php` is the canonical source for domain enums (unit types, roles, statuses). Reference this file rather than hardcoding strings elsewhere.
