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

The rules below live in services and are covered by tests. The dashboard assembly (Today list, KPI cards, suggestion) is `DashboardService`. Dates use the owner's timezone (`users.timezone`, default `Europe/Dublin`).

### Goal fields

`title`, `slug`, `icon` (optional), `description` (the objective), `done_criteria`, `type` (habit | project), `color` (one of the goal palette in DESIGN.md), `priority` (high | medium | low), `start_date`, `due_date`, `target_per_week` (habit only, 1–7), `area` (optional, free text), `related_goal` (optional link to another goal), `status` (active | paused | done | abandoned).

- `slug` is unique per user, generated from the title (`nomad-visa`, then `nomad-visa-2` on a clash) and editable.
- `due_date` must be after `start_date`.
- `target_per_week` is required for habits and must be null for projects.
- Only `active` goals appear on the dashboard and count in KPIs.

### Progress

- **Project:** `done tasks / total tasks`, as a whole percent. A project with no tasks is 0%.
- **Habit:** `hit days so far / planned days`, as a whole percent capped at 100. A hit day is a day with at least one completion of any task of the goal (two tasks on the same day count once), inside the goal window. Planned days = `target_per_week × weeks between start_date and due_date` (weeks as a decimal). Week dots (dashboard goal cards only) show hit days Monday to Sunday: hit, missed (past without a hit) or upcoming (today without a hit yet, and later days).

### Expected progress and pace

- `expected = elapsed days / total days × 100`, where total = days from `start_date` to `due_date` and elapsed = days from `start_date` to today (clamped to `[0, total]`). Rounded to a whole percent.
- `delta = progress − expected`.
- Status: `delta ≤ −5` → **behind** (danger) · `−5 < delta < 0` → **watch** (warning) · `0 ≤ delta < 5` → **on pace** · `delta ≥ 5` → **ahead**.
- "Goals on pace" KPI counts goals with `delta ≥ 0`.
- **Projection at due date:** `progress + (progress / elapsed days) × remaining days`, capped at 100. Shown on the goal page.
- **Current pace:** done tasks per week since start (projects) or hit days per week (habits), one decimal (0 before the start). **Required pace:** remaining tasks (projects) or remaining planned hit days (habits) per remaining week, one decimal; at least one day remains, so the figure stays finite after the due date. The goal page labels them `tasks/wk` or `days/wk`.

### Goal page

- **Pace chart.** The actual line has five samples evenly spaced from the start day to today, both included (fewer when little time has passed: a sample day never repeats). The percent at a sample day is the progress counting only what was done up to that day: a project task counts from the day of its completion, a habit counts hit days up to it; the last sample is today's real progress. Month ticks are the first of each month inside the window, dropping any that sits closer than 18% of the window to the previous tick, the start or the due date (a goal from 01 jul to 15 jan shows `sep` and `nov`).
- **Chart captions.** Before the start the chart says `starts in N days`; a started project without tasks says `add tasks to measure progress`. The axes and the expected line are always drawn.
- **Board or actions.** A project shows its tasks on the board, every task, by status and `position`. A habit shows its recurring tasks instead: title, weekday chips, time, points and `hits/planned this week`. One-off tasks of a habit goal are not listed there.
- **Progress panel.** Projects: percent, done/total tasks and tasks per status. Habits: the same card shows two progresses, the total (percent, hit days / planned days) and the weekly score of the current week (`week N`, checks / possible checks, a bar). No new card (owner decision).
- **Weekly score (habits).** Weeks run Monday to Sunday. Week 1 is the week the goal was created in, even when it was created mid-week: earlier days of that week can still be checked and count. A possible check is one scheduled weekday of one recurring action (an action on Monday to Friday is 5 checks); `target_per_week` does not drive the score, and the score can never pass the possible checks. Each action shows its schedule as the seven weekday chips (read-only, scheduled days in the goal color) and, next to them, one checkbox per scheduled day (`mon`, `wed`...). A checkbox marks that slot as done and is stored on the slot's own date, whichever day it is ticked on: ticking Friday's workout on Saturday counts as Friday's check. Slots of future days are locked; ticking works for any scheduled day of the current week up to today. A completion on a day the action is not scheduled does not count, and neither does it show in the action's `hits/planned this week`. A new week starts on Monday; the closed weeks are listed in the goal's history as `week N · done of total` (latest first, at most 12), computed from the data and counting only actions that already existed by the end of that week.
- **Reminders of the goal** list recurring ones first, then once-only ones, each in creation order. **History** is the latest 20 activities of the goal.
- **Facts.** `remaining` is the days left to the due date (0 once past); `overdue` counts the goal's overdue tasks; the frequency shows only for habits.
- **Slug.** A goal URL resolves the slug among the signed-in user's goals only; two users can share a slug.

### Points

- Completing a task stores a completion with the task's points at that moment (snapshot).
- Day score = sum of completion points for that date.
- Un-checking a recurring action deletes that day's completion.
- **Project task done ⇔ completion.** Moving a project task to `done` sets `completed_at` and creates the completion for today with the task's points; moving it out of `done` clears `completed_at` and deletes that completion, whatever day it was. Checking a one-off task in Today moves it to `done`, and unchecking it moves it back to `todo`.
- A one-off task has at most one completion. A recurring task has at most one per day and can be completed on any day, scheduled or not, so logging extra work never fails.
- Completing a task that is already completed (same day) changes nothing and logs nothing.

### Tasks

