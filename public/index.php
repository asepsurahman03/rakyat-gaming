
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Base path resolution for local dev or hosting repository structure
$baseDir = file_exists(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/..'
    : __DIR__.'/../repositories/warung-gaming';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $baseDir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $baseDir.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $baseDir.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
