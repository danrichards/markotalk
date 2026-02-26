<?php

declare(strict_types=1);

namespace App\Message\Controller;

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Repository\ReactionRepositoryInterface;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use DateTimeImmutable;
use Marko\Authentication\AuthManager;
use App\User\Middleware\PresenceMiddleware;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Authorization\Contracts\GateInterface;
use Marko\Authorization\Exceptions\AuthorizationException;
use Marko\RateLimiting\Contracts\RateLimiterInterface;
use Marko\Routing\Attributes\Delete;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Attributes\Put;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Exceptions\ValidationException;

readonly class MessageController
{
    public function __construct(
        private MessageRepositoryInterface $messages,
        private SpaceRepositoryInterface $spaces,
        private SpaceMembershipRepositoryInterface $memberships,
        private AuthManager $auth,
        private ValidatorInterface $validator,
        private ConfigRepositoryInterface $config,
        private ?GateInterface $gate = null,
        private ?RateLimiterInterface $rateLimiter = null,
        private ?ReactionRepositoryInterface $reactions = null,
    ) {}

    /**
     * @throws \JsonException
     */
    #[Post('/spaces/{slug}/messages', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function send(
        string $slug,
        Request $request,
    ): Response {
        $maxLength = $this->config->getInt(key: 'markotalk.max_message_length');

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

        $space = $this->spaces->findBySlug(slug: $slug);

        if ($space === null) {
            return Response::json(
                data: ['error' => 'Space not found'],
                statusCode: 404,
            );
        }

        $user = $this->auth->user();

        if ($user === null) {
            return Response::json(
                data: ['error' => 'Unauthenticated'],
                statusCode: 401,
            );
        }

        if ($this->rateLimiter !== null) {
            $userId = (int) $user->getAuthIdentifier();
            $maxAttempts = $this->config->getInt(key: 'markotalk.rate_limit_messages');
            $decaySeconds = $this->config->getInt(key: 'markotalk.rate_limit_window');
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

        $body = (string) $request->post(key: 'body');

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

        $this->messages->save(entity: $message);

        return Response::json(
            data: ['id' => $message->id],
            statusCode: 201,
        );
    }

    #[Put('/messages/{id}', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function edit(
        int $id,
        Request $request,
    ): Response {
        $message = $this->messages->find(id: $id);

        if ($message === null) {
            return Response::json(
                data: ['error' => 'Message not found'],
                statusCode: 404,
            );
        }

        /** @var Message $message */
        $user = $this->auth->user();

        if ($user === null) {
            return Response::json(
                data: ['error' => 'Unauthenticated'],
                statusCode: 401,
            );
        }

        $userId = (int) $user->getAuthIdentifier();
        $isAdmin = $user instanceof User && $user->role === UserRole::Admin;

        if ($message->userId !== $userId && !$isAdmin) {
            return Response::json(
                data: ['error' => 'Forbidden'],
                statusCode: 403,
            );
        }

        $maxLength = $this->config->getInt(key: 'markotalk.max_message_length');

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

        $body = (string) $request->post(key: 'body');
        $message->body = $body;
        $message->bodyHtml = htmlspecialchars(string: $body, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8');
        $message->editedAt = new DateTimeImmutable();

        $this->messages->save(entity: $message);

        return Response::json(
            data: ['id' => $message->id],
            statusCode: 200,
        );
    }

    #[Delete('/messages/{id}', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function delete(
        int $id,
        Request $request,
    ): Response {
        $message = $this->messages->find(id: $id);

        if ($message === null) {
            return Response::json(
                data: ['error' => 'Message not found'],
                statusCode: 404,
            );
        }

        /** @var Message $message */
        $user = $this->auth->user();

        if ($user === null) {
            return Response::json(
                data: ['error' => 'Unauthenticated'],
                statusCode: 401,
            );
        }

        $userId = (int) $user->getAuthIdentifier();
        $isAdmin = $user instanceof User && $user->role === UserRole::Admin;

        if ($message->userId !== $userId && !$isAdmin) {
            return Response::json(
                data: ['error' => 'Forbidden'],
                statusCode: 403,
            );
        }

        $this->memberships->clearLastReadMessageId(messageId: (int) $message->id);
        $this->messages->delete(entity: $message);

        return Response::json(
            data: ['success' => true],
            statusCode: 200,
        );
    }

    #[Post('/messages/{id}/pin', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function pin(
        int $id,
        Request $request,
    ): Response {
        $message = $this->messages->find(id: $id);

        if ($message === null) {
            return Response::json(
                data: ['error' => 'Message not found'],
                statusCode: 404,
            );
        }

        /** @var Message $message */
        try {
            $this->gate?->authorize(ability: 'pin', resource: $message);
        } catch (AuthorizationException) {
            return Response::json(
                data: ['error' => 'Forbidden'],
                statusCode: 403,
            );
        }

        $message->isPinned = !$message->isPinned;
        $this->messages->save(entity: $message);

        $referer = $request->header(name: 'Referer') ?? '/';

        return Response::redirect(url: $referer);
    }

    #[Post('/messages/{id}/reactions', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function react(
        int $id,
        Request $request,
    ): Response {
        $message = $this->messages->find(id: $id);

        if ($message === null) {
            return Response::json(
                data: ['error' => 'Message not found'],
                statusCode: 404,
            );
        }

        $user = $this->auth->user();

        if ($user === null) {
            return Response::json(
                data: ['error' => 'Unauthenticated'],
                statusCode: 401,
            );
        }

        $emoji = (string) $request->post(key: 'emoji');
        $userId = (int) $user->getAuthIdentifier();

        $existing = $this->reactions?->findOneBy(criteria: [
            'message_id' => $id,
            'user_id' => $userId,
            'emoji' => $emoji,
        ]);

        if ($existing !== null) {
            $this->reactions?->remove(messageId: $id, userId: $userId, emoji: $emoji);
        } else {
            $this->reactions?->add(messageId: $id, userId: $userId, emoji: $emoji);
        }

        $grouped = $this->reactions?->findGrouped(messageId: $id, userId: $userId) ?? [];

        return Response::json(data: $grouped);
    }

    #[Get('/spaces/{slug}/messages', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function history(
        string $slug,
        Request $request,
    ): Response {
        $space = $this->spaces->findBySlug(slug: $slug);

        if ($space === null) {
            return Response::json(
                data: ['error' => 'Space not found'],
                statusCode: 404,
            );
        }

        $cursor = $request->query(key: 'cursor') !== null
            ? (string) $request->query(key: 'cursor')
            : null;

        $paginator = $this->messages->findPaginated(
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
}
