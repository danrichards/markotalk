<?php

declare(strict_types=1);

namespace App\Notification\Controller;

use Marko\Authentication\AuthManager;
use Marko\Authentication\Exceptions\AuthException;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Notification\Contracts\NotifiableInterface;
use Marko\Notification\Database\Repository\NotificationRepositoryInterface;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class NotificationController
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private ViewInterface $view,
        private AuthManager $auth,
    ) {}

    /**
     * @throws AuthException
     */
    #[Get('/notifications', middleware: [AuthMiddleware::class])]
    public function index(): Response
    {
        $user = $this->auth->user();

        if (!$user instanceof NotifiableInterface) {
            return Response::redirect('/login');
        }

        $notifications = $this->notificationRepository->forNotifiable(notifiable: $user);
        $unreadCount = $this->notificationRepository->unreadCount(notifiable: $user);

        return $this->view->render('notification::index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Post('/notifications/{id}/read', middleware: [AuthMiddleware::class])]
    public function markRead(
        string $id,
    ): Response {
        $this->notificationRepository->markAsRead(notificationId: $id);

        return Response::redirect('/notifications');
    }

    /**
     * @throws AuthException
     */
    #[Post('/notifications/read', middleware: [AuthMiddleware::class])]
    public function markAllRead(): Response
    {
        $user = $this->auth->user();

        if (!$user instanceof NotifiableInterface) {
            return Response::redirect('/login');
        }

        $this->notificationRepository->markAllAsRead(notifiable: $user);

        return Response::redirect('/notifications');
    }
}