- Status: `todo`, `doing`, `done`. Recurring tasks only use done-per-day via completions; their `status` stays `todo`.
- Recurrence: `days_of_week` (ISO 1–7). Null means one-off. Habit goals hold recurring actions and may also hold one-off tasks with a due date (a mock test, a weigh-in); project goals hold one-off tasks only.
- Points are 1–5. A goal's type cannot change once it has tasks, and a task cannot change between recurring and one-off. A task in a habit goal has weekdays or, as a one-off, a due date.
- Deleting a task or goal also deletes its completions, so its points leave the history (streak, charts, week totals); the activity log is kept.
- Checklist items are added at the end, can be renamed, ticked and deleted, and never score.
- Overdue: a one-off task not `done` with `due_date` before today. Shown with how many days late (`−3d`).
- "Due today": one-off task not done with `due_date` = today; shown in Today with `DUE TODAY`.
- Today list = recurring tasks scheduled for today's weekday + recurring tasks checked today (so a habit logged on an unscheduled day stays and can be unchecked) + late recurring tasks + one-off tasks due today + one-off tasks in `doing` without a due date + one-off tasks completed today (so a row you just checked stays and can be unchecked). Sorted by `scheduled_time` (tasks without one last), then priority of the goal, then position.
- **Late habit action.** A recurring task is late when a day of the current week (Monday to yesterday) on which it was scheduled has no check covering it: a check on a day covers every scheduled day up to it, and days before the task was created never count. It stays in Today, unchecked, marked `LATE −Nd` where N is the days since the oldest uncovered scheduled day; checking it today clears the lateness. Late habit actions only affect the Today list: they are not in Overdue, the KPIs, the suggestion or replanning, and the limit of the current week keeps old misses from piling up.
- Overdue, Next 7 days, the suggestion and the KPIs only look at tasks of **active** goals.
- **Goal name.** Wherever a task or goal is named next to other text (Today, Overdue, suggestion, log lines, focus project), the name is the goal title up to its first ` · `, lowercased: `Nomad visa · Spain` becomes `nomad visa`.

### KPIs

| KPI | Rule |
| --- | --- |
| Points today | Day score vs daily target, with % of target. |
| Streak | As defined above, with the all-time record. Sub-line: `record · N days`, or `start one today` when no day has ever been good. |
| This week | Points from Monday to today vs the same span last week, as a % change (no percentage when last week had no points). |
| Goals on pace | Active goals with `delta ≥ 0` / active goals. No active goals: `—`. |
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

Take the active goal with the lowest delta that has overdue tasks. `n` = its overdue count. Text: `knock out the {n} overdue {goal title lowercased} {task|tasks} {when}. It unblocks your most behind goal and clears {n} {item|items} from red.` where `when` = `today` if n ≤ 2 or today is Saturday or Sunday, else `on Saturday morning`. If no goal has overdue tasks but some task is overdue, use the task with the most days late: `clear "{task}" today, it is {d} days late.` Nothing overdue: no suggestion. Because Overdue only covers active goals, every overdue task belongs to an active goal, so the "task with the most days late" fallback of the spec never applies and is not built.

### Activity log

- **Activity → log kind:** `task_completed`, `check_in` → `done`, `checkin` · `task_undone`, `reminder_paused` → `undone` · `task_moved`, `reminder_sent`, `reminder_enabled`, `goal_created`, `task_created`, `replanned`, `mcp_call` → `sent` · `task_reopened` → `reopened` · `task_overdue`, `deadline_near`, `reminder_failed` → `warning`.
- `timeLabel`: `HH:MM` today, `yday HH:MM` yesterday, otherwise `dd mon`, in the owner's timezone.
- Activities carry a `source`: `web`, `mcp` or `system`. Activities from MCP end their text with ` · claude`.
- The log survives deleting a goal or task.

## MCP tools

| Tool | Kind | Purpose |
| --- | --- | --- |
| `get-today` | read-only | Today's actions, points vs target, overdue, due soon. |
| `list-goals` | read-only | Active goals with progress, expected and pace. |
| `complete-task` | write, idempotent | Mark a task done for today, by id or by fuzzy title. Returns new day score. |
| `uncomplete-task` | write, idempotent | Undo today's completion (a mistake). |
| `create-task` | write | Add a task to a goal (title, points, due date or recurrence). |
| `move-task` | write, idempotent | Change a project task's status. |
| `create-goal` | write | Create a goal with all fields. |
| `replan-tasks` | write, idempotent | Push open one-off tasks (usually overdue) to today, tomorrow, next Monday or a date. |

Tools call the same services as the web app. They never duplicate rules.

### Finding a task by title

- An id always wins. Otherwise the title is normalised (lowercase, ASCII, letters and digits only) and matched in this order: exact, then contained (either text contains the other), then the closest title among today's actions with similarity ≥ 0.6.
- Several matches: the only one on today's list wins; otherwise the tool answers with the candidates' ids and asks which one. A closest-title guess within 0.05 of the runner-up is also ambiguous.
- A title with no letters or digits never matches.

### Reminders and scans

- A reminder is sent when the current minute is its slot or up to 10 minutes after it; a slot that failed is not retried.
- `goals:scan-deadlines` logs a `deadline_near` warning at 14 and at 3 days left, once per goal and day count. `tasks:scan-overdue` logs `task_overdue` once per task and due date. Both run hourly and are idempotent, so the first run after the owner's midnight is the one that logs.
