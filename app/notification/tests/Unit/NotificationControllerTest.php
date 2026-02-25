<?php

declare(strict_types=1);

use App\Notification\Controller\NotificationController;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Notification\Contracts\NotifiableInterface;
use Marko\Notification\Database\Entity\DatabaseNotification;
use Marko\Notification\Database\Repository\NotificationRepositoryInterface;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

function makeNotificationControllerView(): ViewInterface
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

            return new Response(body: '<html>notifications</html>', statusCode: 200);
        }

        public function renderToString(
            string $template,
            array $data = [],
        ): string {
            return '<html>notifications</html>';
        }
    };
}

function makeNotificationRepository(
    array $notifications = [],
    int $unreadCount = 0,
): NotificationRepositoryInterface {
    return new class ($notifications, $unreadCount) implements NotificationRepositoryInterface {
        public bool $markedReadCalled = false;

        public string $markedReadId = '';

        public bool $markedAllReadCalled = false;

        public function __construct(
            private array $notifications,
            private int $unreadCount,
        ) {}

        public function forNotifiable(NotifiableInterface $notifiable): array
        {
            return $this->notifications;
        }

        public function unread(NotifiableInterface $notifiable): array
        {
            return array_values(array: array_filter(
                array: $this->notifications,
                callback: fn (DatabaseNotification $n) => $n->readAt === null,
            ));
        }

        public function markAsRead(string $notificationId): void
        {
            $this->markedReadCalled = true;
            $this->markedReadId = $notificationId;
        }

        public function markAllAsRead(NotifiableInterface $notifiable): void
        {
            $this->markedAllReadCalled = true;
        }

        public function delete(string $notificationId): void {}

        public function deleteAll(NotifiableInterface $notifiable): void {}

        public function unreadCount(NotifiableInterface $notifiable): int
        {
            return $this->unreadCount;
        }
    };
}

function makeNotifiableUser(): AuthenticatableInterface&NotifiableInterface
{
    return new class implements AuthenticatableInterface, NotifiableInterface {
        public function getAuthIdentifier(): int|string
        {
            return 1;
        }

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthPassword(): string
        {
            return 'hashed_password';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken(?string $token): void {}

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }

        public function routeNotificationFor(string $channel): mixed
        {
            return null;
        }

        public function getNotifiableId(): string|int
        {
            return 1;
        }

        public function getNotifiableType(): string
        {
            return 'user';
        }
    };
}

function makeStubAuthManager(?AuthenticatableInterface $user = null): AuthManager
{
    return new class ($user) extends AuthManager {
        public function __construct(
            private readonly ?AuthenticatableInterface $mockUser,
        ) {
            // Skip parent constructor
        }

        public function user(): ?AuthenticatableInterface
        {
            return $this->mockUser;
        }
    };
}

function makeNotificationGetRequest(): Request
{
    return new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/notifications']);
}

function makeNotificationPostRequest(): Request
{
    return new Request(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/notifications/read']);
}

function makeNotificationController(
    NotificationRepositoryInterface $repository,
    ViewInterface $view,
    AuthManager $auth,
): NotificationController {
    return new NotificationController(
        notifications: $repository,
        view: $view,
        auth: $auth,
    );
}

function makeTestNotification(
    string $id = 'abc-123',
    ?string $readAt = null,
): DatabaseNotification {
    $notification = new DatabaseNotification();
    $notification->id = $id;
    $notification->type = 'MentionNotification';
    $notification->notifiableType = 'user';
    $notification->notifiableId = '1';
    $notification->data = '{"message_preview":"Hello @user"}';
    $notification->readAt = $readAt;
    $notification->createdAt = '2026-02-24 10:00:00';

    return $notification;
}

it('lists all notifications for the authenticated user', function (): void {
    $user = makeNotifiableUser();
    $notification = makeTestNotification();
    $repository = makeNotificationRepository(notifications: [$notification], unreadCount: 1);
    $view = makeNotificationControllerView();
    $auth = makeStubAuthManager(user: $user);
    $controller = makeNotificationController(repository: $repository, view: $view, auth: $auth);

    $response = $controller->index(request: makeNotificationGetRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200);
});

it('marks a single notification as read', function (): void {
    $user = makeNotifiableUser();
    $repository = makeNotificationRepository();
    $view = makeNotificationControllerView();
    $auth = makeStubAuthManager(user: $user);
    $controller = makeNotificationController(repository: $repository, view: $view, auth: $auth);

    $response = $controller->markRead(id: 'abc-123', request: makeNotificationPostRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($repository->markedReadCalled)->toBeTrue()
        ->and($repository->markedReadId)->toBe('abc-123');
});

it('marks all notifications as read', function (): void {
    $user = makeNotifiableUser();
    $repository = makeNotificationRepository();
    $view = makeNotificationControllerView();
    $auth = makeStubAuthManager(user: $user);
    $controller = makeNotificationController(repository: $repository, view: $view, auth: $auth);

    $response = $controller->markAllRead(request: makeNotificationPostRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($repository->markedAllReadCalled)->toBeTrue();
});

it('shows unread count in the header bell icon', function (): void {
    $user = makeNotifiableUser();
    $unreadNotification = makeTestNotification(id: 'unread-1');
    $readNotification = makeTestNotification(id: 'read-1', readAt: '2026-02-24 09:00:00');
    $repository = makeNotificationRepository(
        notifications: [$unreadNotification, $readNotification],
        unreadCount: 1,
    );
    $view = makeNotificationControllerView();
    $auth = makeStubAuthManager(user: $user);
    $controller = makeNotificationController(repository: $repository, view: $view, auth: $auth);

    $response = $controller->index(request: makeNotificationGetRequest());

    expect($response->statusCode())->toBe(200)
        ->and($view->lastData['unreadCount'])->toBe(1);
});

it('has notification.css with semantic @apply styles', function (): void {
    $cssPath = __DIR__ . '/../../src/css/notification.css';

    expect(file_exists(filename: $cssPath))->toBeTrue();

    $contents = file_get_contents(filename: $cssPath);

    expect($contents)->toContain('.notification-page')
        ->toContain('.notification-list')
        ->toContain('.notification-item')
        ->toContain('@apply');
});

it('contains no Tailwind utility classes in templates', function (): void {
    $templatePath = __DIR__ . '/../../resources/views/index.latte';

    expect(file_exists(filename: $templatePath))->toBeTrue();

    $contents = file_get_contents(filename: $templatePath);

    $utilityPattern = '/\bclass="[^"]*\b(flex|grid|p-\d|m-\d|bg-|text-[a-z]+-\d|rounded|shadow|border|items-|justify-|gap-|space-|w-\d|h-\d|overflow-|absolute|relative|top-|right-|bottom-|left-|opacity-)[^"]*"/';

    expect(preg_match(pattern: $utilityPattern, subject: $contents))->toBe(0);
});
