<?php

// Start output buffering to capture ANY stray output
ob_start();

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

// Clean any output that happened during autoload (BOM, stray chars, etc)
if (ob_get_length() > 0) {
    ob_clean();
}

$app = require_once __DIR__.'/../bootstrap/app.php';

// Clean any output from bootstrap too
if (ob_get_length() > 0) {
    ob_clean();
}

$app->handleRequest(Request::capture());