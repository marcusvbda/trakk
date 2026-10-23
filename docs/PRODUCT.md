# Trakk — Product

## What it is

A personal app to keep long-term goals in focus and move a little toward them every day. One user (the owner), dark dense dashboard, reminders on Telegram, and an MCP server so Claude can read and write the same data ("I did 40 min of speaking today").

## Problems it solves

1. Goals get forgotten between the moment they are set and the deadline.
2. There is no honest signal of whether a goal is on pace until it is too late.
3. Logging progress is friction, so it stops after a week.

## Decisions

Owner-approved, from `docs/specs/real-features.md`.

1. **No AI in the app yet.** The overdue suggestion is rule-based. The command bar (⌘K) is search plus commands, with the placeholder `search or run a command…`. `OPENAI_*` stays unused until a separate AI milestone.
2. **Scope of the real-data round.** In: real data, every interaction and form, reminders delivered to Telegram and email, the local MCP server. Out: deploy, Passport and remote MCP, PWA, inbound Telegram commands (all stay in M6 / Later).
3. **Goal URLs use a slug** (`/goals/nomad-visa`): unique per user, generated from the title, editable.
4. **New surfaces reuse the existing tokens and components.** No new design tokens.
5. **Production starts empty.** Only the owner account and the default reminders exist. `DemoSeeder` runs only in `local` and `testing` and builds its data relative to today.

## MVP scope

In:
- Goals of two types: **habit** (recurring actions, scored) and **project** (one-off tasks with deadlines, kanban).
- Tasks with points, optional due date, optional recurrence, checklist items.
- Today view with check-in, daily points against a target.
- Dashboard: KPIs, today, overdue, next 7 days, goal cards with pace, 30-day points chart, 12-week heatmap, reminders, focus project kanban, activity log.
- Goal detail page: facts, objective, pace chart, progress, board, reminders, history.
- Reminders sent to Telegram (and email as fallback).
- MCP server with a small set of tools.

Out (later):
- Multi-user, sharing, teams.
- Native mobile app (PWA first).
- AI features inside the app (the AI lives in Claude via MCP).
- Finance, notes, documents, journaling modules.
- Inbound Telegram commands (check-in by replying to the bot).

## Glossary

| Term | Meaning |
| --- | --- |
| Goal | Something with a start date, a due date and a "done when" criterion. Type `habit` or `project`. |
| Task | A unit of work inside a goal. One-off (project) or recurring (habit action). Worth 1–5 points. |
| Checklist item | A sub-step of a task ("0/3 items"). Does not score. |
| Completion | The record that a task was done on a given date. Recurring tasks have one per scheduled day. |
| Daily target | Points the owner aims for per day. Default 14. |
| Good day | A day with points ≥ 70% of the daily target (rounded up: 10 of 14). |
| Hit day | A day on which a habit goal had at least one completion, on any of its tasks. |
| Target hit | A day with points ≥ the daily target. |
| Streak | Consecutive good days ending today (if today is already good) or yesterday. |
| Pace | Progress compared with how far through its time window the goal is. |
| Activity | An event in the log feed (task done, task moved, reminder sent, task became overdue, check-in). |

## Business rules

All rules below are implemented in services and covered by unit tests. Dates use the owner's timezone (`users.timezone`, default `Europe/Dublin`).

### Goal fields

`title`, `slug`, `icon` (optional), `description` (the objective), `done_criteria`, `type` (habit | project), `color` (one of the goal palette in DESIGN.md), `priority` (high | medium | low), `start_date`, `due_date`, `target_per_week` (habit only, 1–7), `area` (optional, free text), `related_goal` (optional link to another goal), `status` (active | paused | done | abandoned).

- `slug` is unique per user, generated from the title (`nomad-visa`, then `nomad-visa-2` on a clash) and editable.
- `due_date` must be after `start_date`.
- `target_per_week` is required for habits and must be null for projects.
- Only `active` goals appear on the dashboard and count in KPIs.

### Progress

