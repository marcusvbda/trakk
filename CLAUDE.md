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

Local development runs natively (no Docker). Docker is only for the VPS deploy, where the app is served by FrankenPHP (see `docs/DEPLOY.md`).

## Commands

```bash
composer run dev                 # EVERYTHING the app needs locally: server, queue worker, scheduler, logs and Vite
php artisan test --compact       # all tests (Pest)
php artisan test --filter=Pace   # focused run
vendor/bin/pint --dirty          # format changed PHP files
vendor/bin/phpstan analyse       # static analysis
npm run types                    # TypeScript check (tsc --noEmit)
npm run check                    # lint + format check (Vite+); `npm run check:fix` to fix
npx vp check --fix resources/js  # scoped fix; never run it repo-wide, it reformats docs and prototypes
php artisan migrate:fresh --seed # reset local DB with demo data that mirrors the prototype
php artisan mcp:inspector trakk  # inspect the local MCP server
```

If a script above does not exist yet, add it to `composer.json` / `package.json` as part of the milestone that needs it.

## One command to run everything

- `composer run dev` is the only command needed to use the app locally. Never ask the owner to start a second terminal.
- Every process the app needs (queue worker, scheduler, a Telegram poller, anything added later) is registered with `DevCommands::artisan(...)` in `AppServiceProvider::registerDevProcesses()`. Add it there in the same change that introduces the need.
- External services (PostgreSQL) are the only things started outside `composer run dev`; `composer run setup` prepares a fresh clone.

## Production target: FrankenPHP

- Production serves the app with FrankenPHP (Caddy built in) in **classic mode**: one fresh PHP request per hit, like PHP-FPM. Laravel Octane is not installed, so no worker-mode state rules apply. Do not add Octane without asking.
- Write code that does not depend on the web server: no `$_SERVER` server-name checks, no nginx/Apache rewrites, no PHP-FPM-only functions (`fastcgi_finish_request`). Trust the framework (`Request`, `url()`, `asset()`).
- The document root is `public/`. HTTPS, compression and static assets are handled by FrankenPHP, so the app needs no proxy configuration.
- Queue worker and scheduler run as separate containers of the same image in production (`docs/DEPLOY.md`), mirroring the processes in `composer run dev`.

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

The approved design must be reproduced exactly. Use the tokens from `docs/DESIGN.md` (already in `resources/css/app.css`), never raw hex values or default Tailwind greys. Build Trakk screens from the components listed in DESIGN.md. Before calling a screen done, compare it side by side with the matching reference PNG at 1440px and 390px. Exception, by owner decision: the page container has no `max-w-*` (full width on wide screens), so do not add one back to match the prototype or the PNGs; judge the 1440px comparison at a 1440px viewport only.

## Definition of done

1. The behaviour matches `docs/PRODUCT.md`; if the docs were wrong, update them in the same change.
2. Tests pass: `php artisan test --compact`.
3. `vendor/bin/pint --dirty`, `vendor/bin/phpstan analyse` and `npm run types` are clean.
4. UI matches the reference images.
5. The relevant box in `docs/ROADMAP.md` is ticked.

## Working agreements

- **Owner instructions are durable.** Anything the owner states explicitly in a prompt (a layout rule, a tool choice, a removed limit, a workflow) is changed in the code **and** written into the matching doc in the same change: `CLAUDE.md`, `docs/*.md` or `.ai/rules/*`. A prompt-only change will be undone by the next agent, so the docs must say it, phrased as a rule ("never ...", "always ..."). Owner decisions override the prototypes, DESIGN.md and these docs; when they conflict, update the doc instead of reverting the decision.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
