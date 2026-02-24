# Code Standards

## Style Guide
Follows the Marko Framework code standards (see `~/Sites/marko/.claude/code-standards.md` for full details).

## Linting
```bash
# Check for issues
./vendor/bin/phpcs --standard=phpcs.xml

# Auto-fix issues
./vendor/bin/php-cs-fixer fix && ./vendor/bin/phpcbf

# Static analysis
./vendor/bin/rector process
```

## Pre-commit Checks
- Rector -> fix-multiline-params -> PHP-CS-Fixer -> PHPCBF -> PHPCS
- All tests must pass
- Coverage >= 80%

## PHP Code Standards

| Rule | Requirement |
|------|-------------|
| Strict Types | `declare(strict_types=1);` on every file |
| Type Declarations | Required on all parameters, returns, properties |
| Constructor Injection | Only DI method; no service locator |
| Readonly | Use when all properties are immutable (`readonly class`) |
| No Final Classes | Blocks Preference-based extension |
| No Magic Methods | `__get`, `__set`, `__call` forbidden |
| No Traits | Use composition instead |
| Attributes | Single mechanism for code metadata |
| Named Parameters | All function calls prefer named syntax |

## Naming Conventions
- Classes: `PascalCase`
- Methods: `camelCase`
- Variables: `camelCase`
- Constants: `SCREAMING_SNAKE_CASE`
- Files: Match class name (`UserRepository.php`)
- Namespaces: `App\{Module}\{Subdirectory}`
- Modules: lowercase singular (`user`, `space`, `message`, `notification`, `admin`)

## Formatting Rules
- Constructor property promotion
- Multiline method signatures (2+ params)
- Trailing commas in multiline constructs
- Import organization and alphabetization
- PSR-12 base formatting

## Exception Handling
- All exceptions extend `MarkoException`
- Three named parameters: `message`, `context`, `suggestion`
- Static factory methods for common cases
- All thrown exceptions must have `@throws` PHPDoc

## Configuration
- PHP files only (no YAML, XML, DSL)
- Environment variables ONLY in config files
- No fallback parameters on config getters
