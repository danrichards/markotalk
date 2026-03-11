<?php

declare(strict_types=1);

namespace App\Message\Controller;

use App\Message\Entity\Message;
use App\Message\Entity\Reaction;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Repository\ReactionRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use JsonException;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Exceptions\AuthException;
use App\User\Middleware\PresenceMiddleware;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Config\Exceptions\ConfigNotFoundException;
use Marko\Authorization\Contracts\GateInterface;
use Marko\Authorization\Exceptions\AuthorizationException;
use Marko\Pagination\Exceptions\PaginationException;
use Marko\PubSub\Message as PubSubMessage;
use Marko\PubSub\PublisherInterface;
use Marko\RateLimiting\Contracts\RateLimiterInterface;
use Marko\Routing\Attributes\Delete;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Attributes\Put;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Exceptions\ValidationException;
use Marko\View\ViewInterface;

readonly class MessageController
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private SpaceRepositoryInterface $spaceRepository,
        private AuthManager $auth,
        private ValidatorInterface $validator,
        private ConfigRepositoryInterface $configRepository,
        private ?SpaceMembershipRepositoryInterface $spaceMembershipRepository = null,
        private ?GateInterface $gate = null,
        private ?RateLimiterInterface $rateLimiter = null,
        private ?ReactionRepositoryInterface $reactionRepository = null,
        private ?PublisherInterface $publisher = null,
        private ?UserRepositoryInterface $userRepository = null,
        private ?ViewInterface $view = null,
    ) {}

    /**
     * @throws JsonException|ConfigNotFoundException|AuthException
     */
    #[Post('/spaces/{slug}/messages', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function send(
        string $slug,
        Request $request,
    ): Response {
        $maxLength = $this->configRepository->getInt(key: 'markotalk.max_message_length');

        try {
            $this->validator->validateOrFail(
                data: $request->post(),
                rules: [
                    'body' => [
                        'required', "max:$maxLength",
                    ],
                ],
            );
        } catch (ValidationException $e) {
            return Response::json(
                data: ['errors' => $e->errors()->all()],
                statusCode: 422,
            );
        }

        $space = $this->spaceRepository->findBySlug(slug: $slug);

        if (!$space instanceof Space) {
            return Response::json(
                data: ['error' => 'Space not found'],
                statusCode: 404,
            );
        }

        $user = $this->auth->user();

        if (!$user instanceof User) {
            return Response::json(
                data: ['error' => 'Unauthenticated'],
                statusCode: 401,
            );
        }

        if ($this->rateLimiter !== null) {
            $userId = (int) $user->getAuthIdentifier();
            $maxAttempts = $this->configRepository->getInt(key: 'markotalk.rate_limit_messages');
            $decaySeconds = $this->configRepository->getInt(key: 'markotalk.rate_limit_window');

            $result = $this->rateLimiter->attempt(
                key: "user_{$userId}_messages",
                maxAttempts: $maxAttempts,
                decaySeconds: $decaySeconds,
            );

            if (!$result->allowed()) {
                return new Response(
                    body: (string) json_encode(value: ['error' => 'Too many messages. Please wait before sending again.'], flags: JSON_THROW_ON_ERROR),
                    statusCode: 429,
                    headers: [
                        'Content-Type' => 'application/json',
                        'Retry-After' => (string) $result->retryAfter(),
                    ],
                );
            }
        }

        $body = $request->post(key: 'body') ?? '';

        $message = new Message(
            id: null,
            spaceId: (int) $space->id,
            userId: (int) $user->getAuthIdentifier(),
            body: $body,
            bodyHtml: htmlspecialchars(string: $body, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8'),
            isPinned: false,
            editedAt: null,
            createdAt: new DateTimeImmutable(),
        );

        $this->messageRepository->save(entity: $message);

        if ($this->publisher !== null && $this->view !== null) {
            $userEntity = $this->userRepository?->find(id: $message->userId);
            $authorName = $userEntity instanceof User ? ($userEntity->displayName ?: $userEntity->username) : 'Unknown User';
            $html = $this->view->renderToString(
                template: 'message::_message',
                data: [
                    'message' => $message,
                    'userMap' => [$message->userId => $authorName],
                ],
            );
            $payload = json_encode(
                value: [
                    'type' => 'message',
                    'id' => $message->id,
                    'userId' => $message->userId,
                    'html' => $html,
                ],
                flags: JSON_THROW_ON_ERROR,
            );
            $this->publisher->publish(
                channel: "space:$slug",
                message: new PubSubMessage(channel: "space:$slug", payload: $payload),
            );
        }

        return Response::json(
            data: ['id' => $message->id],
            statusCode: 201,
        );
    }

    /**
     * @throws JsonException|ConfigNotFoundException|AuthException
     */
    #[Put('/messages/{id}', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function edit(
        int $id,
        Request $request,
    ): Response {
        $result = $this->authorizeMessageOwner(id: $id);

        if ($result instanceof Response) {
            return $result;
        }

        $message = $result['message'];
        $maxLength = $this->configRepository->getInt(key: 'markotalk.max_message_length');

        try {
            $this->validator->validateOrFail(
                data: $request->post(),
                rules: [
                    'body' => ['required', "max:$maxLength"],
                ],
            );
        } catch (ValidationException $e) {
            return Response::json(
                data: ['errors' => $e->errors()->all()],
                statusCode: 422,
            );
        }

        $body = $request->post(key: 'body') ?? '';
        $message->body = $body;
        $message->bodyHtml = htmlspecialchars(string: $body, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8');
        $message->editedAt = new DateTimeImmutable();

        $this->messageRepository->save(entity: $message);

        if ($this->publisher !== null) {
            $space = $this->spaceRepository->find(id: $message->spaceId);
            $slug = $space instanceof Space ? $space->slug : (string) $message->spaceId;
            $payload = json_encode(
                value: [
                    'type' => 'message_edited',
                    'id' => $message->id,
                    'bodyHtml' => $message->bodyHtml,
                ],
                flags: JSON_THROW_ON_ERROR,
            );
            $this->publisher->publish(
                channel: "space:$slug",
                message: new PubSubMessage(channel: "space:$slug", payload: $payload),
            );
        }

        return Response::json(
            data: ['id' => $message->id],
        );
    }

    /**
     * @throws JsonException|AuthException
     */
    #[Delete('/messages/{id}', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function delete(
        int $id,
    ): Response {
        $result = $this->authorizeMessageOwner(id: $id);

        if ($result instanceof Response) {
            return $result;
        }

        $message = $result['message'];
        $messageId = (int) $message->id;
        $spaceId = $message->spaceId;

        $this->spaceMembershipRepository?->clearLastReadMessageId(messageId: $messageId);
        $this->messageRepository->delete(entity: $message);

        if ($this->publisher !== null) {
            $space = $this->spaceRepository->find(id: $spaceId);
            $slug = $space instanceof Space ? $space->slug : (string) $spaceId;
            $payload = json_encode(
                value: [
                    'type' => 'message_deleted',
                    'id' => $messageId,
                ],
                flags: JSON_THROW_ON_ERROR,
            );
            $this->publisher->publish(
                channel: "space:$slug",
                message: new PubSubMessage(channel: "space:$slug", payload: $payload),
            );
        }

        return Response::json(data: ['success' => true]);
    }

    /**
     * @throws JsonException
     */
    #[Post('/messages/{id}/pin', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function pin(
        int $id,
        Request $request,
    ): Response {
        $message = $this->messageRepository->find(id: $id);

        if (!$message instanceof Message) {
            return Response::json(
                data: ['error' => 'Message not found'],
                statusCode: 404,
            );
        }

        try {
            $this->gate?->authorize('pin', $message);
        } catch (AuthorizationException) {
            return Response::json(
                data: ['error' => 'Forbidden'],
                statusCode: 403,
            );
        }

        $message->isPinned = !$message->isPinned;
        $this->messageRepository->save(entity: $message);
        $referer = $request->header(name: 'Referer') ?? '/';

        return Response::redirect(url: $referer);
    }

    /**
     * @throws AuthException|JsonException
     */
    #[Post('/messages/{id}/reactions', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function react(
        int $id,
        Request $request,
    ): Response {
        $message = $this->messageRepository->find(id: $id);

        if (!$message instanceof Message) {
            return Response::json(
                data: ['error' => 'Message not found'],
                statusCode: 404,
            );
        }

        $user = $this->auth->user();

        if (!$user instanceof User) {
            return Response::json(
                data: ['error' => 'Unauthenticated'],
                statusCode: 401,
            );
        }

        $emoji = $request->post(key: 'emoji') ?? '';
        $userId = (int) $user->getAuthIdentifier();

        $existing = $this->reactionRepository?->findOneBy(criteria: [
            'message_id' => $id,
            'user_id' => $userId,
            'emoji' => $emoji,
        ]);

        if ($existing instanceof Reaction) {
            $this->reactionRepository?->remove(messageId: $id, userId: $userId, emoji: $emoji);
        } else {
            $this->reactionRepository?->add(messageId: $id, userId: $userId, emoji: $emoji);
        }

        $grouped = $this->reactionRepository?->findGrouped(messageId: $id, userId: $userId) ?? [];

        return Response::json(data: $grouped);
    }

    /**
     * @throws PaginationException|JsonException
     */
    #[Get('/spaces/{slug}/messages', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function history(
        string $slug,
        Request $request,
    ): Response {
        $space = $this->spaceRepository->findBySlug(slug: $slug);

        if (!$space instanceof Space) {
            return Response::json(
                data: ['error' => 'Space not found'],
                statusCode: 404,
            );
        }

        $cursor = $request->query(key: 'cursor');

        $paginator = $this->messageRepository->findPaginated(
            spaceId: (int) $space->id,
            cursor: $cursor,
        );

        $items = array_map(
            callback: fn (Message $m): array => [
                'id' => $m->id,
                'spaceId' => $m->spaceId,
                'userId' => $m->userId,
                'body' => $m->body,
                'bodyHtml' => $m->bodyHtml,
                'isPinned' => $m->isPinned,
                'editedAt' => $m->editedAt?->format(format: 'Y-m-d H:i:s'),
                'createdAt' => $m->createdAt->format(format: 'Y-m-d H:i:s'),
            ],
            array: $paginator->items(),
        );

        $data = $paginator->toArray();
        $data['items'] = $items;

        return Response::json(data: $data);
    }

    /**
     * Authorize the current user to modify a message (owner or admin).
     *
     * @return Response|array{message: Message, user: User} Error response or authorized context
     * @throws JsonException|AuthException
     */
    private function authorizeMessageOwner(int $id): Response|array
    {
        $message = $this->messageRepository->find(id: $id);

        if (!$message instanceof Message) {
            return Response::json(
                data: ['error' => 'Message not found'],
                statusCode: 404,
            );
        }

        $user = $this->auth->user();

        if (!$user instanceof User) {
            return Response::json(
                data: ['error' => 'Unauthenticated'],
                statusCode: 401,
            );
        }

        $userId = (int) $user->getAuthIdentifier();
        $isAdmin = $user->role === UserRole::Admin;

        if ($message->userId !== $userId && !$isAdmin) {
            return Response::json(
                data: ['error' => 'Forbidden'],
                statusCode: 403,
            );
        }

        return ['message' => $message, 'user' => $user];
    }
}
