---
paths:
  - "app/**"
  - "routes/**"
  - "database/**"
  - "config/**"
---

# PHP

## Style

- `declare(strict_types=1);` at the top of every file.
- Classes are `final` unless something extends them. Services, controllers, requests, resources, jobs, tools, data objects: `final`.
- Constructor property promotion with `private readonly` for dependencies.
- Full types on every property, parameter and return. `void` and `never` where they apply.
- Pint (Laravel preset) decides formatting. Do not hand-format against it.
- Prefer Laravel 13 PHP attributes where the framework offers them (models, jobs, commands) over property arrays. Check with Boost `search-docs` when unsure.

## Naming

- Classes: nouns by role. `GoalProgressService`, `StoreGoalRequest`, `GoalResource`, `GoalData`, `SendReminder`.
- Methods: verbs that say the outcome. `complete()`, `moveTo()`, `expectedPercent()`, `isOverdue()`.
- Booleans read as questions: `$isDone`, `$hasChecklist`, `isOverdue()`.
- No abbreviations except well-known ones (`id`, `url`). Input/output objects are suffixed `Data`, not `Dto`.
- Database columns snake_case; PHP properties and array keys sent to the frontend camelCase through Resources.

## Clean code

- A method does one thing. If it needs a comment to explain its sections, split it.
- Early return. No `else` after `return` or `throw`.
- No magic values: enums for sets, named constants or config for numbers (`GOOD_DAY_RATIO = 0.7`).
- Collections over manual loops when they read better; plain `foreach` when the collection chain gets clever.
- Exceptions for broken invariants (domain exceptions in `app/Exceptions`), validation for user input.
- Comments only for a non-obvious *why*. Never commented-out code. Never TODOs without a ROADMAP entry.

## Dates

- Use `CarbonImmutable` everywhere (`Date::use(CarbonImmutable::class)` in `AppServiceProvider`).
- Rules receive `$today` from the caller; only the `Clock` service reads the current time.
