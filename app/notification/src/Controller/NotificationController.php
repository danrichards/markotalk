<?php

declare(strict_types=1);

namespace App\Notification\Controller;

use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Notification\Contracts\NotifiableInterface;
use Marko\Notification\Database\Repository\NotificationRepositoryInterface;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class NotificationController
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private ViewInterface $view,
        private AuthManager $auth,
    ) {}

    #[Get('/notifications', middleware: [AuthMiddleware::class])]
    public function index(
        Request $request,
    ): Response {
        $user = $this->auth->user();

        if (!$user instanceof NotifiableInterface) {
            return Response::redirect('/login');
        }

        $notifications = $this->notifications->forNotifiable(notifiable: $user);
        $unreadCount = $this->notifications->unreadCount(notifiable: $user);

        return $this->view->render('notification::index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Post('/notifications/{id}/read', middleware: [AuthMiddleware::class])]
    public function markRead(
        string $id,
        Request $request,
    ): Response {
        $this->notifications->markAsRead(notificationId: $id);

        return Response::redirect('/notifications');
    }

    #[Post('/notifications/read', middleware: [AuthMiddleware::class])]
    public function markAllRead(
        Request $request,
    ): Response {
        $user = $this->auth->user();

        if (!$user instanceof NotifiableInterface) {
            return Response::redirect('/login');
        }

        $this->notifications->markAllAsRead(notifiable: $user);

        return Response::redirect('/notifications');
    }
}
