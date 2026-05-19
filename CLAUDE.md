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

The core domain is a multi-step travel permit approval workflow based on the official NIMR form NIMR-ADM-F002:

- **Unit** — Organizational unit with four types: `hq_standalone`, `hq_directorate`, `hq_section`, `research_centre`. Self-referential parent hierarchy (`parent_id`). HQ sections belong to a directorate parent.
- **TravelRequest** — Created by a requester, bound to a Unit. Tracks `status` and `current_approver_id`. Full form data stored as columns (sections B–G). Approval chain serialized in `approval_chain` JSON computed at submission time by `ApprovalChainService`. Status constants are class constants on the model.
- **ApprovalAction** — Audit log of every approve/reject/return action, recording stage, actor, decision, comment, and timestamp.

### Statuses (TravelRequest constants)

`draft` → `pending` → `approved` | `rejected` | `returned` | `cancelled`

- `returned` — approver sent the request back for revision; requester can edit and resubmit
- `cancelled` — requester cancelled; terminal state

### Roles

Eight roles on `users.role`: `staff`, `head`, `manager`, `director`, `centre_manager`, `director_general`, `hr`, `system_admin`.

Role helpers on User model: `isDirectorGeneral()`, `isCentreManager()`, `isHr()`, `isApprover()`.

### Approval Chain Service

`ApprovalChainService::buildChain(User $traveller)` computes the ordered chain at submission time based on unit type and role:

| Unit Type | Traveller Role | Chain |
|---|---|---|
| `research_centre` | `staff`/`manager` (with supervisor) | supervisor → centre_manager |
| `research_centre` | `staff`/`manager` (no supervisor) | centre_manager |
| `research_centre` | `centre_manager` | DG |
| `hq_section` | `head` | director → DG |
| `hq_section` | `staff`/`manager` | section_head → director → DG |
| `hq_standalone` | `manager` | DG |
| `hq_standalone` | `staff` | unit_manager → DG |
| `hq_directorate` | any | DG |

Stages: `supervisor`, `director`, `final`. **HR is not an active approver.** DG (or centre_manager for centre staff) is always the final approver.

HR role: receives email copy on submission and request outcomes (approved/rejected/returned). Has access to the HR Reports dashboard (`/hr/reports`) for full visibility across all requests.

`advance(TravelRequest, decision)` moves to next step (`approved`), marks as `rejected`, or marks as `returned` (resets chain/submitted_at for re-edit).

### Authorization

- `EnsureIsAdmin` middleware guards `/users` routes — only `system_admin`.
- HQ/global system admins can manage all users and assign all roles. Centre system admins can manage non-admin users in their own research centre only.
- `ApprovalController` checks `current_approver_id === auth()->id()`.
- `TravelRequestController` edit/update checks `requester_id === auth()->id()` and `isEditable()`.
- Download checks requester, current approver, acted-on history, or HR/DG.

### Key Controllers

| Controller | Responsibility |
|---|---|
| `TravelRequestController` | CRUD + pagination + search/filter + cancel + file download |
| `ApprovalController` | approve/reject/return decisions + email notifications |
| `ApprovalsController` | Pending and historical approval queue for an approver |
| `DashboardController` | Aggregated stats; personalized approval queue |
| `UserController` | System-admin CRUD for user identity, unit placement, and role assignment |

### Notifications (Queued Mail)

Four queued notification classes in `app/Notifications/`:
- `TravelRequestSubmittedNotification` — to first approver on submission
- `TravelRequestApprovedNotification` — to requester when fully approved
- `TravelRequestRejectedNotification` — to requester when rejected
- `TravelRequestReturnedNotification` — to requester when returned for revision

Mail driver: `log` in dev. Set `MAIL_MAILER` in `.env` for production (e.g. SMTP).
Queue driver: `database`. Run `php artisan queue:work` in production.

### Frontend

Blade templates in `resources/views/`. Main authenticated layout: `layouts/app.blade.php`.

Travel request form: 7-step Alpine.js wizard (steps A–G). Completed steps are clickable for backward navigation. Mobile step counter shows current step number.

Approval action UI: Alpine.js confirmation modal with three decision buttons (Approve, Return for Revision, Reject). No native `confirm()` dialogs.

Flash messages auto-dismiss after 5 seconds with transition.

Status constants and colors are centralized on `TravelRequest` model — use `$tr->statusLabel()` and `$tr->statusColor()` in views; or `TravelRequest::STATUS_COLORS` / `TravelRequest::STATUS_LABELS` for arrays.

### Database

SQLite for development. For production, switch to MySQL or PostgreSQL by updating `.env` DB_* variables.

Indexes added by migration `2026_05_15_200000_*` on: `status`, `current_approver_id`, `requester_id`, `unit_id`, `submitted_at`.
