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

- `returned` — the **first** approver sent the request back for revision; the chain and `submitted_at` are cleared and the requester can edit and resubmit. A return by a *later* approver does not reach this status: the request stays `pending` and steps back one place to the approver below, who re-reviews it. See "Return for Revision" below.
- `cancelled` — requester cancelled; terminal state

### Roles

Nine roles on `users.role`: `staff`, `head`, `manager`, `supervisor`, `director`, `centre_manager`, `director_general`, `hr`, `system_admin`.

Role helpers on User model: `isDirectorGeneral()`, `isCentreManager()`, `isHr()`, `isApprover()`.

Which roles apply to which unit type is not just a convention — `UserController::rolesForUnitType()` enforces it server-side, and the create/edit form's role dropdown filters to match the selected unit client-side (`resources/views/users/_form.blade.php`), so an admin can never even see "Head of Section" as an option once they've picked a Research Centre:

| Unit Type | Valid roles |
|---|---|
| `research_centre` | `staff`, `supervisor`, `centre_manager`, `hr`, `system_admin` |
| `hq_standalone` | `staff`, `manager`, `hr`, `system_admin`, `director_general` |
| `hq_directorate` | `director` only |
| `hq_section` | `staff`, `head`, `manager`, `hr`, `system_admin` |

### Approval Chain Service

`ApprovalChainService::buildChain(User $traveller)` computes the ordered chain at submission time based on unit type and role:

| Unit Type | Traveller Role | Chain |
|---|---|---|
| `research_centre` | `staff`/`hr`/`system_admin`, any colleague chosen | chosen colleague → centre_manager |
| `research_centre` | `staff`/`hr`/`system_admin`, Centre Manager chosen | centre_manager (single step) |
| `research_centre` | `supervisor` | centre_manager |
| `research_centre` | `centre_manager` | DG |
| `hq_section` | `head`/`manager` (section lead) | director → DG |
| `hq_section` | `staff`/`hr`/`system_admin` | section lead (head or manager) → director → DG |
| `hq_standalone` | `manager` | DG |
| `hq_standalone` | `staff` | unit_manager → DG |
| `hq_directorate` | `director` only | DG |

Stages: `supervisor`, `director`, `final`. **HR is not an active approver.** DG (or centre_manager for centre staff) is always the final approver. `chainForHqDirectorate()` rejects any role other than `director` — self-registration cannot place a new (always `staff`-role) account directly in a Directorate unit (only its sections), and the chain builder itself refuses to run for anyone else who ends up there, so a stray non-Director account can never skip supervisor/section-head/director review and go straight to the DG.

