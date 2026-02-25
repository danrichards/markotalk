<?php

declare(strict_types=1);

it('has a composer.json with all required marko package dependencies', function (): void {
    $composerPath = __DIR__ . '/../../composer.json';

    expect(file_exists($composerPath))->toBeTrue()
        ->and(is_readable($composerPath))->toBeTrue();

    $composer = json_decode(file_get_contents($composerPath), associative: true);

    expect($composer)->toBeArray()
        ->and($composer['name'])->toBe('markotalk/app')
        ->and($composer['require'])->toBeArray();

    $requiredPackages = [
        'marko/authentication',
        'marko/authorization',
        'marko/cache-array',
        'marko/config',
        'marko/core',
        'marko/database',
        'marko/database-mysql',
        'marko/env',
        'marko/errors',
        'marko/errors-simple',
        'marko/hashing',
        'marko/log',
        'marko/log-file',
        'marko/notification',
        'marko/notification-database',
        'marko/pagination',
        'marko/rate-limiting',
        'marko/routing',
        'marko/security',
        'marko/session',
        'marko/session-file',
        'marko/sse',
        'marko/testing',
        'marko/validation',
        'marko/view',
        'marko/view-latte',
    ];

    foreach ($requiredPackages as $package) {
        expect($composer['require'])->toHaveKey(key: $package, message: "Missing required package: {$package}");
    }
});

it('has path repositories for all marko packages referenced', function (): void {
    $composerPath = __DIR__ . '/../../composer.json';
    $composer = json_decode(file_get_contents($composerPath), associative: true);

    expect($composer['repositories'])->toBeArray();

    $allPackages = [
        'admin', 'admin-api', 'admin-auth', 'admin-panel', 'api', 'authentication',
        'authentication-token', 'authorization', 'blog', 'cache', 'cache-array',
        'cache-file', 'cache-redis', 'cli', 'config', 'core', 'cors', 'database',
        'database-mysql', 'database-pgsql', 'encryption', 'encryption-openssl', 'env',
        'errors', 'errors-advanced', 'errors-simple', 'filesystem', 'filesystem-local',
        'filesystem-s3', 'framework', 'hashing', 'health', 'http', 'http-guzzle', 'log',
        'log-file', 'mail', 'mail-log', 'mail-smtp', 'media', 'media-gd', 'media-imagick',
        'notification', 'notification-database', 'pagination', 'queue', 'queue-database',
        'queue-rabbitmq', 'queue-sync', 'rate-limiting', 'routing', 'scheduler', 'search',
        'security', 'session', 'session-database', 'session-file', 'sse', 'testing',
        'translation', 'translation-file', 'validation', 'view', 'view-latte', 'webhook',
    ];

    $repositoryUrls = array_column($composer['repositories'], 'url');

    foreach ($allPackages as $package) {
        $expectedUrl = "../marko/packages/{$package}";
        expect(in_array($expectedUrl, $repositoryUrls, strict: true))->toBeTrue(
            "Missing path repository for: {$package}"
        );
    }
});

it('has a public/index.php that bootstraps the Marko application', function (): void {
    $indexPath = __DIR__ . '/../../public/index.php';

    expect(file_exists($indexPath))->toBeTrue();

    $content = file_get_contents($indexPath);

    expect($content)->toContain("declare(strict_types=1);")
        ->and($content)->toContain("require_once __DIR__ . '/../vendor/autoload.php';")
        ->and($content)->toContain("use Marko\\Routing\\Http\\Request;")
        ->and($content)->toContain("require __DIR__ . '/../vendor/marko/core/bootstrap.php'")
        ->and($content)->toContain('vendorPath:')
        ->and($content)->toContain('modulesPath:')
        ->and($content)->toContain('appPath:')
        ->and($content)->toContain('Request::fromGlobals()')
        ->and($content)->toContain('$app->router->handle($request)')
        ->and($content)->toContain('$response->send()');
});

it('has a .env.example with all required environment variables', function (): void {
    $envExamplePath = __DIR__ . '/../../.env.example';

    expect(file_exists($envExamplePath))->toBeTrue();

    $content = file_get_contents($envExamplePath);

    expect($content)->toContain('APP_ENV=')
        ->and($content)->toContain('DB_HOST=')
        ->and($content)->toContain('DB_PORT=')
        ->and($content)->toContain('DB_DATABASE=')
        ->and($content)->toContain('DB_USERNAME=')
        ->and($content)->toContain('DB_PASSWORD=');
});

it('has config/database.php with MySQL configuration', function (): void {
    $configPath = __DIR__ . '/../../config/database.php';

    expect(file_exists($configPath))->toBeTrue();

    $config = require $configPath;

    expect($config)->toBeArray()
        ->and($config['driver'])->toBe('mysql')
        ->and($config)->toHaveKey('host')
        ->and($config)->toHaveKey('port')
        ->and($config)->toHaveKey('database')
        ->and($config)->toHaveKey('username')
        ->and($config)->toHaveKey('password');
});

it('has config/markotalk.php with app-level configuration', function (): void {
    $configPath = __DIR__ . '/../../config/markotalk.php';

    expect(file_exists($configPath))->toBeTrue();

    $config = require $configPath;

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('max_message_length')
        ->and($config)->toHaveKey('presence_timeout')
        ->and($config)->toHaveKey('sse_poll_interval')
        ->and($config)->toHaveKey('sse_heartbeat_interval')
        ->and($config)->toHaveKey('sse_timeout')
        ->and($config)->toHaveKey('default_spaces');
});

it('has the correct directory structure for all five app modules', function (): void {
    $modules = ['user', 'space', 'message', 'notification', 'admin'];
    $namespaces = [
        'user' => 'App\\User\\',
        'space' => 'App\\Space\\',
        'message' => 'App\\Message\\',
        'notification' => 'App\\Notification\\',
        'admin' => 'App\\Admin\\',
    ];

    foreach ($modules as $module) {
        $modulePath = __DIR__ . "/../../app/{$module}";
        $composerPath = "{$modulePath}/composer.json";

        expect(is_dir($modulePath))->toBeTrue("Module directory missing: app/{$module}")
            ->and(file_exists($composerPath))->toBeTrue("composer.json missing: app/{$module}/composer.json");

        $moduleComposer = json_decode(file_get_contents($composerPath), associative: true);

        expect($moduleComposer['extra']['marko']['module'])->toBeTrue(
            "extra.marko.module must be true in app/{$module}/composer.json"
        );

        $psr4Keys = array_keys($moduleComposer['autoload']['psr-4'] ?? []);
        expect(in_array($namespaces[$module], $psr4Keys, strict: true))->toBeTrue(
            "PSR-4 namespace {$namespaces[$module]} missing in app/{$module}/composer.json"
        );
    }
});
