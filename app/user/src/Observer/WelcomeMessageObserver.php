<?php

declare(strict_types=1);

namespace App\User\Observer;

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Event\UserRegisteredEvent;
use DateTimeImmutable;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Config\Exceptions\ConfigNotFoundException;
use Marko\Core\Attributes\Observer;

#[Observer(event: UserRegisteredEvent::class)]
readonly class WelcomeMessageObserver
{
    public function __construct(
        private SpaceRepositoryInterface $spaceRepository,
        private SpaceMembershipRepositoryInterface $membershipRepository,
        private MessageRepositoryInterface $messageRepository,
        private ConfigRepositoryInterface $config,
    ) {}

    /**
     * @throws ConfigNotFoundException
     */
    public function handle(UserRegisteredEvent $event): void
    {
        $user = $event->user;

        if (!$user instanceof User) {
            return;
        }

        $defaultSpaces = $this->config->getArray(key: 'markotalk.default_spaces');

        $generalSpace = null;

        foreach ($defaultSpaces as $spaceConfig) {
            $space = $this->spaceRepository->findBySlug(slug: $spaceConfig['slug']);

            if ($space === null) {
                continue;
            }

            $membership = new SpaceMembership(
                id: null,
                userId: $user->id,
                spaceId: $space->id,
                lastReadMessageId: null,
                joinedAt: new DateTimeImmutable(),
            );

            $this->membershipRepository->save(entity: $membership);

            if ($spaceConfig['slug'] === 'general') {
                $generalSpace = $space;
            }
        }

        if ($generalSpace !== null) {
            $body = sprintf('Welcome @%s to MarkoTalk!', $user->username);
            $message = new Message(
                id: null,
                spaceId: $generalSpace->id,
                userId: $user->id,
                body: $body,
                bodyHtml: $body,
                isPinned: false,
                editedAt: null,
                createdAt: new DateTimeImmutable(),
            );

            $this->messageRepository->save(entity: $message);
        }
    }
}
