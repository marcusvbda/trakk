---
paths:
  - "tests/**"
---

# Tests (Pest 4)

Goal: a regression net that catches broken rules and broken endpoints. Not coverage for its own sake.

## What to test

| Layer | Test type | Cover |
| --- | --- | --- |
| Calculators (`GoalProgressService`, `ScoreService`, `StreakService`, `ConsistencyService`) | Unit | Every rule in `docs/PRODUCT.md`, edge cases via datasets: thresholds (−5, 0, +5), empty goals, before start, after due, week boundaries, timezone day boundaries. |
| Write services (`GoalService`, `TaskService`, `CompletionService`) | Feature (DB) | State change and the main invariant (e.g. completing twice on the same day creates one completion). |
| HTTP routes | Feature | Happy path, one validation failure, another user's resource returns 403/404. |
| MCP tools | Feature | One success via `TrakkServer::actingAs($user)->tool(...)`, one error case. |
| Scheduler / reminders | Feature | Due reminder dispatches the job once per slot (`Queue::fake()`, `Http::fake()` for Telegram). |
| Bugs | Any | A test that fails before the fix. |

## What not to test

Framework features, Eloquent relations by themselves, getters, enum labels, starter-kit auth screens, React markup snapshots.

## How

- `describe()` per method or behaviour, `it('...')` sentences that read as rules: `it('marks a goal behind when progress is 5 points under expected')`.
- Arrange with factories and states; no manual inserts.
- Freeze time with `$this->travelTo()` or pass `$today` explicitly. Never depend on the real date.
- Postgres test database (`trakk_test`) with `RefreshDatabase`, so behaviour matches production.
- `Http::fake()` for Telegram, `Queue::fake()` / `Notification::fake()` where relevant. No real network calls.
- One assertion focus per test; several `expect()` calls are fine when they describe one outcome.
- Run focused while working (`php artisan test --filter=...`), full suite before finishing.
