<?php

declare(strict_types=1);

namespace App\Controller;

use Marko\Authentication\AuthManager;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class LandingController
{
    public function __construct(
        private ViewInterface $view,
        private AuthManager $auth,
    ) {}

    #[Get(path: '/')]
    public function index(
        Request $request,
    ): Response {
        if ($this->auth->check()) {
            return Response::redirect(url: '/home');
        }

        return $this->view->render(template: 'landing');
    }
}
