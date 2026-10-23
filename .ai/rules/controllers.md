---
paths:
  - "app/Http/**"
  - "routes/web.php"
---

# Controllers, requests, resources, routes

## Controllers

- Resourceful methods only (`index`, `show`, `create`, `store`, `edit`, `update`, `destroy`). Anything else becomes its own controller: `TaskStatusController@update`, `CompletionController@store/destroy`.
- Shape of every write action: authorize → `$request->toData()` → one service call → `redirect()->back()` or `to_route(...)`. Around 5 lines.
- Shape of every page action: authorize → one service/read-model call → `Inertia::render('goals/show', [...Resources])`.
- No Eloquent queries, no business rules, no loops, no `DB::` in controllers. Route-model binding is fine.
- Authorization through policies (`Gate::authorize('update', $goal)` or `$this->authorize`). Scope every lookup to the current user.

```php
final class GoalController extends Controller
{
    public function __construct(private readonly GoalService $goals) {}

    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $goal = $this->goals->create($request->user(), $request->toData());

        return to_route('goals.show', $goal);
    }
}
```

## Form requests

- One per write action: `StoreGoalRequest`, `UpdateGoalRequest`, `MoveTaskRequest`.
- Array-style rules with `Rule::enum()` for enums.
- Expose `toData(): GoalData`. Controllers never call `$request->validated()` directly.
- Cross-field rules (habit requires `target_per_week`) live here; domain invariants are re-checked in the service.

## Resources

- `JsonResource` per model shown in the UI; camelCase keys; dates as ISO strings; computed numbers (progress, pace) passed in, not computed inside the resource.
- Keep TS types in `resources/js/types/` in sync with every Resource change.

## Routes

- Named routes, resource routes where possible, grouped under `auth` + `verified` middleware.
- The frontend calls routes through Wayfinder; never hardcode URLs in TS.
