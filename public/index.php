<?php

/*
|--------------------------------------------------------------------------
| Suppress PHP 8.5 PDO Deprecation Warnings from Laravel Vendor Files
|--------------------------------------------------------------------------
|
| PHP 8.5 deprecated PDO::MYSQL_ATTR_SSL_* constants in favor of
| Pdo\Mysql::ATTR_SSL_*. Laravel's vendor files still use the old constants.
| This handler suppresses ONLY these specific warnings from vendor files
| while allowing all other errors/deprecations through normally.
|
| This can be removed once Laravel updates their codebase for PHP 8.5.
|
*/
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Suppress specific PDO::MYSQL_ATTR_SSL_* deprecations from Laravel vendor
    if ($errno === E_DEPRECATED &&
        (str_contains($errstr, 'PDO::MYSQL_ATTR_SSL_') || 
         str_contains($errstr, 'use Pdo\\Mysql::ATTR_SSL_')) &&
        (str_contains($errfile, 'vendor/laravel/framework') || 
         str_contains($errfile, 'database.php'))) {
        return true; // Suppress this specific warning
    }

    // Let all other errors pass through normal handling
    return false;
}, E_ALL);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
// ini_set("post_max_size","500M");
// ini_set("upload_max_filesize","500M");
// ini_set("memory_limit","1000M");
// ini_set('max_input_time', 10000);
// ini_set('max_execution_time', 10000);
/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
