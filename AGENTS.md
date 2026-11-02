# AGENTS.md

## Entrypoint
- Main plugin file: `nominal-ai-provider-for-opencode.php` (not `plugin.php`).
- Hooks into WordPress `init` at priority 5 to register `Nominal_AIPO_OpenCodeProvider` with `AiClient::defaultRegistry()`.

## Architecture
- Namespace: `Nominal\AIProviderOpenCode\` (capital "N" in "Nominal", capital "A", "I", "P", "O" in "AIProviderOpenCode").
- Three classes, all extending SDK `Abstract*` base classes for OpenAI-compatible behavior:
  - `Nominal_AIPO_OpenCodeProvider` → `AbstractApiProvider`
  - `Nominal_AIPO_OpenCodeModelMetadataDirectory` → `AbstractOpenAiCompatibleModelMetadataDirectory`
  - `Nominal_AIPO_OpenCodeTextGenerationModel` → `AbstractOpenAiCompatibleTextGenerationModel`
- Plugin classes live in `src/` and use the `Nominal_AIPO_` prefix. The only vendor SDK is Composer `vendor/wordpress/php-ai-client` (dev) and WordPress core at runtime.
- Production autoloading uses `spl_autoload_register` in `src/autoload.php`. The Composer autoloader is **only for dev tooling** and is stripped from builds.
- **Do not** add `require`/`include` calls for Composer's `vendor/autoload.php` — use the custom autoloader in production.

## Function Prefix
- `nominal_ai_po_` (e.g. `nominal_ai_po_register_provider`)
- Class prefix: `Nominal_AIPO_`
- Constant prefix: `NOMINAL_AIPO_`

## API Base URL
- `https://opencode.ai/zen/go/v1` — hardcoded in `Nominal_AIPO_OpenCodeProvider::baseUrl()`.

## SDK version gating
The provider checks `AiClient::VERSION` before using newer features:
- Provider description: requires SDK ≥ 1.2.0
- Provider logo path: requires SDK ≥ 1.3.0
- If neither gate is met, those metadata fields are omitted gracefully.

## Models
- Models are dynamically discovered from OpenCode's `/models` endpoint.
- Two models have explicit display-name mappings: `deepseek-v4-flash` and `deepseek-v4-pro`. All others get auto-formatted display names. Models sort alphabetically by ID.
- Every model returned from the API gets `textGeneration` + `chatHistory` capabilities, plus the `functionDeclarations` option.
- Text-generation requests send `User-Agent: Nominal-AI-Provider-OpenCode/<plugin version>`. Do not invent an `x-opencode-session` value. The PHP AI Client prompt has no stable session ID.

## Build for production
```bash
# First-time: composer install must already have run
./scripts/build.sh
```
- Strips dev dependencies via `composer install --no-dev`, copies files per `.distignore`, produces `nominal-ai-provider-for-opencode.zip`, then restores dev deps.
- Files excluded from build (`.distignore`): `AGENTS.md`, `CODING-STYLE.md`, `composer.json`, `composer.lock`, `vendor/`, `scripts/`, `.git/`, `.DS_Store`, `phpcs.xml.dist`, `phpmd.xml.dist`, `phpstan.neon.dist`.

## Lint
```bash
composer lint         # phpcs, phpmd, phpstan
composer phpcs        # phpcs only
composer phpcbf       # auto-fix phpcs issues
composer phpmd        # phpmd only
composer phpstan      # phpstan analyze --memory-limit=256M
```
- Requires `composer install` first (dev dependencies include phpcs, phpmd, and phpstan).
- Style overrides and the PhpStorm settings are in `CODING-STYLE.md`.

## Reference plugins (adjacent in filesystem)
- `../ai-provider-for-openai/` — official OpenAI provider (source of truth for conventions)
- `../ai-provider-for-google/` — Google provider
- `../ai-provider-for-deepseek/` — DeepSeek provider (structurally closest to this one)

## Code style
- WordPress coding standards, with the overrides in `CODING-STYLE.md`: character alignment, aligned PHPDoc, calls over 120 characters chopped to one argument per line.
- Tabs for indentation, space inside parens (`function ( $arg )`), `array()` over `[]`.
- All files declare `strict_types=1`.
- PHPDoc `@since 1.0.0` on every method. New methods use `@since 1.1.0`.
