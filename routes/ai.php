<?php

declare(strict_types=1);

use App\Mcp\Servers\TrakkServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;

Mcp::local('trakk', TrakkServer::class);

Mcp::oauthRoutes(registration: false);

// Dynamic client registration is public by specification and every call stores an OAuth client, so it is throttled
// on its own; the discovery routes above stay unthrottled because Claude fetches them on every connection.
Route::post('oauth/register', OAuthRegisterController::class)
    ->middleware('throttle:10,1')
    ->name('mcp.oauth.register');

Mcp::web('/mcp', TrakkServer::class)->middleware(['auth:api', 'throttle:mcp']);
