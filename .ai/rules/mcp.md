---
paths:
  - "app/Mcp/**"
  - "routes/ai.php"
---

# MCP server (laravel/mcp)

- One server: `App\Mcp\Servers\TrakkServer`, with `#[Name('Trakk')]`, `#[Version]` and `#[Instructions]` explaining what Trakk is and when to use each tool.
- Generate classes with `php artisan make:mcp-tool` / `make:mcp-server`; check the current API with Boost `search-docs` (the `mcp-development` skill) before writing tools.
- Tools are thin adapters, exactly like controllers: validate input, resolve the user, call a service, format the result. No rules inside tools.
- Tool list and purpose: `docs/PRODUCT.md` → "MCP tools". Tool names are kebab-case verbs (`complete-task`).
- Mark behaviour with attributes: `#[IsReadOnly]` for reads, `#[IsIdempotent]` for complete/move, `#[IsDestructive]` for deletes.
- `#[Description]` is written for the model: when to call it, what it returns, how to find ids (e.g. "Call list-goals first to get goal ids").
- Inputs via `schema(JsonSchema $schema)`, every field with a description; validate in `handle()` with `$request->validate([...])`.
- Return concise text the model can quote back (`Done: "Speaking · 30 min" +3 pts. Today 12/14.`). Use `Response::error()` for user-fixable problems.
- Fuzzy matching (task by title) lives in a service, not in the tool.
- Registration in `routes/ai.php`: `Mcp::local('trakk', ...)` first; `Mcp::oauthRoutes()` inside the `throttle:30,1` group and `Mcp::web('/mcp', ...)->middleware(['auth:api', 'throttle:mcp'])`. `config/mcp.php` `redirect_domains` is an allow-list and must never contain `*` (consent phishing). The owner fallback in `TrakkTool` exists only for the local console server; over HTTP it must fail closed.
- Remote tests live in `tests/Feature/Mcp/RemoteMcpTest.php` and generate throwaway RSA keys in `beforeEach`; never call `passport:keys` in tests, it would overwrite the developer's keys in `storage/`.
- Every tool has a Pest test: `TrakkServer::actingAs($user)->tool(CompleteTaskTool::class, [...])->assertOk()->assertSee('...')`, plus one error case.
