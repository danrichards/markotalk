<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Middleware\AdminMiddleware;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use DateTimeImmutable;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Attributes\Put;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class AdminSpaceController
{
    public function __construct(
        private SpaceRepositoryInterface $spaces,
        private ViewInterface $view,
    ) {}

    #[Get('/admin/spaces', middleware: [AdminMiddleware::class])]
    public function index(
        Request $request,
    ): Response {
        $spaces = $this->spaces->findAll();

        return $this->view->render(template: 'admin::spaces.index', data: ['spaces' => $spaces]);
    }

    #[Post('/admin/spaces', middleware: [AdminMiddleware::class])]
    public function create(
        Request $request,
    ): Response {
        $name = (string) $request->post(key: 'name', default: '');
        $description = $request->post(key: 'description') !== null
            ? (string) $request->post(key: 'description')
            : null;

        $existing = $this->spaces->findOneBy(criteria: ['name' => $name]);

        if ($existing !== null) {
            return new Response(body: 'Space name already exists', statusCode: 422);
        }

        $slug = strtolower(string: str_replace(search: ' ', replace: '-', subject: $name));
        $now = new DateTimeImmutable();

        $space = new Space(
            id: null,
            name: $name,
            slug: $slug,
            description: $description,
            isArchived: false,
            createdBy: 1,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->spaces->save(entity: $space);

        return Response::redirect(url: '/admin/spaces');
    }

    #[Put('/admin/spaces/{id}', middleware: [AdminMiddleware::class])]
    public function update(
        string $id,
        Request $request,
    ): Response {
        $space = $this->spaces->find(id: (int) $id);

        if ($space === null) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $name = (string) $request->post(key: 'name', default: $space->name);
        $description = $request->post(key: 'description') !== null
            ? (string) $request->post(key: 'description')
            : $space->description;
        $slug = strtolower(string: str_replace(search: ' ', replace: '-', subject: $name));

        $updated = new Space(
            id: $space->id,
            name: $name,
            slug: $slug,
            description: $description,
            isArchived: $space->isArchived,
            createdBy: $space->createdBy,
            createdAt: $space->createdAt,
            updatedAt: new DateTimeImmutable(),
        );

        $this->spaces->save(entity: $updated);

        return Response::redirect(url: '/admin/spaces');
    }

    #[Post('/admin/spaces/{id}/archive', middleware: [AdminMiddleware::class])]
    public function archive(
        string $id,
        Request $request,
    ): Response {
        $space = $this->spaces->find(id: (int) $id);

        if ($space === null) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $archived = new Space(
            id: $space->id,
            name: $space->name,
            slug: $space->slug,
            description: $space->description,
            isArchived: !$space->isArchived,
            createdBy: $space->createdBy,
            createdAt: $space->createdAt,
            updatedAt: new DateTimeImmutable(),
        );

        $this->spaces->save(entity: $archived);

        return Response::redirect(url: '/admin/spaces');
    }
}