**Section lead role varies by directorate, per the official NIMR organogram**: sections under the two *scientific* directorates (Research Coordination and Promotion; Research Information and Regulatory Affairs) are led by a `head`; sections under the Corporate Services Directorate (an administrative directorate) are led by a `manager` instead. `ApprovalChainService::chainForHqSection()` treats both roles identically (top of section, no supervisor, straight to their Directorate's Director); `SupervisorService::supervisorRolesFor()` resolves an ordinary section staff member's supervisor by looking for whichever of `head`/`manager` actually leads their specific section — it is not hardcoded per directorate.

A section lead's own `supervisor_id` is auto-assigned to their Directorate's active Director, the same "fixed supervisor" mechanism that gives Directors/centre managers/standalone-unit managers the DG automatically — `SupervisorService::reportsDirectlyToDirectorate()` / `fixedSupervisorForAssignment()`. Neither the admin (user create/edit form) nor the lead themselves (Dashboard) picks it manually; it's silently kept in sync on every load via `applyFixedSupervisor()`. If the Directorate has no active Director yet, user creation/update is blocked with a clear validation error (`users.director_supervisor_missing`) rather than silently leaving a bad value.

**Research centres use a dedicated `supervisor` role**, not `manager` — that name was easy to confuse with an `hq_standalone` unit's Manager. A centre's Supervisor is the single person ordinary staff report to below the Centre Manager; like a section lead, they get their Centre Manager auto-assigned as their own fixed supervisor (`SupervisorService::reportsDirectlyToCentreManager()`) and never pick one themselves. **Centres do not all follow one reporting line.** Some staff sit under a Supervisor, some report straight to the Centre Manager, others answer to a senior colleague holding no supervisory role. Rather than encode a guess, `SupervisorService::supervisorRolesFor()` offers a centre's `staff`/`hr`/`system_admin` **every active colleague in their centre** and they choose for themselves — on the user form or their own Dashboard. Nothing is pinned: `fixedSupervisorForAssignment()` returns null for these roles, so the choice is always explicit.

Whoever is chosen approves first and the request goes to the Centre Manager after them, whatever role the chosen person holds — so an ordinary staff member set as someone's supervisor becomes an approver for that person. The exception is choosing the Centre Manager themselves, where `chainForCentre()` collapses to a **single** `final` step; routing supervisor → centre_manager would otherwise place the same person in two consecutive stages and ask them to approve the same request twice. This also removes an old failure mode where a centre with no active Supervisor left its staff with nothing to select and unable to submit at all. Migration `2026_08_28_000001_add_supervisor_role` also migrates any pre-existing `manager`-role user inside a `research_centre` unit to `supervisor`.

Moving a user between a Research Centre and any HQ unit type (or back) rebuilds their whole place in the approval chain, so `UserController::update()` refuses it unless the request carries `confirm_unit_type_change=1` — the edit form only shows that checkbox once the selected unit's category actually differs from the user's current one.

HR role: receives email copy on submission and request outcomes (approved/rejected/returned). Has access to the HR Reports dashboard (`/hr/reports`) — **HQ HR and the DG see the whole institute; a centre HR officer is scoped to their own centre** via `User::isCentreScopedViewer()`, which also scopes `/travel-reports`.

`advance(TravelRequest, decision)` moves to the next step (`approved`), marks as `rejected`, or applies the return rules below.

**Return for Revision** steps back exactly one place, never straight to the requester from mid-chain:

| Returning approver | Result |
|---|---|
| First in the chain (or chain missing) | status `returned`, chain and `submitted_at` cleared, requester edits and resubmits |
| Any later approver | stays `pending`, `current_approver_id` moves to the previous step; that approver re-reviews and decides whether to send it up again or return it further |

`ApprovalChainService::returnGoesToRequester()` is the single source of truth for this and also picks the wording of the confirmation modal on the show page.

On resubmission of a `returned` request the chain resumes at the approver who returned it. If the requester has since been transferred, the chain is rebuilt from their **current** unit and the request's `unit_id` moves with them.

### Authorization

- `EnsureIsAdmin` middleware guards `/users` routes — only `system_admin`.
- `EnsureAccountIsActive` runs on the whole `web` group: a user deactivated mid-session is logged out on their next request. Deactivating via `/users` also calls `SessionRevocationService` to delete their stored sessions and rotate `remember_token`.
- HQ/global system admins can manage all users and assign all roles. Centre system admins can manage non-admin users in their own research centre only.
- `ApprovalController` checks `current_approver_id === auth()->id()`.
- `TravelRequestController` edit/update checks `requester_id === auth()->id()` and `isEditable()`, then re-checks it under `lockForUpdate()` inside the write transaction so a stale tab cannot overwrite a request that has moved on. `cancel` does the same with `isCancellable()`.
- Traveller identity on the permit (`b_applicant_name`, `b_email`, `b_position`) is **never** accepted from the request body — `travellerIdentity()` fills it from the authenticated account.
- Self-service account "deletion" (`ProfileController::destroy`) deactivates rather than deletes, and is refused while the user holds pending approvals or open requests. Hard deletion would null out requester/approver foreign keys and erase approval attribution.
- Download checks requester, current approver, acted-on history, or HR/DG.

### Serving the app

Only `public/` may be exposed. The project must **not** be served from a document root that contains the repository — that makes `.env`, the SQLite database, `storage/logs`, and private handover uploads directly fetchable. `docs/apache-vhost.conf` holds a ready XAMPP virtual host (port 8080); the repo-root `.htaccess` denies everything as a backstop.

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

`travel_requests` indexes on `status`, `current_approver_id`, `requester_id`, `unit_id`, `submitted_at`, plus a unique index on `request_number`, are owned by `2026_08_08_120000_restore_travel_request_indexes`. The earlier `2026_05_15_200000_*` created them but the SQLite table rebuild in `2026_05_18_113454_*` silently dropped them — **any future SQLite table rebuild must recreate them**, and that rebuild's `INSERT ... SELECT *` is positional, so it also depends on column order.

Permit numbers come from `TravelRequestController::nextRequestNumber()`, derived from the highest number issued that year; the unique index plus a retry in `createWithRequestNumber()` is what actually prevents duplicates under concurrency.

Migrations that alter enum/CHECK constraints must branch per driver (`mysql`, `pgsql`, `sqlite`) and fail loudly on anything else — see `2026_05_19_000002_add_system_admin_role`. `DatabaseSeeder` creates demo accounts with the password `password` and throws if run in production.
