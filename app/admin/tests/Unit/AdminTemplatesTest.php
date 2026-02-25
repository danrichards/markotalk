<?php

declare(strict_types=1);

it('renders the space management table with all spaces', function (): void {
    $file = '/Users/markshust/Sites/markotalk/app/admin/resources/views/spaces/index.latte';

    expect(file_exists(filename: $file))->toBeTrue()
        ->and(file_get_contents(filename: $file))->toContain('admin-table')
        ->and(file_get_contents(filename: $file))->toContain('$spaces')
        ->and(file_get_contents(filename: $file))->toContain('$space->name')
        ->and(file_get_contents(filename: $file))->toContain('$space->slug')
        ->and(file_get_contents(filename: $file))->toContain('Space Management');
});

it('renders the create space form', function (): void {
    $file = '/Users/markshust/Sites/markotalk/app/admin/resources/views/spaces/index.latte';

    expect(file_exists(filename: $file))->toBeTrue()
        ->and(file_get_contents(filename: $file))->toContain('admin-form')
        ->and(file_get_contents(filename: $file))->toContain('admin-field')
        ->and(file_get_contents(filename: $file))->toContain('admin-input')
        ->and(file_get_contents(filename: $file))->toContain('admin-btn')
        ->and(file_get_contents(filename: $file))->toContain('Create Space')
        ->and(file_get_contents(filename: $file))->toContain('name="name"')
        ->and(file_get_contents(filename: $file))->toContain('name="description"');
});

it('renders the user management table with all users', function (): void {
    $file = '/Users/markshust/Sites/markotalk/app/admin/resources/views/users/index.latte';

    expect(file_exists(filename: $file))->toBeTrue()
        ->and(file_get_contents(filename: $file))->toContain('admin-table')
        ->and(file_get_contents(filename: $file))->toContain('$users')
        ->and(file_get_contents(filename: $file))->toContain('$user->username')
        ->and(file_get_contents(filename: $file))->toContain('$user->email')
        ->and(file_get_contents(filename: $file))->toContain('User Management');
});

it('shows ban/unban and role change actions per user', function (): void {
    $file = '/Users/markshust/Sites/markotalk/app/admin/resources/views/users/index.latte';

    expect(file_exists(filename: $file))->toBeTrue()
        ->and(file_get_contents(filename: $file))->toContain('/admin/users/{$user->id}/ban')
        ->and(file_get_contents(filename: $file))->toContain('/admin/users/{$user->id}/unban')
        ->and(file_get_contents(filename: $file))->toContain('/admin/users/{$user->id}/role')
        ->and(file_get_contents(filename: $file))->toContain('$user->isBanned')
        ->and(file_get_contents(filename: $file))->toContain('admin-action-danger')
        ->and(file_get_contents(filename: $file))->toContain('admin-action-restore');
});

it('has admin.css with all admin-* classes defined via @apply', function (): void {
    $file = '/Users/markshust/Sites/markotalk/src/css/modules/admin.css';

    expect(file_exists(filename: $file))->toBeTrue()
        ->and(file_get_contents(filename: $file))->toContain('.admin-panel')
        ->and(file_get_contents(filename: $file))->toContain('.admin-header')
        ->and(file_get_contents(filename: $file))->toContain('.admin-title')
        ->and(file_get_contents(filename: $file))->toContain('.admin-section')
        ->and(file_get_contents(filename: $file))->toContain('.admin-section-title')
        ->and(file_get_contents(filename: $file))->toContain('.admin-form')
        ->and(file_get_contents(filename: $file))->toContain('.admin-field')
        ->and(file_get_contents(filename: $file))->toContain('.admin-label')
        ->and(file_get_contents(filename: $file))->toContain('.admin-input')
        ->and(file_get_contents(filename: $file))->toContain('.admin-btn')
        ->and(file_get_contents(filename: $file))->toContain('.admin-table')
        ->and(file_get_contents(filename: $file))->toContain('.admin-table-header')
        ->and(file_get_contents(filename: $file))->toContain('.admin-table-row')
        ->and(file_get_contents(filename: $file))->toContain('.admin-actions')
        ->and(file_get_contents(filename: $file))->toContain('.admin-action')
        ->and(file_get_contents(filename: $file))->toContain('.admin-action-danger')
        ->and(file_get_contents(filename: $file))->toContain('.admin-action-restore')
        ->and(file_get_contents(filename: $file))->toContain('@apply');
});

it('contains no Tailwind utility classes in any template', function (): void {
    $spacesTemplate = '/Users/markshust/Sites/markotalk/app/admin/resources/views/spaces/index.latte';
    $usersTemplate = '/Users/markshust/Sites/markotalk/app/admin/resources/views/users/index.latte';

    $tailwindPattern = '/\bclass="[^"]*\b(flex|grid|block|inline|hidden|text-\w+|bg-\w+|p-\d|px-\d|py-\d|m-\d|mx-\d|my-\d|mt-\d|mb-\d|ml-\d|mr-\d|w-\w+|h-\w+|max-w-\w+|min-\w+|border|rounded|shadow|font-\w+|items-\w+|justify-\w+|gap-\d|space-\w+|overflow-\w+|relative|absolute|fixed|sticky|z-\d|opacity-\d|cursor-\w+|hover:\w+|focus:\w+)\b[^"]*"/';

    expect(file_exists(filename: $spacesTemplate))->toBeTrue()
        ->and(preg_match(pattern: $tailwindPattern, subject: (string) file_get_contents(filename: $spacesTemplate)))->toBe(0)
        ->and(file_exists(filename: $usersTemplate))->toBeTrue()
        ->and(preg_match(pattern: $tailwindPattern, subject: (string) file_get_contents(filename: $usersTemplate)))->toBe(0);
});
