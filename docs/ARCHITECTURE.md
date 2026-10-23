# Trakk — Architecture

## Stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13, PHP 8.4 (8.3 minimum) |
| Frontend | Inertia 3 + React 19 + TypeScript, from the official Laravel React starter kit |
| Styling | Tailwind CSS 4 (CSS-first `@theme`, tokens in `docs/DESIGN.md`) |
| Routing in TS | Laravel Wayfinder (typed route functions, ships with the starter kit) |
| Auth | Laravel Fortify (starter kit), login and logout only. Passport added later for MCP OAuth. |
| Database | PostgreSQL 17 |
| Queue / cache / sessions | `database` driver (no Redis in the MVP) |
| Scheduler | Laravel scheduler (`routes/console.php`). Locally `schedule:work` runs inside `composer run dev`; in production a `scheduler` container runs `schedule:work` |
| MCP | `laravel/mcp` (official) |
| Notifications | Telegram Bot API via Laravel HTTP client; mail as fallback |
| Tests | Pest 4 |
| Quality | Pint (format), Larastan (static analysis), ESLint + Prettier (starter kit) |
| AI tooling | Laravel Boost (dev only): guidelines, skills and the Boost MCP for coding agents |
| Local dev | Native PHP + Postgres. `composer run dev` starts everything else: server, queue worker, scheduler, logs, Vite. Extra processes are registered with `DevCommands::artisan()` in `AppServiceProvider` |
| Deploy | Own VPS, Docker Compose, app served by FrankenPHP in classic mode, no Octane (see `docs/DEPLOY.md`) |

## Request flow

```
HTTP / MCP request
  → Route (routes/web.php | routes/ai.php)
  → Controller (thin) or MCP Tool (thin)
      → FormRequest validates and builds a Data object
      → Service does the work (business rules, transactions, events)
      → Model / Eloquent (persistence only)
  ← Resource / Data shapes the response
  ← Inertia::render('page', props) | Response::text/json for MCP
```

Controllers and MCP tools are adapters. They hold no rules. The same service method backs the web action and the MCP tool.

## Backend layout

```
app/
├── Data/                     # readonly input/output objects (GoalData, TaskData, DayScore, GoalPace)
├── Enums/                    # GoalType, GoalStatus, GoalColor, Priority, TaskStatus, PaceStatus, ReminderKind, ReminderChannel, ActivityType, ActivitySource, LogKind, ReplanTarget, DayState, PointsTier
├── Http/
│   ├── Controllers/          # thin, resourceful (DashboardController, GoalController, TaskController, TaskStatusController, CompletionController, ReminderController)
│   ├── Requests/             # one FormRequest per write action, each exposes toData()
│   └── Resources/            # JsonResource classes shaping Inertia props
├── Jobs/                     # SendReminder
├── Mcp/
│   ├── Servers/TrakkServer.php
│   └── Tools/                # GetTodayTool, ListGoalsTool, CompleteTaskTool, CreateTaskTool, MoveTaskTool, CreateGoalTool
├── Models/                   # User, Goal, Task, ChecklistItem, Completion, Reminder, Activity
├── Policies/                 # GoalPolicy, TaskPolicy, ChecklistItemPolicy, ReminderPolicy (ownership)
├── Support/                  # Clock (today in the user's timezone), Labels (server-side formatting)
└── Services/
    ├── Goals/                # GoalService (create with unique slug, update, status, delete), GoalProgressService (progress, expected, pace, projection)
    ├── Tasks/                # TaskService (create, update, move, replan, checklist), CompletionService (complete, undo), OnTimeService (on time rate)
    ├── Scoring/              # ScoreService (day score, week score), StreakService, ConsistencyService (heatmap levels)
    ├── Dashboard/            # DashboardService (assembles the dashboard read model), GoalDetailService
    ├── Activity/             # ActivityLogger (log entries, `· claude` suffix for MCP), CheckInService (close the day)
    ├── Reminders/            # ReminderService (create, update, delete, toggle, defaults; later due reminders and message building)
    └── Telegram/             # TelegramClient (send message)
```

Rules for each layer are in `.ai/rules/` (index: `.ai/rules/index.md`).

## Frontend layout

```
resources/js/
├── pages/
│   ├── dashboard.tsx
│   ├── goals/{index,show,create,edit}.tsx
│   └── reminders/index.tsx
├── components/
│   ├── ui/                   # primitives: panel, chip, button, toggle, checkbox, progress-bar
│   └── trakk/                # domain components from docs/DESIGN.md (kpi-card, action-row, goal-card, kanban-board, ...)
├── layouts/trakk-layout.tsx  # top bar + page container
├── hooks/                    # use-clock, use-command-bar
├── lib/                      # format (dates, points, percent), pace colors
└── types/                    # TS types mirroring API Resources
```

## Data model

