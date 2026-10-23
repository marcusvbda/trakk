<?php

declare(strict_types=1);

use App\Mcp\Servers\TrakkServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('trakk', TrakkServer::class);
