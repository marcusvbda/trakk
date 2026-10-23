<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Exceptions\AmbiguousTaskException;
use App\Exceptions\TaskNotFoundException;
use App\Models\Task;
use App\Models\User;
use App\Services\Mcp\OwnerResolver;
use App\Services\Tasks\TaskLookupService;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Every Trakk tool is an adapter: it resolves the owner, calls the same services as the web app and words the
 * result. Anything a person can fix (unknown id, ambiguous title, a rule the service refuses) is an error response.
 */
abstract class TrakkTool extends Tool
{
    final public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            // Only the local (console) server has no signed-in user; over HTTP a missing user must never fall back to the owner.
            if (! app()->runningInConsole()) {
                return Response::error('Not authenticated.');
            }

            $user = app(OwnerResolver::class)->owner();
        }

        try {
            return $this->run($request, $user, app(Clock::class)->today($user));
        } catch (TaskNotFoundException|AmbiguousTaskException|InvalidArgumentException|DomainException $exception) {
            return Response::error($exception->getMessage());
        } catch (ModelNotFoundException) {
            return Response::error('Not found. Use get-today or list-goals to see valid ids.');
        }
    }

    abstract protected function run(Request $request, User $user, CarbonImmutable $today): Response;

    /** The task named by `task_id` or by a (loose) `title`. */
    protected function task(Request $request, User $user, CarbonImmutable $today): Task
    {
        $input = $request->validate([
            'task_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:160'],
        ]);

        return app(TaskLookupService::class)->find($user, $today, $input['task_id'] ?? null, $input['title'] ?? null);
    }
}
