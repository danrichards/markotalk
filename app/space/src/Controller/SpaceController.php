<?php

declare(strict_types=1);

namespace App\Space\Controller;

use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Security\Contracts\CsrfTokenManagerInterface;
use Marko\View\ViewInterface;

readonly class SpaceController
{
    public function __construct(
        private SpaceRepositoryInterface $spaces,
        private SpaceMembershipRepositoryInterface $memberships,
        private MessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private ViewInterface $view,
        private AuthManager $auth,
        private CsrfTokenManagerInterface $csrf,
    ) {}

    #[Get('/home', middleware: [AuthMiddleware::class])]
    public function index(
        Request $request,
    ): Response {
        $general = $this->spaces->findBySlug(slug: 'general');

        if ($general !== null) {
            return Response::redirect(url: '/spaces/general');
        }

        $activeSpaces = $this->spaces->findActive();

        if ($activeSpaces !== []) {
            return Response::redirect(url: '/spaces/' . $activeSpaces[0]->slug);
        }

        return Response::redirect(url: '/spaces');
    }

    #[Get('/spaces/{slug}', middleware: [AuthMiddleware::class])]
    public function show(
        string $slug,
        Request $request,
    ): Response {
        $space = $this->spaces->findBySlug(slug: $slug);

        if ($space === null) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $userId = (int) $this->auth->user()->getAuthIdentifier();
        $membership = $this->memberships->findByUserAndSpace(userId: $userId, spaceId: (int) $space->id);

        if ($membership === null) {
            $membership = $this->createMembership(userId: $userId, space: $space);
        }

        $messages = $this->messages->findBySpace(spaceId: (int) $space->id);

        if ($messages !== []) {
            $this->memberships->updateLastReadMessageId(
                membership: $membership,
                messageId: (int) $messages[array_key_last($messages)]->id,
            );
        }

        $allSpaces = $this->spaces->findActive();
        $spaceMemberships = $this->memberships->findAllForSpace(spaceId: (int) $space->id);
        $members = [];
        foreach ($spaceMemberships as $m) {
            $user = $this->users->find(id: $m->userId);
            if ($user !== null) {
                $members[] = $user;
            }
        }

        $userMap = [];
        foreach ($members as $member) {
            $userMap[$member->id] = $member->displayName ?: $member->username;
        }
        foreach ($messages as $message) {
            if (!isset($userMap[$message->userId])) {
                $user = $this->users->find(id: $message->userId);
                if ($user !== null) {
                    $userMap[$user->id] = $user->displayName ?: $user->username;
                }
            }
        }

        $userMemberships = $this->memberships->findAllForUser(userId: $userId);
        $unreadCounts = [];
        foreach ($userMemberships as $m) {
            $unreadCounts[$m->spaceId] = $this->memberships->countUnread(userId: $userId, spaceId: $m->spaceId);
        }

        return $this->view->render(template: 'space::space/show', data: [
            'space' => $space,
            'spaces' => $allSpaces,
            'membership' => $membership,
            'members' => $members,
            'messages' => $messages,
            'userMap' => $userMap,
            'unreadCounts' => $unreadCounts,
            'currentUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->get(),
        ]);
    }

    #[Post('/spaces/{slug}/join', middleware: [AuthMiddleware::class])]
    public function join(
        string $slug,
        Request $request,
    ): Response {
        $space = $this->spaces->findBySlug(slug: $slug);

        if ($space === null) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $userId = (int) $this->auth->user()->getAuthIdentifier();
        $existing = $this->memberships->findByUserAndSpace(userId: $userId, spaceId: (int) $space->id);

        if ($existing === null) {
            $this->createMembership(userId: $userId, space: $space);
        }

        return Response::redirect(url: '/spaces/' . $slug);
    }

    #[Post('/spaces/{slug}/leave', middleware: [AuthMiddleware::class])]
    public function leave(
        string $slug,
        Request $request,
    ): Response {
        $space = $this->spaces->findBySlug(slug: $slug);

        if ($space === null) {
            return new Response(body: 'Not Found', statusCode: 404);
        }

        $userId = (int) $this->auth->user()->getAuthIdentifier();
        $membership = $this->memberships->findByUserAndSpace(userId: $userId, spaceId: (int) $space->id);

        if ($membership !== null) {
            $this->memberships->delete(entity: $membership);
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
        $this->memberships->save(entity: $membership);

        return $membership;
    }
}