```
users           id, name, email, password, timezone ('Europe/Dublin'), daily_points_target (14), telegram_chat_id?, timestamps
goals           id, user_id→users, related_goal_id?→goals (null on delete), title, slug, icon?, description?, done_criteria?,
                type, color, priority, start_date, due_date, target_per_week?, area?, status, position, timestamps
                unique(user_id, slug)
tasks           id, goal_id→goals, title, notes?, status ('todo'), points (1..5), due_date?, scheduled_time?,
                days_of_week (jsonb, ISO 1..7)?, tag?, position, completed_at?, timestamps
checklist_items id, task_id→tasks, title, is_done, position, timestamps
completions     id, task_id→tasks, user_id→users, completed_on (date), points, timestamps        unique(task_id, completed_on)
reminders       id, user_id→users, goal_id?→goals, kind, label, time, days_of_week (jsonb)?, fire_on (date)?,
                channels (jsonb, non-empty), is_active, last_sent_at?, last_error?, timestamps
activities      id, user_id→users, goal_id?→goals, task_id?→tasks, type, message, points?, source ('web'), meta (jsonb)?,
                occurred_at, timestamps
```

- A recurring reminder has `days_of_week`; a one-off reminder has `fire_on` and a null `days_of_week`.
- `activities.source` is `web`, `mcp` or `system`. `activities.goal_id` and `task_id` are set to null when the goal or task is deleted, so the log survives.
- Enum columns are `string` in Postgres and cast to PHP backed enums.
- Foreign keys cascade on delete (goal → tasks → checklist items, completions), except `goals.related_goal_id` and the activity links, which are set to null.
- Indexes: `goals(user_id, status)`, `tasks(goal_id, status)`, `tasks(due_date)`, `completions(user_id, completed_on)`, `activities(user_id, occurred_at)`.
- `completions.user_id` is denormalised on purpose: day and week scores are queried by user and date without joins.

## Read models

The dashboard is one Inertia page fed by `DashboardService::for(User $user, CarbonImmutable $today): DashboardData`. It runs a fixed number of queries (no N+1), computes pace with `GoalProgressService`, and returns plain arrays/Data objects. Pages never compute business numbers; they only format them.

Mutations (check action, move card, toggle reminder) use Inertia `router` / `<Form>` with optimistic UI and `preserveScroll`. Props are reloaded partially (`only: [...]`) after each mutation.

## Time

- Every service that depends on "today" receives `CarbonImmutable $today` (resolved once per request from the user's timezone by `App\Support\Clock`). No `now()` deep inside rules.
- `$today` is the user's calendar date as midnight in the app timezone, the same shape `date` columns are cast to, so day differences are exact.
- Dates are stored as `date` columns; timestamps are UTC.
- Labels such as `dueLabel`, `timeLabel`, `dayLabel` and the top bar date line are formatted on the server by `App\Support\Labels`, never in React. The day of the year in the top bar line is 1-based (23 Oct 2026 is `day 296/365`).

## MCP

- `routes/ai.php`:
  - `Mcp::local('trakk', TrakkServer::class)` for Claude Code / Desktop on this machine (milestone M5).
  - `Mcp::web('/mcp', TrakkServer::class)->middleware(['auth:api', 'throttle:mcp'])` with `Mcp::oauthRoutes()` once Passport is installed (milestone M6), so claude.ai web and mobile can connect as a custom connector.
- Tools resolve the user from `$request->user()` (web) or the single owner account (local).
- Tool descriptions are written for the model: say when to use the tool and what it returns.

## Reminders

- `Schedule::command('reminders:dispatch')->everyMinute()->withoutOverlapping()` in `routes/console.php`.
- `ReminderService::due(CarbonImmutable $now)` returns active reminders whose time and weekday match in the user's timezone and that were not sent in this slot.
- Each one dispatches `SendReminder` (queued). The job builds the message through `ReminderService` and sends it with `TelegramClient` (or mail), then records `last_sent_at` and an activity.
- Overdue detection: a daily scheduled command logs `task_overdue` activities at 00:05 user time.

## Seeding

`DatabaseSeeder` creates the owner (`OWNER_EMAIL` / `OWNER_PASSWORD`) and the five default reminders. In `local` and `testing` it also runs `DemoSeeder`, which builds the prototype data relative to today (`database/seeders/Demo/DemoHistory.php` generates the completion history). Production gets no demo data.

## Security

- Every query is scoped to the authenticated user; policies enforce ownership on route-model binding.
- Single-user app: no registration, password reset or email verification. The owner account is seeded from `OWNER_EMAIL` / `OWNER_PASSWORD`; to change the password use `php artisan tinker` or a small artisan command later.
- Login is throttled by Fortify (5 attempts per minute per email + IP).
- MCP web endpoint requires OAuth (Passport) and is rate limited; the dynamic client registration route is throttled.
- Secrets only in `.env`. `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` are read through `config/services.php`.
