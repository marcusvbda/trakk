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
- Un-checking a recurring action deletes that day's completion.
- **Project task done ⇔ completion.** Moving a project task to `done` sets `completed_at` and creates the completion for today with the task's points; moving it out of `done` clears `completed_at` and deletes that completion, whatever day it was. Checking a one-off task in Today moves it to `done`, and unchecking it moves it back to `todo`.
- A one-off task has at most one completion. A recurring task has at most one per day and can be completed on any day, scheduled or not, so logging extra work never fails.
- Completing a task that is already completed (same day) changes nothing and logs nothing.

### Tasks

- Status: `todo`, `doing`, `done`. Recurring tasks only use done-per-day via completions; their `status` stays `todo`.
- Recurrence: `days_of_week` (ISO 1–7). Null means one-off. Habit goals hold recurring tasks (at least one weekday); project goals hold one-off tasks.
- Points are 1–5. A goal's type cannot change once it has tasks.
- Checklist items are added at the end, can be renamed, ticked and deleted, and never score.
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

- **Schedule.** A reminder has a `time`, one or more `channels` (`telegram`, `email`), and either `days_of_week` (recurring) or `fire_on` (once, a date), never both. It can be attached to a goal; `pace_summary` and `deadline_alert` must be. It is sent at most once per scheduled slot.
- **Frequency label.** `daily` (7 days), `weekdays`, a single day name (`sunday`), a list of short names (`mon, wed, fri`), `day before` (kind `due_tomorrow`), or the date for once (`01 jan`).
- **Kinds:** `daily_brief` (morning summary of today), `evening_checkin` (what is still open, points left), `due_tomorrow` (tasks due tomorrow), `weekly_review` (week summary, goals off pace), `off_pace` (goals behind), `pace_summary` (goal), `deadline_alert` (goal, usually once), `custom` (free text).
- **Defaults**, created once with the owner (when the owner has no reminders): daily brief 07:30 daily · evening check-in 21:30 daily · tasks due tomorrow 18:00 daily (all telegram) · weekly review 10:00 sunday (telegram + email) · goals off pace 09:00 monday (email, inactive).
- Pausing or enabling a reminder is logged.

### Check-in

The check-in sheet shows today's actions (checkable) and the day score. `close the day` logs one `check_in` activity with the score and the target, text `check-in · target hit` or `check-in · N pts short`. At most one check-in per day: closing the day again updates the first.

### Replan

For each selected overdue task, set `due_date` to today, tomorrow, next Monday (the Monday after today, so a Monday goes to the following one) or a picked date (today or later). One `replanned` activity per task. Done and recurring tasks cannot be replanned.

### Suggestion (rule-based, no AI)

Take the active goal with the lowest delta that has overdue tasks. `n` = its overdue count. Text: `knock out the {n} overdue {goal title lowercased} {task|tasks} {when}. It unblocks your most behind goal and clears {n} {item|items} from red.` where `when` = `today` if n ≤ 2 or today is Saturday or Sunday, else `on Saturday morning`. If no goal has overdue tasks but some task is overdue, use the task with the most days late: `clear "{task}" today, it is {d} days late.` Nothing overdue: no suggestion.

### Activity log

- **Activity → log kind:** `task_completed`, `check_in` → `done`, `checkin` · `task_undone`, `reminder_paused` → `undone` · `task_moved`, `reminder_sent`, `reminder_enabled`, `goal_created`, `task_created`, `replanned`, `mcp_call` → `sent` · `task_reopened` → `reopened` · `task_overdue`, `deadline_near`, `reminder_failed` → `warning`.
- `timeLabel`: `HH:MM` today, `yday HH:MM` yesterday, otherwise `dd mon`, in the owner's timezone.
- Activities carry a `source`: `web`, `mcp` or `system`. Activities from MCP end their text with ` · claude`.
- The log survives deleting a goal or task.

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
