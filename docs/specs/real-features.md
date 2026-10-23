# Spec — Make every mocked feature real

Status: approved for execution · Covers ROADMAP M1, M2, M3, M4, M5 · Replaces milestone M3a mocks

## Goal

Every number, list and button on the three screens (login, dashboard, goal detail) and in the top bar works against real data in PostgreSQL. Nothing is mocked when this spec is done: `app/Support/Mocks/` is deleted, the local sums in `use-dashboard-state.ts` and `use-goal-board-state.ts` are gone, reminders reach Telegram, and Claude can read and log progress through the local MCP server.

## Decisions taken for this spec

These were defaults chosen while the owner had no preference. They are owner-approved unless the owner says otherwise; write each into `docs/PRODUCT.md` or `docs/ARCHITECTURE.md` when the session that implements it lands.

1. **No AI in this round.** The overdue "suggestion" is rule-based. The command bar (⌘K) is search + commands; its placeholder becomes `search or run a command…`. `OPENAI_*` stays unused until a separate "AI" milestone.
2. **In scope:** real data, every interaction and form, reminders delivered to Telegram (and email), the local MCP server. **Out of scope:** deploy, Passport/remote MCP, PWA, inbound Telegram commands (all stay in M6 / Later).
3. **Goal URLs use a slug** (`/goals/nomad-visa`), unique per user, generated from the title, editable.
4. **New UI (forms, sheets, palette, goals list) follows "New surfaces" below.** No new tokens; reuse existing components.
5. **Production starts empty** (owner account + default reminders). `DemoSeeder` only runs in `local`/`testing` and builds data relative to today so the screens always look like the reference images.

## Contracts that must not break

- The page props consumed today are the contract: `resources/js/types/dashboard.ts`, `goal-detail.ts`, `trakk.ts`. Laravel Resources / read models must produce **the same shapes**. Allowed changes: ids become numbers (goal routes use `slug`), and new fields needed by new UI. Change the TS type in the same commit as the PHP that produces it.
- The UI must keep matching `docs/design/reference/*.png` with `DemoSeeder` data. `tests/Browser/ScreensTest.php` keeps running against seeded data.
- Labels such as `dueLabel`, `timeLabel`, `dayLabel`, `startLabel` are formatted on the server in the user's timezone by one formatter (`App\Support\Labels`), never in React.

## Feature inventory

Every item below is a checklist entry for the sessions. "Source" is what produces the data; "Action" is what the button/interaction does.

### Top bar (every authenticated page)

| Element | Source / action |
| --- | --- |
| Date line (`fri, 23 oct 2026 · wk 43 · day 296/365`) and year progress | `TopBarData` built by a small service from `Clock` in the user's timezone, shared in `HandleInertiaRequests` (replaces `TopBarMock`). |
| Live clock | Client-side (`use-clock`), already real. |
| Command bar ⌘K | Opens the command palette (see New surfaces). |
| `mcp connected` dot | `mcp · N tools`: N = count of tools on `TrakkServer`. Dot pulses (accent) if an MCP call was logged in the last 24h, otherwise `dim` with label `mcp idle`. |
| `+ goal` | Opens the goal sheet in create mode. |
| `check-in` | Opens the check-in sheet. |

### Dashboard (`/`)

