<?php

declare(strict_types=1);

namespace App\Space\Controller;

use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use DateTimeImmutable;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

readonly class SpaceController
{
    public function __construct(
        private SpaceRepositoryInterface $spaces,
        private SpaceMembershipRepositoryInterface $memberships,
        private MessageRepositoryInterface $messages,
        private ViewInterface $view,
        private AuthManager $auth,
    ) {}

    #[Get('/home', middleware: [AuthMiddleware::class])]
    public function index(
        Request $request,
    ): Response {
        $userId = (int) $this->auth->user()->getAuthIdentifier();
        $userMemberships = $this->memberships->findAllForUser(userId: $userId);

        if ($userMemberships !== []) {
            $spaceId = $userMemberships[0]->spaceId;

            foreach ($this->spaces->findActive() as $space) {
                if ($space->id === $spaceId) {
                    return Response::redirect(url: '/spaces/' . $space->slug);
                }
            }
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

        $latestMessages = $this->messages->findBySpace(spaceId: (int) $space->id, limit: 1);

        if ($latestMessages !== []) {
            $this->memberships->updateLastReadMessageId(
                membership: $membership,
                messageId: (int) $latestMessages[0]->id,
            );
        }

        return $this->view->render(template: 'space::show', data: ['space' => $space, 'membership' => $membership]);
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
