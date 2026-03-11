<?php

declare(strict_types=1);

namespace App\Space\Controller;

use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\PresenceTrackerInterface;
use DateTimeImmutable;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Exceptions\AuthException;
use App\User\Middleware\PresenceMiddleware;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Response;
use Marko\Security\Contracts\CsrfTokenManagerInterface;
use Marko\View\ViewInterface;

readonly class SpaceController
{
    public function __construct(
        private SpaceRepositoryInterface $spaceRepository,
        private SpaceMembershipRepositoryInterface $spaceMembershipRepository,
        private MessageRepositoryInterface $messageRepository,
        private UserRepositoryInterface $userRepository,
        private ViewInterface $view,
        private AuthManager $auth,
        private CsrfTokenManagerInterface $csrf,
        private PresenceTrackerInterface $presence,
    ) {}

    #[Get('/home', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function index(): Response
    {
        $general = $this->spaceRepository->findBySlug(slug: 'general');

        if ($general instanceof Space) {
            return Response::redirect(url: '/spaces/general');
        }

        $activeSpaces = $this->spaceRepository->findActive();

        if ($activeSpaces !== []) {
            return Response::redirect(url: '/spaces/' . $activeSpaces[0]->slug);
        }

        return Response::redirect(url: '/spaces');
    }

    /**
     * @throws AuthException
     */
    #[Get('/spaces/{slug}', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function show(
        string $slug,
    ): Response {
        $space = $this->spaceRepository->findBySlug(slug: $slug);

        if (!$space instanceof Space) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $userId = (int) $this->auth->user()->getAuthIdentifier();
        $membership = $this->spaceMembershipRepository->findByUserAndSpace(userId: $userId, spaceId: (int) $space->id);

        if (!$membership instanceof SpaceMembership) {
            $membership = $this->createMembership(userId: $userId, space: $space);
        }

        $messages = $this->messageRepository->findBySpace(spaceId: (int) $space->id);

        if ($messages !== []) {
            $this->spaceMembershipRepository->updateLastReadMessageId(
                membership: $membership,
                messageId: (int) $messages[array_key_last($messages)]->id,
            );
        }

        $allSpaces = $this->spaceRepository->findActive();
        $spaceMemberships = $this->spaceMembershipRepository->findAllForSpace(spaceId: (int) $space->id);

        $members = [];
        foreach ($spaceMemberships as $m) {
            $user = $this->userRepository->find(id: $m->userId);
            if ($user instanceof User) {
                $members[] = $user;
            }
        }

        $userMap = [];
        foreach ($members as $member) {
            $userMap[$member->id] = $member->displayName ?: $member->username;
        }

        foreach ($messages as $message) {
            if (!isset($userMap[$message->userId])) {
                $user = $this->userRepository->find(id: $message->userId);
                if ($user instanceof User) {
                    $userMap[$user->id] = $user->displayName ?: $user->username;
                }
            }
        }

        $userMemberships = $this->spaceMembershipRepository->findAllForUser(userId: $userId);

        $unreadCounts = [];
        foreach ($userMemberships as $m) {
            $unreadCounts[$m->spaceId] = $this->spaceMembershipRepository->countUnread(userId: $userId, spaceId: $m->spaceId);
        }

        $onlineUserIds = array_map(
            callback: fn (User $user): int => $user->id,
            array: $this->presence->getOnlineUsers(),
        );

        return $this->view->render(template: 'space::space/show', data: [
            'space' => $space,
            'spaces' => $allSpaces,
            'membership' => $membership,
            'members' => $members,
            'messages' => $messages,
            'userMap' => $userMap,
            'unreadCounts' => $unreadCounts,
            'onlineUserIds' => $onlineUserIds,
            'currentUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->get(),
        ]);
    }

    /**
     * @throws AuthException
     */
    #[Post('/spaces/{slug}/join', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function join(
        string $slug,
    ): Response {
        $space = $this->spaceRepository->findBySlug(slug: $slug);

        if (!$space instanceof Space) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $userId = (int) $this->auth->user()->getAuthIdentifier();
        $existing = $this->spaceMembershipRepository->findByUserAndSpace(userId: $userId, spaceId: (int) $space->id);

        if (!$existing instanceof SpaceMembership) {
            $this->createMembership(userId: $userId, space: $space);
        }

        return Response::redirect(url: '/spaces/' . $slug);
    }

    /**
     * @throws AuthException
     */
    #[Post('/spaces/{slug}/leave', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]
    public function leave(
        string $slug,
    ): Response {
        $space = $this->spaceRepository->findBySlug(slug: $slug);

        if (!$space instanceof Space) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $userId = (int) $this->auth->user()->getAuthIdentifier();
        $membership = $this->spaceMembershipRepository->findByUserAndSpace(userId: $userId, spaceId: (int) $space->id);

        if ($membership instanceof SpaceMembership) {
            $this->spaceMembershipRepository->delete(entity: $membership);
        }

        return Response::redirect(url: '/home');
    }

    private function createMembership(
        int $userId,
        Space $space,
    ): SpaceMembership {
        $membership = new SpaceMembership(
            id: null,
            userId: $userId,
            spaceId: (int) $space->id,
            lastReadMessageId: null,
            joinedAt: new DateTimeImmutable(),
        );

        $this->spaceMembershipRepository->save(entity: $membership);

        return $membership;
    }
}