| Panel | Source | Actions |
| --- | --- | --- |
| KPI strip (6 cards) | `DashboardService` using `ScoreService`, `StreakService`, `GoalProgressService`, task queries. Rules in PRODUCT.md → KPIs. | — |
| Today | Today list rule (PRODUCT.md → Tasks). `timeLabel`: `HH:MM` if `scheduled_time`; `today` if one-off due today; `all day` for a recurring task without time; `anytime` for a `doing` task without due date. `isDueToday` for one-off due today. | Check / uncheck → `POST /tasks/{task}/completions` / `DELETE /tasks/{task}/completions/today`. Optimistic, partial reload `only: [kpis, actions, pointsChart, consistency, goals, focusProject, log, status]`. |
| Overdue | One-off tasks not done with `due_date < today`, sorted by days late desc. | `replan` → replan sheet. |
| Suggestion | `SuggestionService` (rule below). Not rendered when nothing is overdue; the Overdue empty state covers it. | — |
| Next 7 days | One-off tasks not done with due date in `(today, today+7]`, plus active goals whose `due_date` falls in that window (`goal deadline`). `dayLabel` = `SAT 24`. | Click → goal page. |
| Goals grid | Active goals with progress, expected, pace, week dots (habits), progress label (projects). | Filter tabs stay client-side (pure presentation). Card → goal page. |
| Points · 30 days | `ScoreService::series(user, today, 30)`, tiers by rule. Stats: average (1 decimal), best day, days at target. | — |
| Consistency · 12 weeks | `ConsistencyService` heat levels, Monday-based weeks, future cells `null`. | — |
| Reminders | Owner's reminders (not goal-scoped first, then goal-scoped), with `timeLabel`, `frequencyLabel`, `channelsLabel`. | Toggle → `PATCH /reminders/{reminder}/active`. Row click → reminder sheet. Panel header gets `+` (icon button) → reminder sheet in create mode. |
| Focus project | Most behind active project goal (lowest delta; tie → earliest due date). 3 cards per column max, `hiddenCount` for the rest. Empty state when no active project exists. | Move card → `PATCH /tasks/{task}/status`. Card click → task sheet. |
| Log | Latest 8 activities of the owner. | — |
| Footer status | `next reminder HH:MM` (next active reminder occurrence today, else tomorrow's first), `N pts to daily target` / `daily target hit`, `mcp N tools`, `telegram bot active` when token + chat id are set and the last send did not fail, else `telegram off`. | — |

### Goal detail (`/goals/{goal:slug}`)

| Element | Source | Actions |
| --- | --- | --- |
| Breadcrumb, title, chips, pace | Goal + `GoalProgressService`. | `edit` → goal sheet (edit mode). `+ task` → task sheet (create mode, goal preselected). |
| Facts grid | Goal fields; `remaining` = days to due date (0 when past); `overdue` = count of overdue tasks. | — |
| Objective | `description`, `done_criteria`, `area`, `linked to` = `related_goal` title (link). | — |
| Pace chart | Actual series: 5 samples evenly spaced from start to today (always including both ends); percent at a sample day = progress computed with only completions/`completed_at` up to that day. Expected line, projection, current and required pace from PRODUCT.md. Month ticks. | — |
| Progress panel | Percent, done/total, counts per status. Habits: hit days / planned days. | — |
| Board (projects) | All tasks of the goal by status, ordered by `position`, overdue and tags flagged. | Advance / reopen → `PATCH /tasks/{task}/status`. Card click → task sheet. |
| Actions (habits) | Replaces the board for habit goals: one row per recurring task: title, weekday chips (`M T W T F S S`, active in goal color), time, `+N`, this week's hits. Same panel style as the board. | Row click → task sheet. |
| Reminders for this goal | Goal-scoped reminders. | Row click → reminder sheet; header `+` → reminder sheet with goal preselected. |
| History | Latest 20 activities for the goal. | — |

### Settings (`/settings/profile`)

Add to the existing profile form: `timezone` (select of IANA zones, default `Europe/Dublin`), `daily points target` (1–50), `telegram chat id` with a `send test` button (`POST /settings/telegram/test`) and the link instructions (run `php artisan telegram:link`). Styled with Trakk tokens.

### Goals list (`/goals`)

Small page reachable from the command palette and from a `goals →` link next to the dashboard Goals heading. Table-like list grouped by status (active, paused, done, abandoned): color square, title, type, priority, dates, progress. Row → goal page. This is how paused or finished goals are found again.

## Rules added by this spec (merge into PRODUCT.md)

- **Habit progress uses hit days.** A hit day is a day with at least one completion of any task of the habit goal. Week dots show hit days Monday→Sunday. Planned days = `target_per_week × weeks(start..due)`. Progress = hit days so far / planned days, capped at 100.
- **Project task done ⇔ completion.** Moving to `done` sets `completed_at` and creates the completion for today with the task's points; moving out of `done` clears `completed_at` and deletes that completion (whatever day it was). Checking a one-off task in Today moves it to `done`.
- **Suggestion rule.** Take the active goal with the lowest delta that has overdue tasks. `n` = its overdue count. Text: `knock out the {n} overdue {goal title lowercased} {task|tasks} {when}. It unblocks your most behind goal and clears {n} {item|items} from red.` where `when` = `today` if n ≤ 2 or today is Saturday/Sunday, else `on Saturday morning`. If no goal has overdue tasks but some task is overdue, use the task with the most days late: `clear "{task}" today, it is {d} days late.`
- **Replan.** For each selected overdue task, set `due_date` to today, tomorrow, next Monday or a picked date; logs one `replanned` activity per task.
- **Check-in.** The check-in sheet shows today's actions (same rows, checkable) and the day score. `close the day` logs one `check_in` activity with `score/target` and whether the target was hit (log kind `checkin`, text `check-in · target hit` or `check-in · N pts short`). At most one check-in per day; a second one updates the first.
- **Reminder schedule.** A reminder has `time`, `channels` (one or more of `telegram`, `email`) and either `days_of_week` (recurring) or `fire_on` (once, a date). `frequencyLabel`: `daily` (7 days), `weekdays`, a single day name (`sunday`), `day before` (kind `due_tomorrow`), or the date for once (`01 jan`).
- **Reminder kinds** (`ReminderKind`): `daily_brief`, `evening_checkin`, `due_tomorrow`, `weekly_review`, `off_pace`, `pace_summary` (goal), `deadline_alert` (goal, usually once), `custom`.
- **Default reminders** created with the owner: daily brief 07:30 daily · evening check-in 21:30 daily · tasks due tomorrow 18:00 daily · weekly review 10:00 sunday (telegram + email) · goals off pace 09:00 monday (email, inactive).
- **Activities → log kinds.** `task_completed`/`check_in` → `done`/`checkin`; `task_undone`, `reminder_paused` → `undone`; `task_moved`, `reminder_sent`, `reminder_enabled`, `goal_created`, `task_created`, `replanned`, `mcp_call` → `sent`; `task_reopened` → `reopened`; `task_overdue`, `deadline_near`, `reminder_failed` → `warning`. `timeLabel`: `HH:MM` today, `yday HH:MM`, otherwise `dd mon`.
- **Activities from MCP** carry `source = mcp`; their text ends with ` · claude`.

## Data model changes (merge into ARCHITECTURE.md)

- `goals`: add `slug` (unique with `user_id`), `related_goal_id` (nullable FK to goals, null on delete).
- `reminders`: `channel` → `channels jsonb` (non-empty array); add `fire_on date null`; `days_of_week` nullable (null when `fire_on` is set); add `last_error text null`.
- `activities`: add `source` (`web` | `mcp` | `system`, default `web`), `meta jsonb null`.
- `users`: `timezone`, `daily_points_target`, `telegram_chat_id` (as planned).
- Everything else as in ARCHITECTURE.md → Data model.

## Routes (web)

```
GET    /                                   DashboardController@index
GET    /goals                              GoalController@index
POST   /goals                              GoalController@store
GET    /goals/{goal:slug}                  GoalController@show
PATCH  /goals/{goal:slug}                  GoalController@update
DELETE /goals/{goal:slug}                  GoalController@destroy
PATCH  /goals/{goal:slug}/status           GoalStatusController@update
POST   /goals/{goal:slug}/tasks            TaskController@store
PATCH  /tasks/{task}                       TaskController@update
DELETE /tasks/{task}                       TaskController@destroy
PATCH  /tasks/{task}/status                TaskStatusController@update
POST   /tasks/{task}/completions           CompletionController@store      (today)
DELETE /tasks/{task}/completions/today     CompletionController@destroy
POST   /tasks/{task}/checklist             ChecklistItemController@store
PATCH  /checklist/{item}                   ChecklistItemController@update  (title, is_done)
DELETE /checklist/{item}                   ChecklistItemController@destroy
POST   /replan                             ReplanController@store          (task ids + target)
POST   /check-in                           CheckInController@store
POST   /reminders                          ReminderController@store
PATCH  /reminders/{reminder}               ReminderController@update
DELETE /reminders/{reminder}               ReminderController@destroy
PATCH  /reminders/{reminder}/active        ReminderActiveController@update
POST   /settings/telegram/test             TelegramTestController@store
```

`{goal:slug}` resolves among the signed-in user's goals only (a binding in `AppServiceProvider`), so a slug another user owns is a 404.

All behind `auth`, policies on every bound model, redirects back with `preserveScroll`. Sheets are not routes: they open over the current page and submit with Inertia `<Form>` / `useForm`.

## New surfaces (UI)

Build with existing tokens and components (`Panel`, `PanelHeader`, `TextField`, `TrakkButton`, `TrakkCheckbox`, `TrakkToggle`, `Chip`). Read `docs/DESIGN.md` first. Match the density and voice of the existing screens.

- **Sheet** (`components/trakk/sheet.tsx`, built on `components/ui/sheet`): right side, 440px wide (full width under 480px), `surface` background, 1px `line` left border, no shadow, no overlay blur (overlay `canvas` at 70% opacity). Header like `PanelHeader` (mono uppercase title, `esc` hint, close button 44px). Body padding 16px, fields gap 14px. Footer pinned: secondary `TrakkButton` (cancel) + accent `TrakkButton` (save), right-aligned. Errors under each field in mono 10.5px `danger`.
- **Form controls:** text → `TextField`; textarea → same box, min 88px tall, sans 12.5px; select of few options → segmented row of `Chip`-like buttons (active: `selected` background, `line-strong` border, `fg` text); weekdays → seven 28px square toggles `M T W T F S S`; color → seven 22px swatches of the goal palette with a 1px `fg` ring on the selected one; date → native `<input type="date">` inside the `TextField` box with `color-scheme: dark`; points → segmented 1–5.
- **Goal sheet:** title, type (habit | project, locked once the goal has tasks), color, priority, start, due, target per week (habit only), area, related goal (select), objective, done when, slug (edit mode). Edit mode adds a status row (`active · paused · done · abandoned`) and a `delete goal` danger text button with an inline confirm (`type the goal title to delete`).
- **Task sheet:** goal (select, preselected), title, points, then for projects due date + tag (`critical`, `milestone`, free text) + status; for habits weekdays + time. Notes. Checklist section (projects): rows with `TrakkCheckbox`, inline add field (`› add item` + Enter), delete on hover. Edit mode: `delete task` danger text button with inline confirm.
- **Reminder sheet:** label, kind (segmented), goal (optional), time, recurring weekdays **or** once date, channels (telegram, email toggles), active toggle. Edit mode: delete.
- **Replan sheet:** list of overdue tasks with checkboxes (all selected), target segmented `today · tomorrow · next monday · pick date`, save button `move N tasks`.
- **Check-in sheet:** score line (`9/14 pts · 64%` with progress bar), today's `ActionRow`s, `close the day` accent button.
- **Command palette** (`components/trakk/command-palette.tsx`): opens on ⌘K / Ctrl+K and on click of the command bar; centered 560px panel at 15vh, `surface`, `line` border, input row identical to the command bar; results grouped under mono micro labels (`commands`, `goals`, `tasks today`), rows 36px with arrow-key navigation and Enter. Commands: new goal, new task, new reminder, check-in, replan, go to dashboard, go to goals, settings, sign out. Goals and today's tasks come from an optional Inertia prop `commandIndex` loaded on open (`router.reload({ only: ['commandIndex'] })`); selecting a today task toggles its completion.
- **Empty states:** see the section below. Every surface listed there is part of the session that builds that surface.

## Empty states (owner rule: no component is ever blank)

**Rule:** a panel, card, chart, list, column, sheet section or palette never renders blank and never disappears because it has no data. It shows an `EmptyState` (or, for charts and grids, its empty structure plus a caption). Panels in the same row keep their height.

**`EmptyState` component** (`components/trakk/empty-state.tsx`):
- 1px dashed `line-strong` box, `rounded-control`, padding 14px, fills the panel body (`flex-1`, min-height 88px in panels, 56px in kanban columns and sheet sections).
- Line 1: mono 11px. Leading glyph: `›` in `accent` for neutral ("nothing yet"), `✓` in `accent` for positive ("all clear"), `!` in `warning` for a gap the owner should fix. Text in `body`.
- Line 2 (optional): mono 10px `faint`, one useful fact (the next deadline, what will appear here).
- Action (optional): one `TrakkButton size="sm"` that opens the right sheet.
- No blinking cursor or `_` anywhere, including log-style empties (owner decision in DESIGN.md).
- `role="status"`; never a spinner, illustration or emoji.

**Charts and grids keep their structure:** the points chart draws 30 placeholder bars (2px, `bar-low`) and the target line; the heatmap draws all cells at `heat-0`; the pace chart draws axes and the expected line. A caption sits on top of the structure.

**Copy, surface by surface** (`{}` are server-filled values):

| Surface | State | Copy |
| --- | --- | --- |
| KPI · points today | no points | value `0` `/14`, sub `first check of the day` |
| KPI · streak | 0 | `0 days`, sub `start one today` (record line when a record exists) |
| KPI · this week | no data last week | value as usual, sub `no data last week` instead of a % |
| KPI · goals on pace | no active goals | `—`, sub `no active goals` |
| KPI · overdue | 0 | `0` in `accent`, sub `all clear` |
| KPI · on time · 30d | no deadlines | `—`, sub `no deadlines in 30d` |
| Today | nothing scheduled | `› no actions scheduled today` · `{next action day}` or `add a task with weekdays or a due date` · `+ task` |
| Overdue (+ suggestion) | nothing overdue | `✓ nothing overdue` · `next deadline: {title} · {day}` or `no deadlines set` (suggestion box not rendered) |
| Next 7 days | nothing in window | `› nothing due in the next 7 days` · `next: {title} · {date}` or `+ task` |
| Goals grid | no active goals | `› no active goals` · `goals you create show their pace here` · `+ goal` |
| Goals grid | filter empty | `› no habit goals` / `› no project goals` / `✓ every goal is on pace` (off pace) |
| Points · 30 days | no points ever | structure + `› no points yet · check an action to start`; stats show `—` |
| Consistency | no history | structure + `› your first good day lights the first cell` |
| Reminders | none | `› no reminders` · `telegram not linked · settings` when no chat id · `+ reminder` |
| Focus project | no active project | `› no active project` · `the most behind project shows its board here` · `+ goal` |
| Kanban column | empty | to do `› no tasks · + task`, doing `› nothing in progress`, done `› nothing done yet` |
| Log | empty | `› nothing logged yet` |
| Footer status | no reminders / telegram off | `no reminders set` / `telegram off · link in settings` |
| First run (zero goals) | dashboard | first-run panel above everything: `› trakk init` + three steps (create a goal, add tasks, link telegram), each with its button and a `✓` once done; other panels show their own empty states |
| Goal · objective | no description / no done criteria | `› no objective yet · edit` / `! done when: not defined · a goal without an end never reaches 100%` |
| Goal · pace chart | before start / project without tasks | structure + `› starts in {n} days` / `› add tasks to measure progress` |
| Goal · progress | no tasks | `0/0`, `› add the first task` · `+ task` |
| Goal · board | no tasks at all | one `EmptyState` across the board: `› no tasks yet` · `break the goal into steps` · `+ task` |
| Goal · habit actions | none | `› no actions yet` · `add what you will repeat each week` · `+ task` |
| Goal · reminders | none | `› no reminders for this goal` · `+ reminder` |
| Goal · history | none | `› no history yet` |
| Goals list (`/goals`) | group empty | one line per group: `› no paused goals` (groups never disappear) |
| Command palette | no matches | `› no matches for "{q}"` · `try a goal or task name` |
| Task sheet · checklist | none | `› add the first item` (the inline add field sits below) |
| Check-in sheet | no actions | `› no actions today` · `you can still close the day`; button stays enabled |
| Replan sheet | nothing overdue | `✓ nothing to replan`; save button hidden |
| Settings · telegram | not linked | `! not linked · run php artisan telegram:link` |

**Tests:** one feature test renders the dashboard and a goal page for a user with no data and asserts the empty-state props; `tests/Browser/ScreensTest.php` adds an `empty` screenshot of the dashboard and of a goal with no tasks (1440px), saved for visual review.

## Reminders delivery

- `routes/console.php`: `reminders:dispatch` every minute (without overlapping), `tasks:scan-overdue` daily at 00:05 owner time, `goals:scan-deadlines` daily (activity `deadline_near` at 14 and 3 days before due).
- `ReminderService::due($now)` → active reminders whose slot matches in the owner's timezone and whose `last_sent_at` is before the slot. Each dispatches `SendReminder` (queued, 3 tries). On success: `last_sent_at`, `last_error = null`, activity `reminder_sent`. On final failure: `last_error`, activity `reminder_failed`.
- Messages (`App\Services\Reminders\Messages\*`, short plain text, no emoji, one fact per line):
  - daily brief: date, points target, today's actions with points, overdue count, focus project line.
  - evening check-in: points so far vs target, open actions, link to the dashboard.
  - tasks due tomorrow: list (goal-scoped reminders filter to their goal).
  - weekly review: week points vs last week, streak, goals behind/ahead.
  - goals off pace: behind goals with delta.
  - pace summary (goal): progress, expected, delta, required pace.
  - deadline alert (goal, once): days to due, open tasks.
  - custom: the label.
- `TelegramClient::send(chatId, text)` via the Bot API with `Http::retry(2, 500)`. `TELEGRAM_BOT_TOKEN` in `.env`, read through `config/services.php`. Mail channel uses a `ReminderMail` mailable (log mailer locally).
- `php artisan telegram:link`: asks the owner to send `/start` to the bot, polls `getUpdates` for up to 60s, saves the chat id on the owner and sends a confirmation.
- Queue worker and scheduler run inside `composer run dev` (`DevCommands`), per CLAUDE.md.

## MCP (local)

- `TrakkServer` with `get-today`, `list-goals`, `complete-task`, `create-task`, `move-task`, `create-goal` (PRODUCT.md → MCP tools) plus `uncomplete-task` and `replan-tasks`. All call the same services as the web; each logs an activity with `source = mcp`.
- `Mcp::local('trakk', TrakkServer::class)`; the user is the owner (`OWNER_EMAIL`).
- `complete-task` accepts `task_id` or `title`; fuzzy match by `TaskLookupService` (case/accent-insensitive contains, then trigram similarity via `pg_trgm` if installed, else Levenshtein on today's tasks). Ambiguous → `Response::error` listing candidates with ids.
- Register in Claude Code: `claude mcp add -s project trakk -- php artisan mcp:start trakk`. Document in README how to add it to Claude Desktop too.

## Read models and performance

- `DashboardService::for(User, CarbonImmutable $today): DashboardData` and `GoalDetailService::for(Goal, $today)`. Fixed number of queries regardless of data size (assert with a query-count test using the demo seed: dashboard ≤ 20 queries).
- Pure calculators get collections already loaded; no queries inside calculators.

## Removing the mocks

Last step of the last session: delete `app/Support/Mocks/`, `TopBarMock` usage, the mock-phase sums in `use-dashboard-state.ts` / `use-goal-board-state.ts` (keep only optimistic UI state), the `abort_unless($goal === 'nomad-visa')`, and the M3a note in ROADMAP (tick M3/M4 instead). `grep -ri mock app resources/js` returns nothing outside tests.

## Sessions

Each session: start in plan mode, read this spec section + the docs it names, commit per slice, keep `composer test`, `composer analyse`, `npm run types` green, ask the `reviewer` subagent before the last commit, tick ROADMAP.

| Session | Scope | ROADMAP |
| --- | --- | --- |
| S1 | Domain + rules: enums, migrations (with the changes above), models, factories, policies, `Clock`, `Labels`, all calculators and write services with unit/feature tests, `DemoSeeder` relative to today, owner + default reminders seeding. No UI changes. | M1, M2 |
| S2 | Dashboard real: `DashboardService`, top bar data, Today check/uncheck, reminder toggle, focus kanban moves, replan sheet, check-in sheet, suggestion, log, footer status, empty states. Browser screenshots still match. | M3 |
| S3 | Goal detail real + all CRUD: `GoalDetailService`, goal sheet (create/edit/status/delete), task sheet + checklist, habit actions panel, reminder sheet, `/goals` list, command palette, settings fields. | M4, M5 (CRUD part) |
| S4 | Delivery + MCP + cleanup: dispatch/scan commands, jobs, messages, Telegram client, `telegram:link`, test button, MCP server and tools with tests, mcp status in top bar, remove mocks, final full review. | M5 |

## Done when

- Every row in "Feature inventory" works against the database and survives a page reload.
- Every surface in "Empty states" shows its empty state for a user with no data; nothing renders blank.
- `php artisan migrate:fresh --seed` in local renders screens matching the reference PNGs; in production with no demo data, the first-run panel appears.
- A reminder set for the next minute arrives in Telegram while `composer run dev` is running.
- In Claude Code, "I did 30 minutes of speaking today" completes the right task and the dashboard shows the points after reload.
- No mocks remain; all checks green.
