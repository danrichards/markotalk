<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Event\UserRegisteredEvent;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\PresenceTrackerInterface;
use DateTimeImmutable;
use Marko\Authentication\Contracts\GuardInterface;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Authentication\Middleware\GuestMiddleware;
use Marko\Core\Event\EventDispatcherInterface;
use Marko\Hashing\Contracts\HasherInterface;
use Marko\PubSub\Message;
use Marko\PubSub\PublisherInterface;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Middleware;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Security\Contracts\CsrfTokenManagerInterface;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Rules\Email;
use Marko\Validation\Rules\Min;
use Marko\Validation\Rules\Required;
use Marko\View\ViewInterface;

readonly class AuthController
{
    public function __construct(
        private ViewInterface $view,
        private GuardInterface $guard,
        private UserRepositoryInterface $userRepository,
        private HasherInterface $hasher,
        private ValidatorInterface $validator,
        private EventDispatcherInterface $eventDispatcher,
        private CsrfTokenManagerInterface $csrf,
        private PresenceTrackerInterface $presence,
        private SpaceRepositoryInterface $spaces,
        private ?PublisherInterface $publisher = null,
    ) {}

    #[Get(path: '/login')]
    #[Middleware(GuestMiddleware::class)]
    public function showLogin(Request $request): Response
    {
        return $this->view->render(template: 'user::auth/login', data: [
            'csrfToken' => $this->csrf->get(),
        ]);
    }

    #[Get(path: '/register')]
    #[Middleware(GuestMiddleware::class)]
    public function showRegister(Request $request): Response
    {
        return $this->view->render(template: 'user::auth/register', data: [
            'csrfToken' => $this->csrf->get(),
        ]);
    }

    #[Post(path: '/login')]
    #[Middleware(GuestMiddleware::class)]
    public function login(Request $request): Response
    {
        $credentials = [
            'email' => $request->post(key: 'email'),
            'password' => $request->post(key: 'password'),
        ];

        if ($this->guard->attempt(credentials: $credentials)) {
            return Response::redirect(url: '/home');
        }

        return $this->view->render(template: 'user::auth/login', data: [
            'csrfToken' => $this->csrf->get(),
            'errors' => ['email' => ['Invalid email or password.']],
        ]);
    }

    #[Post(path: '/register')]
    #[Middleware(GuestMiddleware::class)]
    public function register(Request $request): Response
    {
        $data = [
            'username' => $request->post(key: 'username'),
            'email' => $request->post(key: 'email'),
            'password' => $request->post(key: 'password'),
            'display_name' => $request->post(key: 'display_name'),
        ];

        $errors = $this->validator->validate(data: $data, rules: [
            'username' => [new Required()],
            'email' => [new Required(), new Email()],
            'password' => [new Required(), new Min(minimum: 8)],
        ]);

        if ($errors->isNotEmpty()) {
            return $this->view->render(template: 'user::auth/register', data: [
                'csrfToken' => $this->csrf->get(),
                'errors' => $errors->all(),
            ]);
        }

        if ($this->userRepository->findByEmail(email: (string) $data['email']) !== null) {
            return $this->view->render(template: 'user::auth/register', data: [
                'csrfToken' => $this->csrf->get(),
                'errors' => ['email' => ['The email address is already in use.']],
            ]);
        }

        $now = new DateTimeImmutable();
        $user = new User(
            id: null,
            username: (string) $data['username'],
            email: (string) $data['email'],
            password: $this->hasher->hash(value: (string) $data['password']),
            displayName: (string) ($data['display_name'] ?: $data['username']),
            avatarUrl: null,
            role: UserRole::User,
            isBanned: false,
            lastSeenAt: null,
            rememberToken: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->userRepository->save(entity: $user);
        $this->eventDispatcher->dispatch(event: new UserRegisteredEvent(user: $user));
        $this->guard->login(user: $user);

        return Response::redirect(url: '/home');
    }

    #[Post(path: '/logout')]
    #[Middleware(AuthMiddleware::class)]
    public function logout(Request $request): Response
    {
        $user = $this->guard->user();

        if ($user instanceof User) {
            $this->presence->markOffline(user: $user);
            $this->broadcastPresenceToAllSpaces();
        }

        $this->guard->logout();

        return Response::redirect(url: '/login');
    }

    private function broadcastPresenceToAllSpaces(): void
    {
        if ($this->publisher === null) {
            return;
        }

        $onlineIds = array_map(
            callback: fn(User $u): int => $u->id,
            array: $this->presence->getOnlineUsers(),
        );

        $payload = json_encode(value: ['type' => 'presence', 'onlineIds' => $onlineIds]);

        foreach ($this->spaces->findActive() as $space) {
            $channel = 'space:' . $space->slug;
            $this->publisher->publish(
                channel: $channel,
                message: new Message(channel: $channel, payload: $payload),
            );
        }
    }
}
