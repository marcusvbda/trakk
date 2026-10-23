# Trakk

Personal goal tracker for one user: goals (habits and projects), daily actions worth points, a dense dark dashboard that shows whether each goal is on pace, reminders on Telegram, and an MCP server so Claude can read and log progress.

Everything in this repository is written in English: code, UI copy, comments, commits, docs.

## Read before working

| When | Read |
| --- | --- |
| Any feature or rule question | `docs/PRODUCT.md` (scope, glossary, business rules, formulas) |
| Backend structure, data model, MCP, reminders | `docs/ARCHITECTURE.md` |
| Anything visual | `docs/DESIGN.md`, then open `docs/design/prototype/*.html` and compare with `docs/design/reference/*.png` |
| What to build next | `docs/ROADMAP.md` (tick boxes as you finish) |
| Before editing a file | `.ai/rules/index.md`, then the rule file whose glob matches the path |

## Stack

Laravel 13 · PHP 8.4 · PostgreSQL 17 · Inertia 3 + React 19 + TypeScript · Tailwind CSS 4 · Wayfinder · Fortify · `laravel/mcp` · Pest 4 · Pint · Larastan · Laravel Boost (dev).

Local development runs natively (no Docker). Docker is only for the VPS deploy.

## Commands

```bash
composer run dev                 # server, queue, logs and Vite together
php artisan test --compact       # all tests (Pest)
php artisan test --filter=Pace   # focused run
vendor/bin/pint --dirty          # format changed PHP files
vendor/bin/phpstan analyse       # static analysis
npm run types                    # TypeScript check (tsc --noEmit)
npm run lint && npm run format   # ESLint + Prettier
php artisan migrate:fresh --seed # reset local DB with demo data that mirrors the prototype
php artisan mcp:inspector trakk  # inspect the local MCP server
```

If a script above does not exist yet, add it to `composer.json` / `package.json` as part of the milestone that needs it.

## Architecture in one screen

- **Controllers are thin.** Validate with a FormRequest, call one service method, return `Inertia::render()` or a redirect. No queries, no rules, no loops over models.
- **Business rules live in services** (`app/Services/{Domain}`). Services are `final`, stateless, constructor-injected, and receive `CarbonImmutable $today` instead of calling `now()`.
- **Data objects** (`app/Data`) carry input from requests to services and results back out. Services never receive a `Request`.
- **Models** hold relations, casts, and small query scopes. No business logic.
- **MCP tools are adapters** like controllers: they call the same services. A rule is never implemented twice.
- **Pages format, they don't compute.** Pace, points, streaks and KPIs come from the backend already calculated.

## Code style

- Clean, obvious code over clever code. Small methods with names that make comments unnecessary.
- No comments that restate code. Comment only a non-obvious *why*. No PHPDoc that repeats native types; use PHPDoc only for array shapes and generics.
- `declare(strict_types=1);` in every PHP file. Typed properties, parameters and return types everywhere.
- Backed enums for every fixed set of values. No magic strings for statuses or types.
- Early returns over nested `if`. No `else` after `return`.
- Follow existing patterns in the codebase before inventing new ones. Check sibling files first.
- Do not add packages without asking. Do not create base classes, interfaces or abstractions until a second real use exists.

## Tests (regression, not ceremony)

- Every service rule has a unit test with datasets for its edge cases (pace thresholds, streak breaks, week boundaries).
- Every route and MCP tool has a feature test for the happy path, validation, and ownership.
- Every bug fix starts with a failing test that reproduces it.
- Do not test framework behaviour, getters, starter-kit auth, or markup structure.
- Details: `.ai/rules/testing.md`.

## Design fidelity

The approved design must be reproduced exactly. Use the tokens from `docs/DESIGN.md` (already in `resources/css/app.css`), never raw hex values or default Tailwind greys. Build Trakk screens from the components listed in DESIGN.md. Before calling a screen done, compare it side by side with the matching reference PNG at 1440px and 390px.

## Definition of done

1. The behaviour matches `docs/PRODUCT.md`; if the docs were wrong, update them in the same change.
2. Tests pass: `php artisan test --compact`.
3. `vendor/bin/pint --dirty`, `vendor/bin/phpstan analyse` and `npm run types` are clean.
4. UI matches the reference images.
5. The relevant box in `docs/ROADMAP.md` is ticked.

## Working agreements

- Work in vertical slices: migration → model → service + unit test → request/controller/route + feature test → page/components. Follow the `trakk-feature` skill (`.ai/skills/trakk-feature/SKILL.md`).
- For UI work follow the `trakk-ui` skill (`.ai/skills/trakk-ui/SKILL.md`).
- After a slice, ask the `reviewer` subagent to check the diff before committing.
- Ask before: installing dependencies, changing the data model in `docs/ARCHITECTURE.md`, changing design tokens, deleting files, pushing to git.
- Never read or print `.env` secrets.
- Commits: small, imperative, Conventional Commits (`feat: add pace calculation for habit goals`).

## Agent setup

- Project rules: `.ai/rules/` (agent-agnostic; `.claude/rules` points to it). Record new durable conventions there, and keep `.ai/rules/index.md` in sync.
- Project skills: `.ai/skills/` (Boost copies them to `.claude/skills/` on `php artisan boost:update`).
- Laravel Boost adds its own guidelines block below this section on `boost:install`. Do not edit inside `<laravel-boost-guidelines>`; edit `.ai/guidelines/` instead.
- Use the Boost MCP tools (`search-docs`, `database-schema`, `last-error`, `browser-logs`) before guessing about Laravel APIs, the schema or a runtime error.
