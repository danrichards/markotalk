```
██      ██   ██████   ██████    ██   ██   ██████   ██████   ██████   ██       ██   ██
████  ████  ██    ██  ██   ██   ██  ██   ██    ██    ██    ██    ██  ██       ██  ██
██ ████ ██  ████████  ██████    █████    ██    ██    ██    ████████  ██       █████
██  ██  ██  ██    ██  ██   ██   ██  ██   ██    ██    ██    ██    ██  ██       ██  ██
██      ██  ██    ██  ██    ██  ██   ██   ██████     ██    ██    ██  ███████  ██   ██
```

**Real-time community chat built with Marko.**

Dogfooding the [Marko PHP Framework](https://github.com/marko-php/marko) for its own developer community, showcasing plugins, preferences, events, and modules in a real application.

[![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-7A86B8?style=flat-square)](https://www.php.net/releases/8.5/en.php)
[![License: MIT](https://img.shields.io/badge/License-MIT-E8B931?style=flat-square)](LICENSE)
[![Active Development](https://img.shields.io/badge/Status-Active%20Development-00C853?style=flat-square)](#status)

---

## Features

- **Spaces** — Organized chat channels with membership and permissions
- **Real-time messaging** — Server-Sent Events powered by PostgreSQL LISTEN/NOTIFY
- **Markdown** — Bold, italic, code blocks, and links in messages
- **Reactions** — Emoji reactions on messages
- **Pinning** — Pin important messages to the top
- **Rate limiting** — Configurable per-user message throttling
- **Admin panel** — User and space management

## Quick Start

### Prerequisites

- PHP 8.5+
- Composer 2.x
- Docker (for PostgreSQL)

### Setup

```bash
# Clone the repository
git clone https://github.com/devtomic/markotalk.git
cd markotalk

# Start PostgreSQL
docker compose up -d

# Install dependencies
composer install

# Configure environment
cp .env.example .env
```

Update `.env` with the database settings from `compose.yaml`:

```
APP_ENV=development
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=markotalk
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

Run migrations and start the dev server:

```bash
# Run database migrations
php marko migrate

# Start the development server
PHP_CLI_SERVER_WORKERS=4 php -S localhost:8000 -t public
```

> **Note:** `PHP_CLI_SERVER_WORKERS=4` is required because MarkoTalk uses SSE for real-time messaging. Without multiple workers, the SSE connection blocks all other requests on the single-threaded PHP built-in server.

To promote a user to admin (for pinning messages, moderation, etc.):

```bash
docker compose exec db psql -U postgres markotalk -c \
  "UPDATE users SET role = 'admin' WHERE username = 'myusername';"
```

Visit [http://localhost:8000](http://localhost:8000) to get started.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | [Marko PHP Framework](https://github.com/marko-php/marko) |
| Database | PostgreSQL 17 |
| Templates | Latte 3.0 |
| Real-Time | SSE + PostgreSQL LISTEN/NOTIFY |
| Testing | Pest PHP |
| Styling | Tailwind CSS |
| Client-Side | Vanilla JavaScript |

## Project Structure

```
app/                  # Application modules
├── admin/            # Admin panel
├── message/          # Messaging (send, edit, delete, reactions, SSE)
├── notification/     # User notifications
├── space/            # Spaces (channels, memberships)
└── user/             # Authentication, profiles, presence
config/               # PHP configuration files
database/migrations/  # Sequential migration files
public/               # Entry point, CSS, JS, storage
resources/views/      # Latte templates
```

## Commands

```bash
# Run tests (parallel)
./vendor/bin/pest --parallel

# Run tests (sequential)
./vendor/bin/pest

# Lint check
./vendor/bin/phpcs --standard=phpcs.xml

# Lint fix
./vendor/bin/php-cs-fixer fix && ./vendor/bin/phpcbf

# Static analysis
./vendor/bin/rector process
```

## Status

MarkoTalk is in **active development** alongside the Marko framework. It serves as both a real application and a reference implementation for Marko's module system.

## License

MarkoTalk is open-source software licensed under the [MIT License](LICENSE).
