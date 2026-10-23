---
paths:
  - "app/Models/**"
  - "app/Enums/**"
  - "database/**"
---

# Models, enums, database

## Models

- Allowed: relations (with return types), `casts()`, small query scopes (`scopeActive`, `scopeDueBefore`), simple accessors (`isHabit()`).
- Not allowed: business rules, calculations of progress or points, calls to services, notifications.
- Cast enum columns to their enum, dates to `immutable_date`, jsonb arrays to `array` (or `AsCollection`).
- Every model has a factory with useful states (`Goal::factory()->habit()->behindPace()`).
- Prevent lazy loading in non-production (`Model::preventLazyLoading()`); eager load in read models.

## Enums

- String-backed, singular names: `GoalType::Habit`, `TaskStatus::Doing`, `Priority::High`, `PaceStatus::Behind`.
- May have `label()` and small mapping methods. No database or service access.
- The frontend receives enum values as strings; matching TS union types live in `resources/js/types/`.

## Migrations

- One migration per table in the MVP; later changes are new migrations, never edits to old ones once pushed.
- Postgres types: `jsonb` for arrays, `date` for calendar dates, `timestampTz` for instants, `string` for enums (no native Postgres enums).
- Foreign keys with `constrained()->cascadeOnDelete()`; indexes from `docs/ARCHITECTURE.md`.
- Keep `docs/ARCHITECTURE.md` "Data model" in sync with every migration.

## Seeders

- `DatabaseSeeder` creates the owner account and the default reminders (once), and calls `DemoSeeder` only in `local` and `testing`. Production starts empty.
- `DemoSeeder` reproduces the prototype data (6 goals, today's 8 actions, the nomad visa board with 20 tasks, history of completions, reminders and a log) so the UI can be compared with `docs/design/reference/*.png`. Every date is counted from today, so the screens look the same whichever day it runs. It does nothing when the owner already has goals.
