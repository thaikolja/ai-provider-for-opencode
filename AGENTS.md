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
- Production autoloading uses `spl_autoload_register` in `src/autoload.php`. The Composer autoloader is **only for dev tooling** and is stripped from builds.
- **Do not** add `require`/`include` calls for Composer's `vendor/autoload.php` — use the custom autoloader in production.

## Function Prefix
- `nominal_ai_po_` (e.g. `nominal_ai_po_register_provider`)
- Class prefix: `Nominal_AIPO_`
- Constant prefix: `NOMINAL_AIPO_`

## API Base URL
- `https://opencode.ai/inference/go/v1` — hardcoded in `Nominal_AIPO_OpenCodeProvider::baseUrl()`.

## SDK version gating
The provider checks `AiClient::VERSION` before using newer features:
- Provider description: requires SDK ≥ 1.2.0
- Provider logo path: requires SDK ≥ 1.3.0
- If neither gate is met, those metadata fields are omitted gracefully.

## Models
- Models are dynamically discovered from OpenCode's `/models` endpoint.
- Two models have explicit display-name mappings: `deepseek-v4-flash` and `deepseek-v4-pro`. All others get auto-formatted display names.
- Every model returned from the API gets `textGeneration` + `chatHistory` capabilities.

## Request headers / session ID
- Every request merges in `Nominal_AIPO_OpenCodeProvider::requestHeaders()`, which sends `x-opencode-session` (required by OpenCode Go for routing/prompt-caching).
- The value is a random ID generated once per PHP request; the provider serves one-off requests, so it is not externally configurable.
- Both `createRequest()` implementations (model + metadata directory) must merge those headers.

## Build for production
```bash
# First-time: composer install must already have run
./scripts/build.sh
```
- Strips dev dependencies via `composer install --no-dev`, copies files per `.distignore`, produces `nominal-ai-provider-for-opencode.zip`, then restores dev deps.
- Files excluded from build (`.distignore`): `AGENTS.md`, `composer.json`, `composer.lock`, `vendor/`, `scripts/`, `.git/`, `.DS_Store`, `phpcs.xml.dist`, `phpstan.neon.dist`.

## Lint
```bash
composer lint         # runs phpcs + phpstan
composer phpcs        # phpcs only
composer phpstan      # phpstan analyze --memory-limit=256M
```
- Requires `composer install` first (dev dependencies include phpcs and phpstan).

## Reference plugins (adjacent in filesystem)
- `../ai-provider-for-openai/` — official OpenAI provider (source of truth for conventions)
- `../ai-provider-for-google/` — Google provider
- `../ai-provider-for-deepseek/` — DeepSeek provider (structurally closest to this one)

## Code style
- WordPress coding standards: tabs for indentation, space inside parens (`function ( $arg )`), `array()` over `[]` where possible.
- All files declare `strict_types=1`.
- PHPDoc `@since 1.0.0` on every method.
