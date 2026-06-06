# AGENTS.md

## Entrypoint
- Main plugin file: `ai-provider-for-opencode.php` (not `plugin.php`).
- Hooks into WordPress `init` at priority 5 to register `OpenCodeProvider` with `AiClient::defaultRegistry()`.

## Architecture
- Namespace: `WordPress\OpenCodeAiProvider\` (note the capital "C" in "OpenCode").
- All three classes extend the SDK's `Abstract*` base classes to get OpenAI-compatible behavior for free:
  - `OpenCodeProvider` → `AbstractApiProvider`
  - `OpenCodeModelMetadataDirectory` → `AbstractOpenAiCompatibleModelMetadataDirectory`
  - `OpenCodeTextGenerationModel` → `AbstractOpenAiCompatibleTextGenerationModel`
- Production autoloading uses a custom `spl_autoload_register` in `src/autoload.php` (Composer's `vendor/` is excluded from builds). The Composer autoloader is only for dev tooling.

## API Base URL
- `https://opencode.ai/zen/go/v1` — hardcoded in `OpenCodeProvider::baseUrl()`.

## SDK version gating
The provider checks `AiClient::VERSION` before using newer API features:
- Provider description: requires SDK ≥ 1.2.0
- Provider logo path: requires SDK ≥ 1.3.0
- Currently installed SDK: `0.4.3` — so neither gate is active during local dev.

## Models
- Only two models are explicitly mapped with display names: `deepseek-v4-flash` and `deepseek-v4-pro`.
- All models returned from OpenCode's `/models` endpoint get text-generation + chat-history capabilities.

## Lint
```bash
composer lint         # runs phpcs + phpstan
composer phpcs        # phpcs only
composer phpstan      # phpstan analyze --memory-limit=256M
```

## Build for production
```bash
# First-time setup: composer install must already have run
./scripts/build.sh
```
- Strips dev dependencies, copies files per `.distignore`, produces `ai-provider-for-opencode.zip`, then restores dev deps.
- `.distignore` excludes: `AGENTS.md`, `composer.json`, `composer.lock`, `vendor/`, `scripts/`, `.git/`, `.DS_Store`, `phpcs.xml.dist`, `phpstan.neon.dist`.

## Reference plugins (adjacent in filesystem)
- `../ai-provider-for-openai/` — official OpenAI provider (source of truth for conventions)
- `../ai-provider-for-google/` — Google provider
- `../ai-provider-for-deepseek/` — DeepSeek provider (structurally closest to this one)

## Code style
- WordPress coding standards: tabs for indentation, space inside parens (`function ( $arg )`), `array()` over `[]` where possible.
- All files declare `strict_types=1`.
- PHPDoc `@since 1.0.0` on every method.
