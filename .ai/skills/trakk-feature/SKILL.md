---
name: trakk-feature
description: Build a Trakk feature as a vertical slice (migration, model, service with tests, request, controller, route, page). Use when implementing any ROADMAP item or new behaviour in Trakk.
---

# Build a Trakk feature

## 1. Understand

- Find the item in `docs/ROADMAP.md` and the rules it touches in `docs/PRODUCT.md`.
- Check `docs/ARCHITECTURE.md` for the tables, services and routes involved.
- If a rule is ambiguous or missing, stop and ask the owner. Write the answer into PRODUCT.md.
- Read `.ai/rules/index.md` and the rule files for every folder you will touch.
- Look at a sibling file of each kind you will create and follow its pattern.

## 2. Plan the slice

List the files you will create or change, in this order, before writing code:

1. Migration + model + enum + factory states (only if the data model changes; ask first if it differs from ARCHITECTURE.md).
2. Data object(s).
3. Service method(s).
4. Unit/feature tests for the service.
5. FormRequest with `toData()`, controller action, named route, policy check.
6. Resource + TS type.
7. Page / components (use the `trakk-ui` skill).
8. MCP tool, if the behaviour should be available to Claude.

## 3. Build test-first where it pays

- For calculators and rules: write the Pest test with datasets first, then the service.
- For endpoints: write the feature test (happy path, validation, ownership) alongside the controller.
- Generate with artisan (`make:model -mf`, `make:request`, `make:controller`, `make:test --pest`, `make:mcp-tool`) and then reshape to the project rules.

## 4. Finish

```bash
php artisan test --compact
vendor/bin/pint --dirty
vendor/bin/phpstan analyse
npm run types
```

- Update `docs/PRODUCT.md` / `docs/ARCHITECTURE.md` if behaviour or schema changed.
- Tick the ROADMAP box.
- Summarise what changed and anything left open in 3–5 lines.
