<?php

declare(strict_types=1);

return [

    'version' => env('APP_VERSION', '0.1'),

    'owner' => [
        'email' => env('OWNER_EMAIL'),
        'password' => env('OWNER_PASSWORD'),
    ],

];
