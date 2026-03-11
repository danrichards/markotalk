<?php

declare(strict_types=1);

namespace App\Landing\Controller;

use Marko\Authentication\AuthManager;
use Marko\Authentication\Exceptions\AuthException;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class LandingController
{
    public function __construct(
        private ViewInterface $view,
        private AuthManager $auth,
    ) {}

    /**
     * @throws AuthException
     */
    #[Get(path: '/')]
    public function index(): Response
    {
        if ($this->auth->check()) {
            return Response::redirect(url: '/home');
        }

        return $this->view->render(template: 'landing::index');
    }
}
