<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Middleware\AdminMiddleware;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepositoryInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Exceptions\AuthException;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class AdminUserController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ViewInterface $view,
        private AuthManager $auth,
    ) {}

    #[Get('/admin/users', middleware: [AdminMiddleware::class])]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->view->render(template: 'admin::users/index', data: [
            'users' => $users,
        ]);
    }

    /**
     * @throws AuthException
     */
    #[Post('/admin/users/{id}/ban', middleware: [AdminMiddleware::class])]
    public function ban(
        string $id,
    ): Response {
        $currentUser = $this->auth->user();
        $isSelf = $currentUser instanceof User && (int) $currentUser->getAuthIdentifier() === (int) $id;

        if ($isSelf) {
            return new Response(body: 'Cannot ban yourself', statusCode: 422);
        }

        $user = $this->userRepository->find(id: (int) $id);

        if (!$user instanceof User) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $user->isBanned = true;
        $this->userRepository->save(entity: $user);

        return Response::redirect(url: '/admin/users');
    }

    #[Post('/admin/users/{id}/unban', middleware: [AdminMiddleware::class])]
    public function unban(
        string $id,
    ): Response {
        $user = $this->userRepository->find(id: (int) $id);

        if (!$user instanceof User) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $user->isBanned = false;
        $this->userRepository->save(entity: $user);

        return Response::redirect(url: '/admin/users');
    }

    #[Post('/admin/users/{id}/role', middleware: [AdminMiddleware::class])]
    public function updateRole(
        string $id,
        Request $request,
    ): Response {
        $user = $this->userRepository->find(id: (int) $id);

        if (!$user instanceof User) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $roleValue = $request->post(key: 'role');
        $role = UserRole::tryFrom(value: (string) $roleValue);

        if ($role === null) {
            return new Response(body: 'Invalid role', statusCode: 422);
        }

        $user->role = $role;
        $this->userRepository->save(entity: $user);

        return Response::redirect(url: '/admin/users');
    }
}
