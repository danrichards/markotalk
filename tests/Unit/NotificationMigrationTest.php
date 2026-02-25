<?php

declare(strict_types=1);

it('has a migration that creates the notifications table', function (): void {
    $migrationPath = __DIR__ . '/../../database/migrations/20260224000006_create_notifications.php';

    expect(file_exists($migrationPath))->toBeTrue()
        ->and(is_readable($migrationPath))->toBeTrue();

    $content = file_get_contents($migrationPath);

    expect($content)->toContain('CREATE TABLE')
        ->and($content)->toContain('notifications')
        ->and($content)->toContain('DROP TABLE');
});

it('has id as varchar 36 primary key for UUID', function (): void {
    $migrationPath = __DIR__ . '/../../database/migrations/20260224000006_create_notifications.php';
    $content = file_get_contents($migrationPath);

    expect($content)->toContain('VARCHAR(36)')
        ->and($content)->toContain('PRIMARY KEY');
});

it('has indexes on notifiable_type and notifiable_id', function (): void {
    $migrationPath = __DIR__ . '/../../database/migrations/20260224000006_create_notifications.php';
    $content = file_get_contents($migrationPath);

    expect($content)->toContain('notifiable_type')
        ->and($content)->toContain('notifiable_id')
        ->and($content)->toContain('INDEX');
});

it('has nullable read_at timestamp', function (): void {
    $migrationPath = __DIR__ . '/../../database/migrations/20260224000006_create_notifications.php';
    $content = file_get_contents($migrationPath);

    expect($content)->toContain('read_at')
        ->and($content)->toContain('TIMESTAMP NULL');
});
