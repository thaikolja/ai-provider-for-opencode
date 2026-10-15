# AGENTS.md

## Entrypoint
- Main plugin file: `nominal-ai-provider-for-opencode.php` (not `plugin.php`).
- Hooks into WordPress `init` at priority 5 to register `Nominal_AIPO_OpenCodeProvider` with `AiClient::defaultRegistry()`.

## Architecture
- Namespace: `Nominal\AIProviderOpenCode\` (capital "N" in "Nominal", capital "A", "I", "P", "O" in "AIProviderOpenCode").
- Four classes. Three extend SDK `Abstract*` base classes for OpenAI-compatible behavior; the fourth is a plain helper:
  - `Nominal_AIPO_OpenCodeProvider` → `AbstractApiProvider`
  - `Nominal_AIPO_OpenCodeModelMetadataDirectory` → `AbstractOpenAiCompatibleModelMetadataDirectory`
  - `Nominal_AIPO_OpenCodeTextGenerationModel` → `AbstractOpenAiCompatibleTextGenerationModel`
  - `Nominal_AIPO_OpenCodeImageConverter` (in `src/Support/`) → standalone image re-encoding helper
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
- `formatDisplayName()` has explicit display-name mappings for the known catalogue; anything else gets an auto-formatted name. Keep the map in sync as models come and go.
- Every model returned from the API gets `textGeneration` + `chatHistory` capabilities.

## Vision (image input)
- OpenCode's `/models` endpoint does **not** return capabilities, so vision support is a hardcoded allowlist: `Nominal_AIPO_OpenCodeModelMetadataDirectory::VISION_MODEL_IDS`.
- Vision-capable models declare `inputModalities` as `[[text], [text, image]]`; everything else stays `[[text]]`.
- Only the **image** modality is declared, because the SDK's OpenAI-compatible text generation model can only serialise image (and inline audio) file parts — declaring `document`/`video` would produce runtime errors.
- The list can be extended at runtime via the `nominal_ai_po_opencode_vision_models` filter.

## Image conversion
- OpenCode's upstream providers only accept **webp, png, jpeg, gif** images; anything else (BMP, TIFF, HEIC, SVG, or a wrong MIME label) is rejected with a 400.
- `Nominal_AIPO_OpenCodeTextGenerationModel::getMessagePartContentData()` overrides the SDK method to rewrite unsupported image parts before the request is sent.
- `Nominal_AIPO_OpenCodeImageConverter` sniffs the real format from the bytes and, when needed, re-encodes via **GD** (no Imagick dependency). Remote images with an unsupported MIME are downloaded first (WP HTTP API, `file_get_contents()` fallback) and then re-encoded inline.
- Target format defaults to webp, falling back to the first GD-encodable format; override with the `nominal_ai_po_opencode_image_conversion_mime_type` filter.
- GD cannot decode SVG/TIFF/HEIC, so those still fail upstream — a known limitation.
- GD + WP HTTP functions used here are stubbed in `wp-stubs.php` for PHPStan.

## Request headers / session ID
- Every request merges in `Nominal_AIPO_OpenCodeProvider::requestHeaders()`, which currently sends `x-opencode-session` (required by OpenCode Go for routing/prompt-caching).
- Both `createRequest()` implementations (model + metadata directory) must merge those headers.
- `Nominal_AIPO_OpenCodeProvider::sessionId()` resolves the value: the `OPENCODE_SESSION_ID` constant, then the `nominal_ai_po_opencode_session_id` filter, then a random per-PHP-request ID. The result is cached statically.

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
