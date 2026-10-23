<?php

declare(strict_types=1);

use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;

const CLAUDE_CALLBACK = 'https://claude.ai/api/mcp/auth_callback';

beforeEach(function () {
    $keys = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keys, $privateKey);

    config([
        'passport.private_key' => $privateKey,
        'passport.public_key' => openssl_pkey_get_details($keys)['key'],
    ]);
});

function rpc(string $method, array $params = []): array
{
    return ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params];
}

function registerClient(object $test, string $redirectUri = CLAUDE_CALLBACK): array
{
    return $test->postJson('/oauth/register', [
        'client_name' => 'Claude Test Client',
        'redirect_uris' => [$redirectUri],
    ])->json();
}

function challenge(string $verifier): string
{
    return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
}

function authorizeQuery(array $client, string $verifier = 'verifier'): string
{
    return '/oauth/authorize?'.http_build_query([
        'response_type' => 'code',
        'client_id' => $client['client_id'],
        'redirect_uri' => CLAUDE_CALLBACK,
        'code_challenge' => challenge($verifier),
        'code_challenge_method' => 'S256',
        'scope' => 'mcp:use',
        'state' => 'abc',
    ]);
}

/** Runs the whole OAuth flow Claude performs, as the given user, and returns the access token it ends up with. */
function connectAs(object $test, User $user): string
{
    $verifier = Str::random(64);
    $client = registerClient($test);

    $screen = $test->actingAs($user)->get(authorizeQuery($client, $verifier))->assertOk();
    preg_match('/name="auth_token" value="([^"]+)"/', $screen->getContent(), $match);
    $session = collect($screen->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

    $approved = $test->withCookie($session->getName(), $session->getValue())->post('/oauth/authorize', [
        'state' => '',
        'client_id' => $client['client_id'],
        'auth_token' => $match[1],
    ]);
    parse_str((string) parse_url($approved->headers->get('Location'), PHP_URL_QUERY), $callback);

    return $test->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client['client_id'],
        'redirect_uri' => CLAUDE_CALLBACK,
        'code' => $callback['code'],
        'code_verifier' => $verifier,
    ])->assertOk()->json('access_token');
}

describe('the remote /mcp endpoint', function () {
    it('refuses a request without an access token', function () {
        $this->postJson('/mcp', rpc('tools/list'))->assertUnauthorized();
    });

    it('serves the tools to a connected owner and reads only that owner\'s data', function () {
        $owner = User::factory()->create();
        Goal::factory()->habit()->for($owner)->create(['title' => 'English']);
        Goal::factory()->habit()->for(User::factory()->create())->create(['title' => 'Somebody else']);
        $token = connectAs($this, $owner);

        $this->withToken($token)->postJson('/mcp', rpc('tools/list'))
            ->assertOk()
            ->assertSee('complete-task');

        $this->withToken($token)->postJson('/mcp', rpc('tools/call', ['name' => 'list-goals-tool', 'arguments' => []]))
            ->assertOk()
            ->assertSee('English')
            ->assertDontSee('Somebody else');
    });

    it('does not let another user\'s token touch the owner\'s tasks', function () {
        $owner = User::factory()->create();
        $task = Task::factory()->for(Goal::factory()->project()->for($owner))->create(['title' => 'Owner secret']);
        $stranger = User::factory()->create();

        $this->withToken(connectAs($this, $stranger))
            ->postJson('/mcp', rpc('tools/call', ['name' => 'complete-task-tool', 'arguments' => ['task_id' => $task->id]]))
            ->assertOk()
            ->assertSee('No task with id')
            ->assertDontSee('Owner secret');

        expect($task->fresh()->completions()->count())->toBe(0);
    });

    it('rejects a forged token', function () {
        $this->withToken('not-a-real-token')->postJson('/mcp', rpc('tools/list'))->assertUnauthorized();
    });
});

describe('connecting a client', function () {
    it('advertises the OAuth endpoints Claude discovers', function () {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'));
    });

    it('shows the signed-in owner who is asking and where the approval goes', function () {
        $this->actingAs(User::factory()->create(['email' => 'owner@example.com']))
            ->get(authorizeQuery(registerClient($this)))
            ->assertOk()
            ->assertSee('Claude Test Client')
            ->assertSee('owner@example.com')
            ->assertSee('claude.ai');
    });

    it('sends a signed-out visitor to sign in first', function () {
        $this->get(authorizeQuery(registerClient($this)))->assertRedirect(route('login'));
    });

    it('refuses to register a client that redirects outside the allowed domains', function () {
        $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => ['https://evil.example/callback'],
        ])->assertStatus(400);
    });

    it('does not expose the device flow', function () {
        $this->get('/oauth/device')->assertNotFound();
        $this->postJson('/oauth/device/code')->assertNotFound();
    });
});
