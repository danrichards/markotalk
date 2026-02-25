<?php

declare(strict_types=1);

use App\Landing\Controller\LandingController;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

// ─── Helper stubs ────────────────────────────────────────────────────────────

function makeLandingView(): ViewInterface
{
    return new class implements ViewInterface {
        public string $lastTemplate = '';

        /** @var array<string, mixed> */
        public array $lastData = [];

        public function render(
            string $template,
            array $data = [],
        ): Response {
            $this->lastTemplate = $template;
            $this->lastData = $data;

            return Response::html(html: '<html><body>MarkoTalk</body></html>');
        }

        public function renderToString(
            string $template,
            array $data = [],
        ): string {
            $this->lastTemplate = $template;
            $this->lastData = $data;

            return '<html><body>MarkoTalk</body></html>';
        }
    };
}

function makeLandingAuthManager(?AuthenticatableInterface $user = null): AuthManager
{
    /** @noinspection PhpMissingParentConstructorInspection - Test stub intentionally skips parent */
    return new class ($user) extends AuthManager {
        /** @noinspection PhpMissingParentConstructorInspection */
        public function __construct(
            private readonly ?AuthenticatableInterface $mockUser,
        ) {}

        public function check(): bool
        {
            return $this->mockUser !== null;
        }

        public function user(): ?AuthenticatableInterface
        {
            return $this->mockUser;
        }
    };
}

function makeLandingController(
    ?ViewInterface $view = null,
    ?AuthManager $auth = null,
): LandingController {
    return new LandingController(
        view: $view ?? makeLandingView(),
        auth: $auth ?? makeLandingAuthManager(),
    );
}

// ─── Tests ───────────────────────────────────────────────────────────────────

it('returns 200 for GET / without authentication', function (): void {
    $auth = makeLandingAuthManager(user: null);
    $controller = makeLandingController(auth: $auth);

    $request = new Request();
    $response = $controller->index(request: $request);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200);
});

it('redirects authenticated users from / to /home', function (): void {
    $user = new class implements AuthenticatableInterface {
        public function getAuthIdentifier(): int|string { return 1; }

        public function getAuthIdentifierName(): string { return 'id'; }

        public function getAuthPassword(): string { return 'hashed'; }

        public function getRememberToken(): ?string { return null; }

        public function setRememberToken(?string $token): void {}

        public function getRememberTokenName(): string { return 'remember_token'; }
    };
    $auth = makeLandingAuthManager(user: $user);
    $controller = makeLandingController(auth: $auth);

    $request = new Request();
    $response = $controller->index(request: $request);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/home');
});

it('renders the landing page template with landing::index', function (): void {
    $view = makeLandingView();
    $controller = makeLandingController(view: $view);

    $request = new Request();
    $controller->index(request: $request);

    expect($view->lastTemplate)->toBe('landing::index');
});

it('includes a link to /login on the landing page', function (): void {
    $landingTemplate = file_get_contents(filename: __DIR__ . '/../../resources/views/index.latte');

    expect($landingTemplate)->toContain('href="/login"');
});

it('includes a link to /register on the landing page', function (): void {
    $landingTemplate = file_get_contents(filename: __DIR__ . '/../../resources/views/index.latte');

    expect($landingTemplate)->toContain('href="/register"');
});

it('registers the App\Landing namespace in composer.json autoload', function (): void {
    $composerPath = dirname(path: __DIR__, levels: 4) . '/composer.json';
    $composer = json_decode(json: file_get_contents(filename: $composerPath), associative: true);

    expect($composer['autoload']['psr-4'])->toHaveKey('App\\Landing\\');
});
