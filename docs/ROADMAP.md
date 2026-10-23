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

- [ ] Enums: `GoalType`, `GoalStatus`, `Priority`, `TaskStatus`, `PaceStatus`, `ReminderKind`, `ReminderChannel`, `ActivityType`
- [ ] Migrations + models + factories (with states) for goals, tasks, checklist_items, completions, reminders, activities; user columns `timezone`, `daily_points_target`, `telegram_chat_id`
- [ ] Policies for ownership
- [ ] `DemoSeeder` mirroring the prototype data

## M2 — Rules (services + unit tests)

- [ ] `Clock` (today in the user's timezone)
- [ ] `GoalProgressService`: progress (habit/project), expected, delta, pace status, projection, current/required pace
- [ ] `ScoreService`: day score, week score vs last week, 30-day series
- [ ] `StreakService`: current streak and record
- [ ] `ConsistencyService`: 12-week heat levels
- [ ] `CompletionService`: complete / undo (idempotent per day)
- [ ] `TaskService`: create, update, move between statuses (creates/deletes completion on done)
- [ ] `GoalService`: create, update, change status
- [ ] `ActivityLogger`

## M3 — Dashboard (first usable day)

- [ ] `TrakkLayout`: top bar with live clock, year progress, command bar shell, `+ goal`, `check-in`
- [ ] UI primitives and Trakk components from DESIGN.md
- [ ] `DashboardService` read model (fixed query count)
- [ ] Dashboard page identical to `docs/design/reference/dashboard-desktop.png` and `dashboard-mobile.png`
- [ ] Check/uncheck today's actions (optimistic) with feature tests
- [ ] Goal filter tabs

## M3a — Mocked screens

Login, dashboard and goal detail built against PHP mocks (`app/Support/Mocks/DashboardMock.php`, `GoalDetailMock.php`) shaped like the future Resources. Interactions are local React state only. M3 and M4 replace the mocks with services and real mutations; the sums and counts in `use-dashboard-state.ts` and `use-goal-board-state.ts` move to the server then.

- [x] `AuthLayout`, `LogoMark`, `TextField` and the sign-in screen (real Fortify login, error and throttle states)
- [x] UI primitives (`Panel`, `PanelHeader`, `Chip`, `ProgressBar`, `TrakkButton`, `TrakkCheckbox`, `TrakkToggle`) and `TrakkLayout`
- [x] Dashboard (`/`) with all panels, goal filters, local check / kanban move / reminder toggles
- [x] Goal detail (`/goals/{goal}`) with pace chart, board moves and history
- [x] Typed props in `resources/js/types/`
- [x] Feature tests (guest redirect, owner renders the right component) and `tests/Browser/ScreensTest.php` screenshots at 1440px and 390px

## M4 — Goals and boards

- [ ] Create / edit goal form (all fields from PRODUCT.md, color picker limited to the goal palette)
- [ ] Goal detail page identical to `docs/design/reference/goal-desktop.png` and `goal-mobile.png`
- [ ] Tasks: create, edit, delete, recurrence, points, due date, tag
- [ ] Kanban moves (advance / reopen) with feature tests
- [ ] Checklist items
- [ ] Focus project on the dashboard (most behind active project)

## M5 — Reminders and MCP (local)

- [ ] Reminders CRUD and toggle
- [ ] `TelegramClient`, `SendReminder` job, `reminders:dispatch` every minute, daily overdue scan
- [ ] Message templates for each reminder kind
- [ ] `TrakkServer` with `get-today`, `list-goals`, `complete-task`, `create-task`, `move-task`, `create-goal`, tests for each
- [ ] `Mcp::local('trakk', ...)` registered in Claude Code (`claude mcp add -s project trakk -- php artisan mcp:start trakk`)

## M6 — Deploy and remote MCP

- [ ] FrankenPHP `Dockerfile` + `compose.yaml` + `Caddyfile` per `docs/DEPLOY.md`
- [ ] VPS deploy with HTTPS on a subdomain, scheduler and queue worker running
- [ ] GitHub Action: test on push, deploy on `main`
- [ ] Passport + `Mcp::oauthRoutes()` + `Mcp::web('/mcp', ...)`; connected as a custom connector in Claude (web and mobile)
- [ ] PWA manifest and icons
- [ ] Daily Postgres backup

## Later

- Mobile "Today" screen designed for the phone
- Command bar actions (⌘K: log, search, ask)
- Inbound Telegram commands (check in by replying)
- Weekly review page
