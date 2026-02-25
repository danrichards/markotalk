# Architecture

## Directory Structure
```
markotalk/
├── app/                        # Application modules (Marko modules)
│   ├── user/                   # User registration, login, profiles
│   │   ├── composer.json       # Module manifest (extra.marko.module: true)
│   │   ├── module.php          # Module boot config (bindings, etc.)
│   │   ├── src/
│   │   │   ├── Controller/     # AuthController, ProfileController
│   │   │   ├── css/            # auth.css, user.css
│   │   │   ├── Entity/         # User entity
│   │   │   ├── Observer/       # WelcomeMessageObserver
│   │   │   ├── Repository/     # UserRepository
│   │   │   ├── Policy/         # UserPolicy
│   │   │   └── Provider/       # DatabaseUserProvider
│   │   └── tests/
│   │       ├── Unit/
│   │       └── Feature/
│   │
│   ├── space/                  # Spaces (rooms/channels)
│   │   ├── src/
│   │   │   ├── Controller/     # SpaceController
│   │   │   ├── css/            # space.css
│   │   │   ├── Entity/         # Space, SpaceMembership
│   │   │   ├── Repository/     # SpaceRepository
│   │   │   └── Policy/         # SpacePolicy
│   │   └── tests/
│   │
│   ├── message/                # Messages and real-time streaming
│   │   ├── src/
│   │   │   ├── Controller/     # MessageController, StreamController
│   │   │   ├── css/            # message.css
│   │   │   ├── Entity/         # Message, Reaction
│   │   │   ├── Repository/     # MessageRepository
│   │   │   ├── Plugin/         # MarkdownPlugin, MentionExtractorPlugin
│   │   │   ├── Observer/       # MentionNotificationObserver
│   │   │   └── Policy/         # MessagePolicy
│   │   └── tests/
│   │
│   ├── notification/           # In-app notification UI and tracking
│   │   ├── src/
│   │   │   ├── Controller/     # NotificationController
│   │   │   ├── css/            # notification.css
│   │   │   ├── Notification/   # MentionNotification, WelcomeNotification
│   │   │   └── Observer/       # NotificationStreamObserver
│   │   └── tests/
│   │
│   └── admin/                  # Admin panel for space/user management
│       ├── src/
│       │   ├── Controller/     # AdminSpaceController, AdminUserController
│       │   ├── css/            # admin.css
│       │   └── Middleware/     # AdminMiddleware
│       └── tests/
│
├── config/
│   ├── database.php            # Database connection config
│   └── markotalk.php           # App-level config (message length, presence timeout, etc.)
│
├── database/
│   └── migrations/             # Sequential migration files (001_, 002_, etc.)
│
├── modules/                    # Reserved for third-party non-Composer modules
│
├── public/
│   ├── index.php               # Bootstrap entry point
│   ├── css/app.css             # Styles
│   ├── js/app.js               # Vanilla JS (EventSource, DOM updates)
│   └── storage/
│       ├── logs/
│       └── sessions/
│
├── resources/
│   └── views/                  # Latte templates
│       ├── layout.latte        # Base layout (sidebar + main area)
│       └── landing.latte       # Public landing page
│
├── src/
│   ├── Controller/             # App-level controllers (LandingController)
│   └── css/                    # CSS entry point and app-level styles
│       ├── app.css             # Entry point — imports all layers
│       ├── base.css            # Resets, typography, root variables
│       ├── components.css      # Reusable UI: buttons, inputs, badges, avatars
│       ├── landing.css         # Landing page styles
│       └── layout.css          # App shell: sidebar, main area, header, footer
│
├── composer.json               # Root project config with path repos to ../marko/packages/*
├── .env                        # Environment variables
└── .env.example                # Environment template
```

## Module Pattern

Each app module follows the Marko module structure:
- `composer.json` with `"extra": { "marko": { "module": true } }` and PSR-4 autoload
- Optional `module.php` for bindings, boot callbacks, and module config
- `src/` contains the module code organized by concern (Controller, Entity, Repository, etc.)
- `tests/` mirrors the src structure with Unit/ and Feature/ subdirectories
- Namespace: `App\{ModuleName}\` (e.g., `App\User\`, `App\Message\`)

## Routes

| Method | Path | Controller | Description |
|--------|------|------------|-------------|
| GET | `/` | `App\Controller\LandingController` | Public landing page; redirects to `/home` if authenticated |
| GET | `/home` | `App\Space\Controller\SpaceController` | Authenticated home — redirects to most recent space |
| GET | `/login` | `App\User\Controller\AuthController` | Login form |
| POST | `/login` | `App\User\Controller\AuthController` | Process login |
| POST | `/logout` | `App\User\Controller\AuthController` | Log out |
| GET | `/register` | `App\User\Controller\AuthController` | Registration form |
| POST | `/register` | `App\User\Controller\AuthController` | Process registration |
| GET | `/spaces/{slug}` | `App\Space\Controller\SpaceController` | View a space (chat room) |
| POST | `/spaces` | `App\Space\Controller\SpaceController` | Create a space |
| GET | `/spaces/{slug}/stream` | `App\Message\Controller\StreamController` | SSE stream for a space |
| POST | `/spaces/{slug}/messages` | `App\Message\Controller\MessageController` | Send a message |
| GET | `/notifications` | `App\Notification\Controller\NotificationController` | Notification list |
| GET | `/profile` | `App\User\Controller\ProfileController` | Edit profile |
| POST | `/profile` | `App\User\Controller\ProfileController` | Update profile |
| GET | `/admin` | `App\Admin\Controller\AdminSpaceController` | Admin panel |

## Patterns Used
- **Repository pattern**: Data access for each entity (UserRepository, MessageRepository, etc.)
- **Plugin system**: Before/After method interception (MarkdownPlugin, MentionExtractorPlugin)
- **Event/Observer**: Decoupled event handling (message.created, user.registered, mention.detected)
- **Preferences**: Swappable implementations (markdown parser, presence tracker)
- **Policies**: Authorization per module (MessagePolicy, SpacePolicy, UserPolicy)
- **Constructor injection**: All dependencies via DI container

## Real-Time Architecture (SSE)
1. User sends message via POST
2. MarkdownPlugin processes body before save
3. Message saved to database
4. MentionExtractorPlugin extracts @mentions after save
5. Events dispatched (message.created, mention.detected)
6. Other users' SSE connections poll for new messages (id > lastEventId)
7. New messages flushed as SSE frames to connected clients
8. Client JS receives events and updates DOM

## Key Integrations
- **Authentication**: Session-based via `marko/authentication` + `marko/session-file`
- **Authorization**: Policy-based via `marko/authorization`
- **SSE Streaming**: Via `marko/sse` (SseStream, SseEvent)
- **Notifications**: Via `marko/notification` + `marko/notification-database`
- **Pagination**: Cursor-based via `marko/pagination` for message history

## Build Order
See `~/Sites/markotalk.md` for the full 6-phase build order:
1. Foundation (skeleton, user module, layout)
2. Core Chat (spaces, messages, pagination)
3. Real-Time (SSE, EventSource, presence)
4. Plugins & Events (markdown, mentions, observers)
5. Notifications & Polish (UI, unread counts, reactions, profiles)
6. Admin (middleware, moderation, user management)
