# Contributing

Thanks for your interest in contributing to the Nominal AI Provider for OpenCode.

## Getting Started

1. Clone the repository
2. Run `composer install` to install dev dependencies (PHPCS, PHPMD, PHPStan)
3. Read [`AGENTS.md`](AGENTS.md) for architecture and [`CODING-STYLE.md`](CODING-STYLE.md) for the code style

## Development Workflow

```bash
# Lint your changes
composer lint

# Auto-fix coding standards issues
composer phpcbf

# Build a production zip
./scripts/build.sh
```

## Code Style

This project follows the [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), with a few overrides (column alignment, aligned PHPDoc, long calls chopped at 120 characters). See [`CODING-STYLE.md`](CODING-STYLE.md) for the rules and the PhpStorm settings.

Run `composer phpcs` to check, `composer phpcbf` to auto-fix, and `composer phpmd` for PHPMD. `composer lint` runs phpcs, phpmd, and phpstan.

## Pull Requests

1. Create a feature branch from `main`
2. Make your changes
3. Run `composer lint` and ensure it passes
4. Open a pull request against `main`

## Reporting Issues

Use the [GitHub Issues](https://github.com/thaikolja/nominal-ai-provider-for-opencode/issues) page to report bugs or request features.

## License

By contributing, you agree that your contributions will be licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).
