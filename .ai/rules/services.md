---
paths:
  - "app/Services/**"
  - "app/Data/**"
---

# Services and data objects

## Services

- All business rules from `docs/PRODUCT.md` live here. If a rule is not in PRODUCT.md, add it there in the same change.
- Grouped by domain folder: `Goals`, `Tasks`, `Scoring`, `Dashboard`, `Activity`, `Reminders`, `Telegram`.
- `final`, stateless, dependencies injected through the constructor. No static methods, no facades for things that hold rules.
- Public methods take Models, Data objects, enums and `CarbonImmutable $today`. Never a `Request`, never an HTTP concern, never `auth()`.
- Return Models, Data objects or scalars. Never arrays with unknown shape.
- Writes that touch more than one row run in `DB::transaction()` inside the service.
- Side effects that are not part of the rule (activity log, notifications) go through a dedicated collaborator (`ActivityLogger`) called at the end of the service method.
- Calculators (`GoalProgressService`, `ScoreService`, `StreakService`, `ConsistencyService`) are pure where possible: give them the data, get numbers back. They are the most tested code in the app.
- A service method that grows past ~30 lines or needs a comment per section gets split into private methods with intention-revealing names.

```php
final class CompletionService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function complete(Task $task, CarbonImmutable $today): Completion
    {
        $completion = $task->completions()->firstOrCreate(
            ['completed_on' => $today->toDateString()],
            ['user_id' => $task->goal->user_id, 'points' => $task->points],
        );

        $this->activity->taskCompleted($task, $completion);

        return $completion;
    }
}
```

## Data objects

- `final readonly class` with a constructor of promoted properties. No setters, no behaviour beyond small derived getters.
- Built from requests (`StoreGoalRequest::toData()`), from MCP tool input (`GoalData::fromArray()`), or returned by calculators (`GoalPace`, `DayScore`, `DashboardData`).
- Use enums and `CarbonImmutable` as property types, not strings.
