<?php

declare(strict_types=1);

it('includes marko/pubsub in composer.json require', function (): void {
    $composerPath = __DIR__ . '/../../composer.json';
    $composer = json_decode(json: file_get_contents(filename: $composerPath), associative: true);

    expect($composer['require'])->toHaveKey('marko/pubsub');
});

it('includes marko/pubsub-pgsql in composer.json require', function (): void {
    $composerPath = __DIR__ . '/../../composer.json';
    $composer = json_decode(json: file_get_contents(filename: $composerPath), associative: true);

    expect($composer['require'])->toHaveKey('marko/pubsub-pgsql');
});

it('includes marko/amphp in composer.json require', function (): void {
    $composerPath = __DIR__ . '/../../composer.json';
    $composer = json_decode(json: file_get_contents(filename: $composerPath), associative: true);

    expect($composer['require'])->toHaveKey('marko/amphp');
});

it('has pubsub, pubsub-pgsql, and amphp packages installed', function (): void {
    expect(is_dir(__DIR__ . '/../../vendor/marko/pubsub'))->toBeTrue()
        ->and(is_dir(__DIR__ . '/../../vendor/marko/pubsub-pgsql'))->toBeTrue()
        ->and(is_dir(__DIR__ . '/../../vendor/marko/amphp'))->toBeTrue();
});

it('has a pubsub config file with driver set to pgsql and prefix', function (): void {
    $configPath = __DIR__ . '/../../config/pubsub.php';

    expect(file_exists(filename: $configPath))->toBeTrue();

    $config = require $configPath;

    expect($config)->toBeArray()
        ->and($config['driver'])->toBe('pgsql')
        ->and($config)->toHaveKey('prefix');
});

it('has a pubsub-pgsql config file with host, port, user, password, and database', function (): void {
    $configPath = __DIR__ . '/../../config/pubsub-pgsql.php';

    expect(file_exists(filename: $configPath))->toBeTrue();

    $config = require $configPath;

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('host')
        ->and($config)->toHaveKey('port')
        ->and($config)->toHaveKey('user')
        ->and($config)->toHaveKey('password')
        ->and($config)->toHaveKey('database');
});

it('has an amphp config file with shutdown timeout', function (): void {
    $configPath = __DIR__ . '/../../config/amphp.php';

    expect(file_exists(filename: $configPath))->toBeTrue();

    $config = require $configPath;

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('shutdown_timeout');
});
