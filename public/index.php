<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Force file-based drivers to prevent DB table errors in production
$_ENV['CACHE_STORE'] = 'file';
$_ENV['CACHE_DRIVER'] = 'file';
$_ENV['SESSION_DRIVER'] = 'file';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
