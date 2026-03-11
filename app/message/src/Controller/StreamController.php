<?php

declare(strict_types=1);

namespace App\Message\Controller;

use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Middleware\PresenceMiddleware;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Config\Exceptions\ConfigNotFoundException;
use Marko\PubSub\SubscriberInterface;
use Marko\Routing\Attributes\Get;
use Marko\Sse\Exceptions\SseException;
use Marko\Sse\SseStream;
use Marko\Sse\StreamingResponse;

readonly class StreamController
{
    public function __construct(
        private SpaceRepositoryInterface $spaceRepository,
        private SubscriberInterface $subscriber,
        private ConfigRepositoryInterface $config,
    ) {}

    /**
     * @throws ConfigNotFoundException|SseException
     */
    #[Get('/spaces/{slug}/stream', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function stream(
        string $slug,
    ): StreamingResponse {
        $this->spaceRepository->findBySlug(slug: $slug);

        $timeout = $this->config->getInt(key: 'markotalk.sse_timeout');

        $subscription = $this->subscriber->subscribe("space:$slug");

        return new StreamingResponse(
            stream: new SseStream(
                subscription: $subscription,
                timeout: $timeout,
            ),
        );
    }
}
