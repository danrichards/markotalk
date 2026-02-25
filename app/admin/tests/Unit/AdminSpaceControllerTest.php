<?php

declare(strict_types=1);

use App\Admin\Controller\AdminSpaceController;
use App\Admin\Middleware\AdminMiddleware;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use Marko\Database\Entity\Entity;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

// ─── Helper factories ────────────────────────────────────────────────────────

function makeAdminSpaceView(): ViewInterface
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

            return new Response(body: '<html>admin spaces</html>', statusCode: 200);
        }

        public function renderToString(
            string $template,
            array $data = [],
        ): string {
            return '<html>admin spaces</html>';
        }
    };
}

function makeAdminSpaceRepository(
    array $all = [],
    ?Space $byName = null,
): SpaceRepositoryInterface {
    return new class (all: $all, byName: $byName) implements SpaceRepositoryInterface {
        public bool $saveCalled = false;

        /** @var ?Space */
        public ?Space $savedEntity = null;

        public function __construct(
            private readonly array $all,
            private readonly ?Space $byName,
        ) {}

        public function findBySlug(string $slug): ?Space
        {
            return null;
        }

        public function findActive(): array
        {
            return array_values(array: array_filter(
                array: $this->all,
                callback: fn (Space $s) => !$s->isArchived,
            ));
        }

        public function find(int $id): ?Entity
        {
            foreach ($this->all as $space) {
                if ($space->id === $id) {
                    return $space;
                }
            }

            return null;
        }

        public function findOrFail(int $id): Entity
        {
            $space = $this->find(id: $id);

            if ($space === null) {
                throw new RuntimeException(message: "Space {$id} not found");
            }

            return $space;
        }

        public function findAll(): array
        {
            return $this->all;
        }

        public function findBy(array $criteria): array
        {
            if (isset($criteria['name'])) {
                return $this->byName !== null ? [$this->byName] : [];
            }

            return [];
        }

        public function findOneBy(array $criteria): ?Entity
        {
            if (isset($criteria['name'])) {
                return $this->byName;
            }

            return null;
        }

        public function save(Entity $entity): void
        {
            $this->saveCalled = true;
            $this->savedEntity = $entity;
        }

        public function delete(Entity $entity): void {}
    };
}

function makeAdminSpaceController(
    SpaceRepositoryInterface $spaces,
    ViewInterface $view,
): AdminSpaceController {
    return new AdminSpaceController(
        spaces: $spaces,
        view: $view,
    );
}

function makeAdminSpace(
    int $id = 1,
    string $name = 'General',
    string $slug = 'general',
    bool $isArchived = false,
): Space {
    return new Space(
        id: $id,
        name: $name,
        slug: $slug,
        description: null,
        isArchived: $isArchived,
        createdBy: 1,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeAdminSpaceGetRequest(string $uri = '/admin/spaces'): Request
{
    return new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri]);
}

function makeAdminSpacePostRequest(
    string $uri = '/admin/spaces',
    array $post = [],
): Request {
    return new Request(
        server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => $uri],
        post: $post,
    );
}

function makeAdminSpacePutRequest(
    string $uri = '/admin/spaces/1',
    array $post = [],
): Request {
    return new Request(
        server: ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => $uri],
        post: $post,
    );
}

// ─── Tests ───────────────────────────────────────────────────────────────────

it('lists all spaces including archived ones for admins', function (): void {
    $active = makeAdminSpace();
    $archived = makeAdminSpace(id: 2, name: 'Archive', slug: 'archive', isArchived: true);

    $spaces = makeAdminSpaceRepository(all: [$active, $archived]);
    $view = makeAdminSpaceView();
    $controller = makeAdminSpaceController(spaces: $spaces, view: $view);

    $response = $controller->index(request: makeAdminSpaceGetRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('admin::spaces.index')
        ->and($view->lastData['spaces'])->toHaveCount(2)
        ->and($view->lastData['spaces'][0])->toBe($active)
        ->and($view->lastData['spaces'][1])->toBe($archived);
});

it('creates a new space with valid name and description', function (): void {
    $spaces = makeAdminSpaceRepository();
    $view = makeAdminSpaceView();
    $controller = makeAdminSpaceController(spaces: $spaces, view: $view);

    $response = $controller->create(
        request: makeAdminSpacePostRequest(
            post: ['name' => 'Random', 'description' => 'Random chat'],
        ),
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/admin/spaces')
        ->and($spaces->saveCalled)->toBeTrue()
        ->and($spaces->savedEntity)->not->toBeNull()
        ->and($spaces->savedEntity->name)->toBe('Random')
        ->and($spaces->savedEntity->slug)->toBe('random')
        ->and($spaces->savedEntity->description)->toBe('Random chat');
});

it('rejects creating a space with duplicate name', function (): void {
    $existing = makeAdminSpace();
    $spaces = makeAdminSpaceRepository(all: [$existing], byName: $existing);
    $view = makeAdminSpaceView();
    $controller = makeAdminSpaceController(spaces: $spaces, view: $view);

    $response = $controller->create(
        request: makeAdminSpacePostRequest(
            post: ['name' => 'General', 'description' => ''],
        ),
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(422)
        ->and($spaces->saveCalled)->toBeFalse();
});

it('updates space name and description', function (): void {
    $existing = makeAdminSpace();
    $spaces = makeAdminSpaceRepository(all: [$existing]);
    $view = makeAdminSpaceView();
    $controller = makeAdminSpaceController(spaces: $spaces, view: $view);

    $response = $controller->update(
        id: '1',
        request: makeAdminSpacePutRequest(
            post: ['name' => 'General Chat', 'description' => 'Updated description'],
        ),
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/admin/spaces')
        ->and($spaces->saveCalled)->toBeTrue()
        ->and($spaces->savedEntity)->not->toBeNull()
        ->and($spaces->savedEntity->name)->toBe('General Chat')
        ->and($spaces->savedEntity->slug)->toBe('general-chat')
        ->and($spaces->savedEntity->description)->toBe('Updated description');
});

it('archives a space', function (): void {
    $active = makeAdminSpace();
    $spaces = makeAdminSpaceRepository(all: [$active]);
    $view = makeAdminSpaceView();
    $controller = makeAdminSpaceController(spaces: $spaces, view: $view);

    $response = $controller->archive(
        id: '1',
        request: makeAdminSpacePostRequest(uri: '/admin/spaces/1/archive'),
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/admin/spaces')
        ->and($spaces->saveCalled)->toBeTrue()
        ->and($spaces->savedEntity)->not->toBeNull()
        ->and($spaces->savedEntity->isArchived)->toBeTrue();
});

it('requires admin role for all actions', function (): void {
    $reflection = new ReflectionClass(objectOrClass: AdminSpaceController::class);
    $methods = ['index', 'create', 'update', 'archive'];

    foreach ($methods as $methodName) {
        $method = $reflection->getMethod(name: $methodName);
        $routeAttributes = $method->getAttributes();
        $hasAdminMiddleware = false;

        foreach ($routeAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if (property_exists(object_or_class: $instance, property: 'middleware')) {
                $middleware = $instance->middleware;
                if (in_array(needle: AdminMiddleware::class, haystack: $middleware, strict: true)) {
                    $hasAdminMiddleware = true;
                    break;
                }
            }
        }

        expect($hasAdminMiddleware)->toBeTrue("Method {$methodName} must have AdminMiddleware");
    }
});
