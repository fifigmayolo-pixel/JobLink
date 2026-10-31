<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Vercel serverless adjustments
|--------------------------------------------------------------------------
| Vercel's filesystem is read-only except /tmp, and every new instance
| starts with an empty /tmp. Laravel normally writes compiled views, cache,
| sessions, logs and bootstrap cache files inside the project folder, so
| all of those locations are redirected to /tmp here.
|
| Anything stored in /tmp is temporary. Permanent data (database rows and
| uploaded files) must live in Supabase, not here.
*/

$tmp = '/tmp';
$storage = $tmp.'/storage';

foreach ([
    $storage.'/app/public',
    $storage.'/framework/cache/data',
    $storage.'/framework/sessions',
    $storage.'/framework/views',
    $storage.'/logs',
    $tmp.'/bootstrap/cache',
] as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Laravel reads these environment variables to decide where its cache files go.
foreach ([
    'APP_CONFIG_CACHE' => $tmp.'/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => $tmp.'/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => $tmp.'/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => $tmp.'/bootstrap/cache/routes-v7.php',
    'APP_SERVICES_CACHE' => $tmp.'/bootstrap/cache/services.php',
    'VIEW_COMPILED_PATH' => $storage.'/framework/views',
] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Vercel terminates HTTPS before the request reaches PHP. Without this,
// Laravel would build http:// asset URLs and browsers would block them.
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->useStoragePath($storage);

$app->handleRequest(Request::capture());