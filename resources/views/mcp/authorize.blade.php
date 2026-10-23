<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title>Authorize {{ $client->name }} - {{ config('app.name', 'Trakk') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @vite(['resources/css/app.css'])
</head>
<body class="bg-canvas font-mono text-body antialiased">
<main class="flex min-h-screen items-center justify-center p-4">
    <section class="w-full max-w-md rounded-lg border border-line bg-surface">
        <header class="border-b border-divider px-5 py-4">
            <p class="text-xs text-muted">~/trakk · authorize</p>
            <h1 class="mt-2 text-lg font-semibold text-fg-strong">{{ $client->name }}</h1>
            <p class="mt-1 text-sm text-muted">wants to connect to your Trakk through MCP.</p>
            @php($hosts = collect($client->redirect_uris ?? [])->map(fn ($uri) => parse_url((string) $uri, PHP_URL_HOST))->filter()->unique()->implode(', '))
            @if ($hosts !== '')
                <p class="mt-1 text-xs text-faint">after you approve, it sends you back to <span class="text-fg-2">{{ $hosts }}</span></p>
            @endif
        </header>

        <div class="space-y-4 px-5 py-4 text-sm">
            <div>
                <p class="text-xs text-faint">signed in as</p>
                <p class="mt-1 text-fg">{{ $user->email }}</p>
            </div>

            <div>
                <p class="text-xs text-faint">it will be able to</p>
                <ul class="mt-2 space-y-1.5 text-log">
                    <li>read your goals, today's actions and pace</li>
                    <li>complete and undo actions, move and create tasks and goals, replan overdue tasks</li>
                </ul>
                <p class="mt-2 text-xs text-faint">Everything it does is logged with a "claude" mark. It cannot delete anything.</p>
            </div>
        </div>

        <footer class="flex gap-3 border-t border-divider px-5 py-4">
            <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="w-full cursor-pointer rounded-md border border-line-strong px-4 py-2 text-fg-2 hover:border-line-hover hover:text-fg">
                    cancel
                </button>
            </form>

            <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
                @csrf
                <input type="hidden" name="state" value="">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="w-full cursor-pointer rounded-md border border-accent px-4 py-2 text-accent hover:text-accent-hover">
                    authorize
                </button>
            </form>
        </footer>
    </section>
</main>
</body>
</html>