- **Project:** `done tasks / total tasks`, as a whole percent. A project with no tasks is 0%.
- **Habit:** `hit days so far / planned days`, as a whole percent capped at 100. A hit day is a day with at least one completion of any task of the goal (two tasks on the same day count once), inside the goal window. Planned days = `target_per_week × weeks between start_date and due_date` (weeks as a decimal). Week dots show hit days Monday to Sunday: hit, missed (past without a hit) or upcoming (today without a hit yet, and later days).

### Expected progress and pace

- `expected = elapsed days / total days × 100`, where total = days from `start_date` to `due_date` and elapsed = days from `start_date` to today (clamped to `[0, total]`). Rounded to a whole percent.
- `delta = progress − expected`.
- Status: `delta ≤ −5` → **behind** (danger) · `−5 < delta < 0` → **watch** (warning) · `0 ≤ delta < 5` → **on pace** · `delta ≥ 5` → **ahead**.
- "Goals on pace" KPI counts goals with `delta ≥ 0`.
- **Projection at due date:** `progress + (progress / elapsed days) × remaining days`, capped at 100. Shown on the goal page.
- **Current pace** (projects): done tasks per week since start, one decimal (0 before the start). **Required pace:** remaining tasks / remaining weeks, one decimal; at least one day remains, so the figure stays finite after the due date.

### Points

- Completing a task stores a completion with the task's points at that moment (snapshot).
- Day score = sum of completion points for that date.
- Un-checking an action or reopening a done project task deletes that day's completion.
- Moving a project task to `done` creates its completion; moving it out of `done` deletes it.

### Tasks

- Status: `todo`, `doing`, `done`. Recurring tasks only use done-per-day via completions; their `status` stays `todo`.
- Recurrence: `days_of_week` (ISO 1–7). Null means one-off.
- Overdue: a one-off task not `done` with `due_date` before today. Shown with how many days late (`−3d`).
- "Due today": one-off task not done with `due_date` = today; shown in Today with `DUE TODAY`.
- Today list = recurring tasks scheduled for today's weekday + one-off tasks due today + one-off tasks in `doing`. Sorted by `scheduled_time`, then priority of the goal.

### KPIs

| KPI | Rule |
| --- | --- |
| Points today | Day score vs daily target, with % of target. |
| Streak | As defined above, with the all-time record. |
| This week | Points from Monday to today vs the same span last week, as a % change (no percentage when last week had no points). |
| Goals on pace | Active goals with `delta ≥ 0` / active goals. |
| Overdue | Count of overdue tasks, with how many come from the most behind goal. |
| On time · 30d | One-off tasks with a due date in the last 30 days (today included) that were done on or before it, in the owner's timezone / all such tasks. A task due today that is still open is not late yet and is left out. No such tasks: no percentage. |

### Charts

- Points · 30 days: one bar per day (empty days included), colored by target hit / good day / below. Stats: average per day (one decimal), best day, days at target.
- Consistency · 12 weeks: Monday-based weeks ending with the current one, days that have not happened yet are empty. Heat level per day: 0 = no points, 1 = < 50% of target, 2 = 50% up to a good day, 3 = good day (≥ 70%), 4 = target hit.

### Reminders

Kinds: `daily_brief` (morning summary of today), `evening_checkin` (what is still open, points left), `due_tomorrow` (tasks due tomorrow), `weekly_review` (week summary, goals off pace), `off_pace` (goals behind), `custom` (free text). Each has a time, days of week, channel (`telegram` | `email`), active flag, optional goal. A reminder is sent at most once per scheduled slot.

## MCP tools (first version)

| Tool | Kind | Purpose |
| --- | --- | --- |
| `get-today` | read-only | Today's actions, points vs target, overdue, due soon. |
| `list-goals` | read-only | Active goals with progress, expected and pace. |
| `complete-task` | write, idempotent | Mark a task done for today, by id or by fuzzy title. Returns new day score. |
| `create-task` | write | Add a task to a goal (title, points, due date or recurrence). |
| `move-task` | write, idempotent | Change a project task's status. |
| `create-goal` | write | Create a goal with all fields. |

Tools call the same services as the web app. They never duplicate rules.
