<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Middleware;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\View\ViewInterface;

readonly class ProfileController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ViewInterface $view,
        private AuthManager $auth,
        private ValidatorInterface $validator,
    ) {}

    #[Get(path: '/profile')]
    #[Middleware(AuthMiddleware::class)]
    public function edit(Request $request): Response
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return Response::redirect(url: '/login');
        }

        return $this->view->render(template: 'user::profile/edit', data: [
            'user' => $user,
        ]);
    }

    #[Post(path: '/profile')]
    #[Middleware(AuthMiddleware::class)]
    public function update(Request $request): Response
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return Response::redirect(url: '/login');
        }

        $data = $request->post();
        $errors = $this->validator->validate(
            data: $data,
            rules: [
                'display_name' => ['required', 'string', 'max:100'],
                'avatar_url' => ['nullable', 'url'],
            ],
        );

        if ($errors->isNotEmpty()) {
            return $this->view->render(template: 'user::profile/edit', data: [
                'user' => $user,
                'errors' => $errors,
            ]);
        }

        $displayName = $request->post(key: 'display_name');

        if (is_string($displayName)) {
            $user->displayName = $displayName;
        }

        $avatarUrl = $request->post(key: 'avatar_url');

        if (is_string($avatarUrl) && $avatarUrl !== '') {
            $user->avatarUrl = $avatarUrl;
        }

        $this->userRepository->save(entity: $user);

        return Response::redirect(url: '/profile');
    }
}
