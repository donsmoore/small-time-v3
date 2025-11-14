<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Strip /timeclock/v3 prefix from REQUEST_URI when using Apache Alias
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/timeclock/v3') === 0) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], strlen('/timeclock/v3'));
    // Also update SCRIPT_NAME if it contains the prefix
    if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/timeclock/v3') === 0) {
        $_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'], strlen('/timeclock/v3'));
    }
}

$app->handleRequest(Request::capture());
