<?php

declare(strict_types=1);

it('renders login form with email and password fields', function (): void {
    $loginTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/login.latte');

    expect($loginTemplate)->toContain('type="email"')
        ->and($loginTemplate)->toContain('name="email"')
        ->and($loginTemplate)->toContain('type="password"')
        ->and($loginTemplate)->toContain('name="password"')
        ->and($loginTemplate)->toContain('method="POST"')
        ->and($loginTemplate)->toContain('action="/login"');
});

it('renders register form with username, email, password, and password confirmation fields', function (): void {
    $registerTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/register.latte');

    expect($registerTemplate)->toContain('name="username"')
        ->and($registerTemplate)->toContain('type="email"')
        ->and($registerTemplate)->toContain('name="email"')
        ->and($registerTemplate)->toContain('type="password"')
        ->and($registerTemplate)->toContain('name="password"')
        ->and($registerTemplate)->toContain('name="password_confirmation"')
        ->and($registerTemplate)->toContain('method="POST"')
        ->and($registerTemplate)->toContain('action="/register"');
});

it('displays validation errors next to form fields', function (): void {
    $loginTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/login.latte');
    $registerTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/register.latte');

    expect($loginTemplate)->toContain('$errors[')
        ->and($loginTemplate)->toContain('auth-error')
        ->and($registerTemplate)->toContain('$errors[')
        ->and($registerTemplate)->toContain('auth-error');
});

it('includes CSRF token in both forms', function (): void {
    $loginTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/login.latte');
    $registerTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/register.latte');

    expect($loginTemplate)->toContain('name="_token"')
        ->and($loginTemplate)->toContain('$csrfToken')
        ->and($registerTemplate)->toContain('name="_token"')
        ->and($registerTemplate)->toContain('$csrfToken');
});

it('has auth.css with semantic @apply styles for all auth classes', function (): void {
    $authCss = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/src/css/auth.css');

    expect($authCss)->toContain('.auth-container')
        ->and($authCss)->toContain('.auth-card')
        ->and($authCss)->toContain('.auth-title')
        ->and($authCss)->toContain('.auth-form')
        ->and($authCss)->toContain('.auth-field')
        ->and($authCss)->toContain('.auth-label')
        ->and($authCss)->toContain('.auth-input')
        ->and($authCss)->toContain('.auth-input-error')
        ->and($authCss)->toContain('.auth-error')
        ->and($authCss)->toContain('.auth-submit')
        ->and($authCss)->toContain('.auth-link')
        ->and($authCss)->toContain('@apply');
});

it('contains no Tailwind utility classes in template markup', function (): void {
    $loginTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/login.latte');
    $registerTemplate = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/user/resources/views/auth/register.latte');

    $tailwindPatterns = [
        'class="flex',
        'class="bg-',
        'class="text-',
        'class="p-',
        'class="m-',
        'class="w-',
        'class="h-',
        'class="border',
        'class="rounded',
        'class="font-',
        'class="space-',
        'class="items-',
        'class="justify-',
        'class="shadow',
        'class="min-h-',
        'class="max-w-',
    ];

    foreach ($tailwindPatterns as $pattern) {
        expect($loginTemplate)->not->toContain($pattern)
            ->and($registerTemplate)->not->toContain($pattern);
    }
});
