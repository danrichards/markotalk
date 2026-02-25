<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Marko\Routing\Http\Request;

$app = (require __DIR__ . '/../vendor/marko/core/bootstrap.php')(
    vendorPath: __DIR__ . '/../vendor',
    modulesPath: __DIR__ . '/../modules',
    appPath: __DIR__ . '/../app',
);

$request = Request::fromGlobals();
$response = $app->router->handle($request);
$response->send();
