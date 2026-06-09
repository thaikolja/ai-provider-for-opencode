# Contributing

Thanks for your interest in contributing to the Nominal AI Provider for OpenCode.

## Getting Started

1. Clone the repository
2. Run `composer install` to install dev dependencies (PHPCS, PHPStan)
3. Read [`AGENTS.md`](AGENTS.md) for architecture and conventions

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

This project follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- Tabs for indentation
- Space inside parentheses: `function ( $arg )`
- `array()` over `[]` where possible
- All files declare `strict_types=1`
- PHPDoc `@since 1.0.0` on every method

Run `composer phpcs` to check compliance and `composer phpcbf` to auto-fix issues.

## Pull Requests

1. Create a feature branch from `main`
2. Make your changes
3. Run `composer lint` and ensure it passes
4. Open a pull request against `main`

## Reporting Issues

Use the [GitHub Issues](https://github.com/thaikolja/nominal-ai-provider-for-opencode/issues) page to report bugs or request features.

## License

By contributing, you agree that your contributions will be licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).
