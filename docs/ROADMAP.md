# Trakk — Roadmap

Tick boxes as work lands. Each milestone ends usable. Order matters: every milestone builds on real data from the previous one.

M1–M5 are executed from `docs/specs/real-features.md` (sessions S1–S4). That spec lists every feature, route, rule and UI surface; tick the boxes here as each session lands.

## M0 — Project setup

`laravel new` needs an empty folder and this repository already has files, so the app is created next to it and merged in without overwriting the docs.

```bash
cd ~/Projects
laravel new trakk-app
#   starter kit: React · auth: Laravel's built-in (Fortify) · testing: Pest · database: PostgreSQL
#   install Laravel Boost: yes (Claude Code) · run npm install: yes
rsync -a --ignore-existing trakk-app/ trakk/   # keeps README.md, CLAUDE.md, docs/, .ai/, .claude/
rm -rf trakk-app
cd trakk
```

- [x] App created and merged (`rsync --ignore-existing`, then delete `trakk-app`)
- [x] `.env`: `DB_CONNECTION=pgsql`, `DB_DATABASE=trakk`, `APP_TIMEZONE=UTC`; databases `trakk` and `trakk_test` created (`createdb trakk && createdb trakk_test`)
- [x] `phpunit.xml`: tests use pgsql `trakk_test`
- [x] `composer require laravel/mcp` and `php artisan vendor:publish --tag=ai-routes`
- [x] `composer require --dev larastan/larastan`, `phpstan.neon` at level 6 over `app/`
- [x] `php artisan boost:install` (Claude Code: guidelines, skills, MCP). Confirm `CLAUDE.md` keeps the project section above the Boost block
- [x] `ln -s ../.ai/rules .claude/rules` (if not present) so Claude Code auto-loads path-scoped rules
- [x] Composer scripts: `test`, `lint` (`pint`), `analyse` (`phpstan`); npm script `types` (`tsc --noEmit`) if missing
- [x] Fonts: `npm i @fontsource-variable/inter @fontsource/jetbrains-mono`, imported in `app.tsx`
- [x] Design tokens from `docs/DESIGN.md` pasted into `resources/css/app.css`; app forced to dark
- [x] `Date::use(CarbonImmutable::class)`, `Model::preventLazyLoading()`, `Model::shouldBeStrict()` (non-prod) in `AppServiceProvider`
- [x] Auth reduced to login + logout: in `config/fortify.php` keep no features (remove registration, reset passwords, email verification, two-factor); delete the starter-kit pages, routes and tests for those flows
- [x] Owner account created by `DatabaseSeeder` from `.env` (`OWNER_EMAIL`, `OWNER_PASSWORD`)
- [x] Sign-in page rebuilt with `AuthLayout`, `LogoMark`, `TextField` to match `docs/design/reference/login-*.png`; feature tests: logs in, rejects wrong password, throttles
- [x] First commit: `chore: scaffold Laravel 13 React starter kit`

## M1 — Domain and data

- [x] Enums: `GoalType`, `GoalStatus`, `GoalColor`, `Priority`, `TaskStatus`, `PaceStatus`, `ReminderKind`, `ReminderChannel`, `ActivityType`, `ActivitySource`, `LogKind`, `ReplanTarget`
- [x] Migrations + models + factories (with states) for goals, tasks, checklist_items, completions, reminders, activities; user columns `timezone`, `daily_points_target`, `telegram_chat_id`
- [x] Policies for ownership
- [x] `DemoSeeder` mirroring the prototype data, built relative to today; `DatabaseSeeder` seeds the owner and the default reminders

## M2 — Rules (services + unit tests)

- [x] `Clock` (today in the user's timezone)
- [x] `GoalProgressService`: progress (habit/project), expected, delta, pace status, projection, current/required pace
- [x] `ScoreService`: day score, week score vs last week, 30-day series
- [x] `StreakService`: current streak and record
- [x] `ConsistencyService`: 12-week heat levels
- [x] `CompletionService`: complete / undo (idempotent per day)
- [x] `TaskService`: create, update, move between statuses (creates/deletes completion on done)
- [x] `GoalService`: create, update, change status
- [x] `ActivityLogger`

## M3 — Dashboard (first usable day)

- [x] `TrakkLayout`: top bar with live clock, year progress, command bar shell, `+ goal`, `check-in`
- [x] UI primitives and Trakk components from DESIGN.md
- [x] `DashboardService` read model (fixed query count)
- [x] Dashboard page identical to `docs/design/reference/dashboard-desktop.png` and `dashboard-mobile.png`
- [x] Check/uncheck today's actions (optimistic) with feature tests
- [x] Goal filter tabs

## M4 — Goals and boards

- [x] Create / edit goal form (all fields from PRODUCT.md, color picker limited to the goal palette), status and delete, and the `/goals` list
- [x] Goal detail page identical to `docs/design/reference/goal-desktop.png` and `goal-mobile.png` (real data; deviations: the global top bar, the seed's own title and tasks)
- [x] Tasks: create, edit, delete, recurrence, points, due date, tag
- [x] Kanban moves (advance / reopen) with feature tests
- [x] Checklist items
- [x] Focus project on the dashboard (most behind active project)

## M5 — Reminders and MCP (local)

- [x] Reminders CRUD and toggle
- [x] `TelegramClient`, `SendReminder` job, `reminders:dispatch` every minute, daily overdue scan
- [x] Message templates for each reminder kind
- [x] `TrakkServer` with `get-today`, `list-goals`, `complete-task`, `uncomplete-task`, `create-task`, `move-task`, `create-goal`, `replan-tasks`, tests for each
- [x] `Mcp::local('trakk', ...)` registered in Claude Code (`claude mcp add -s project trakk -- php artisan mcp:start trakk`)

## M6 — Deploy and remote MCP

- [x] FrankenPHP `Dockerfile` + `compose.yaml` + `Caddyfile` per `docs/DEPLOY.md`
- [x] VPS deploy with HTTPS on a subdomain, scheduler and queue worker running
- [ ] GitHub Action: test on push, deploy on `main`
- [ ] Passport + `Mcp::oauthRoutes()` + `Mcp::web('/mcp', ...)`; connected as a custom connector in Claude (web and mobile)
- [ ] PWA manifest and icons
- [x] Daily Postgres backup

## Later

- Mobile "Today" screen designed for the phone
- Command bar "ask" (⌘K asks Claude; search and commands are done)
- Inbound Telegram commands (check in by replying)
- Weekly review page
