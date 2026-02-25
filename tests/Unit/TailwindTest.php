<?php

declare(strict_types=1);

it('has a tailwind.config.js with correct content paths', function (): void {
    $configPath = __DIR__ . '/../../tailwind.config.js';

    expect(file_exists($configPath))->toBeTrue();

    $content = file_get_contents($configPath);

    expect($content)->toContain('./app/*/resources/views/**/*.latte')
        ->and($content)->toContain('./resources/views/**/*.latte')
        ->and($content)->toContain('./src/css/**/*.css');
});

it('has the base layout.latte with structural HTML using only semantic class names', function (): void {
    $layoutPath = __DIR__ . '/../../resources/views/layout.latte';

    expect(file_exists($layoutPath))->toBeTrue();

    $content = file_get_contents($layoutPath);

    $tailwindUtilityPattern = '/class="[^"]*\b(flex|grid|text-|bg-|px-|py-|p-|m-|w-|h-|min-h-|items-|justify-|overflow-|font-|shadow|rounded|border)[^"]*"/';

    // Structural HTML
    expect($content)->toContain('<!doctype html>')
        ->and($content)->toContain('<html')
        ->and($content)->toContain('<head')
        ->and($content)->toContain('<body')
        ->and($content)->toContain('class="app-shell"')
        ->and($content)->toContain('class="app-header"')
        ->and($content)->toContain('class="app-body"')
        ->and($content)->toContain('class="app-sidebar"')
        ->and($content)->toContain('class="main-content"')
        // Latte block syntax
        ->and($content)->toContain('{block title}')
        ->and($content)->toContain('{block head}')
        ->and($content)->toContain('{block header}')
        ->and($content)->toContain('{block sidebar}')
        ->and($content)->toContain('{block content}')
        ->and($content)->toContain('{block scripts}')
        // Asset links
        ->and($content)->toContain('/css/app.css')
        ->and($content)->toContain('/js/app.js')
        // No Tailwind utility classes directly
        ->and(preg_match($tailwindUtilityPattern, $content))->toBe(0);
});

it('compiles to public/css/app.css via Tailwind CLI', function (): void {
    $outputCssPath = __DIR__ . '/../../public/css/app.css';

    expect(file_exists($outputCssPath))->toBeTrue();

    $content = file_get_contents($outputCssPath);

    expect($content)->not->toBeEmpty();
});

it('has src/css/components.css with btn, btn-primary, form-field, form-label classes', function (): void {
    $componentsCssPath = __DIR__ . '/../../src/css/components.css';

    expect(file_exists($componentsCssPath))->toBeTrue();

    $content = file_get_contents($componentsCssPath);

    expect($content)->toContain('.btn')
        ->and($content)->toContain('.btn-primary')
        ->and($content)->toContain('.form-field')
        ->and($content)->toContain('.form-label');
});

it('has src/css/layout.css with app-shell, sidebar, main-content, app-header classes', function (): void {
    $layoutCssPath = __DIR__ . '/../../src/css/layout.css';

    expect(file_exists($layoutCssPath))->toBeTrue();

    $content = file_get_contents($layoutCssPath);

    expect($content)->toContain('.app-shell')
        ->and($content)->toContain('.app-header')
        ->and($content)->toContain('.app-body')
        ->and($content)->toContain('.app-sidebar')
        ->and($content)->toContain('.main-content');
});

it('has src/css/base.css with reset and typography styles', function (): void {
    $baseCssPath = __DIR__ . '/../../src/css/base.css';

    expect(file_exists($baseCssPath))->toBeTrue();

    $content = file_get_contents($baseCssPath);

    expect($content)->not->toBeEmpty();
});

it('has src/css/app.css entry point importing all layers', function (): void {
    $appCssPath = __DIR__ . '/../../src/css/app.css';

    expect(file_exists($appCssPath))->toBeTrue();

    $content = file_get_contents($appCssPath);

    expect($content)->toContain("@import 'tailwindcss/base'")
        ->and($content)->toContain("@import 'tailwindcss/components'")
        ->and($content)->toContain("@import 'tailwindcss/utilities'")
        ->and($content)->toContain("@import './base.css'")
        ->and($content)->toContain("@import './layout.css'")
        ->and($content)->toContain("@import './components.css'");
});
