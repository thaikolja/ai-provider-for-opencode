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

- `https://opencode.ai/inference/go/v1` — hardcoded in `Nominal_AIPO_OpenCodeProvider::baseUrl()`.

## SDK version gating

The provider checks `AiClient::VERSION` before using newer features:

- Provider description: requires SDK ≥ 1.2.0
- Provider logo path: requires SDK ≥ 1.3.0
- If neither gate is met, those metadata fields are omitted gracefully.

## Models

- Models are dynamically discovered from OpenCode's `/models` endpoint.
- Display names come from the model list `name` field. If that field is missing, `deepseek-v4-pro`, `deepseek-v4-flash`, and `deepseek-v4.1-flash` have explicit fallbacks. Every other ID is auto-formatted. Models sort alphabetically by ID.
- Every model returned from the API gets `textGeneration` + `chatHistory` capabilities, plus the `functionDeclarations` option.
- Every request sends `User-Agent: Nominal-AI-Provider-OpenCode/<plugin version>` unless the caller already set one.
- Every request merges in `Nominal_AIPO_OpenCodeProvider::requestHeaders()`, which sends `x-opencode-session` (required by OpenCode Go).
- The value is a random ID generated once per PHP request. Both `createRequest()` implementations must merge those headers. It is not externally configurable.
- Text generation uses the caller timeout when one is set. An empty timeout becomes 120 seconds, then passes through `nominal_aipo_opencode_request_timeout` with the model ID. A non-numeric or negative filter return is ignored. The stored request options object is not mutated. Model listing does not set a timeout.

## Build for production

```bash
# First-time: composer install must already have run
./scripts/build.sh
```

- Strips dev dependencies via `composer install --no-dev`, copies files per `.distignore`, produces `nominal-ai-provider-for-opencode.zip`, then restores dev deps.
- Files excluded from build (`.distignore`): `AGENTS.md`, `composer.json`, `composer.lock`, `vendor/`, `scripts/`, `.git/`, `.DS_Store`, `phpcs.xml.dist`, `phpmd.xml.dist`, `phpstan.neon.dist`.

## Lint

```bash
composer lint         # phpcs, phpmd, phpstan
composer phpcs        # phpcs only
composer phpcbf       # auto-fix phpcs issues
composer phpmd        # phpmd only
composer phpstan      # phpstan analyze --memory-limit=256M
```

- Requires `composer install` first (dev dependencies include phpcs, phpmd, and phpstan).
- Style overrides are in `phpcs.xml.dist`. PHPMD rules are in `phpmd.xml.dist`. PHPStan is level 5.

## Reference plugins (adjacent in filesystem)

- `../ai-provider-for-openai/` — official OpenAI provider (source of truth for conventions)
- `../ai-provider-for-google/` — Google provider
- `../ai-provider-for-deepseek/` — DeepSeek provider (structurally closest to this one)

## Code style

- WordPress coding standards, with the overrides in `phpcs.xml.dist`: character alignment, aligned PHPDoc, calls over 120 characters chopped to one argument per line.
- Tabs for indentation, space inside parens (`function ( $arg )`), `array()` over `[]`.
- All files declare `strict_types=1`.
- PHPDoc `@since` on every method uses the version that introduced it. The current plugin version is 1.1.2.

## Release

- This directory is the development tree. `origin` is `https://github.com/thaikolja/ai-provider-for-opencode.git` on `main`. Pushing GitHub does not publish WordPress.org.
- WordPress.org SVN is `https://plugins.svn.wordpress.org/nominal-ai-provider-for-opencode/`. Trunk and `tags/1.1.2` are version 1.1.2, committed as `thaikolja` in r3818573. Tag `1.0.0` stays.
- Plugin header, `USER_AGENT`, readme stable tag, and `CHANGELOG.md` are 1.1.2. The next code change bumps all of those together. Do not open a new SVN release unless Kolja asks.
- The public plugin page and `https://api.wordpress.org/plugins/info/1.2/` can lag SVN. Read `trunk/readme.txt` with `svn cat` before treating an old directory page as a failed release.
- Do not force-push `main`.
- `CONTRIBUTING.md` says the buildable source lives on GitLab. Leave that note unless Kolja edits it.

## Directory assets

- WordPress.org banners and icons live in SVN `assets/`, outside the plugin zip.
- Required sizes: `banner-1544x500.png` 1544×500, `banner-772x250.png` 772×250, `icon-256x256.png` 256×256, `icon-128x128.png` 128×128.
- The banner wordmark is vector art under a luminance-mask PNG. ImageMagick paints that mask as a white bar. r3818617 replaced both banner PNGs with browser renders of `assets/images/banner-1544x500.svg`. Regenerate that way, or scale a known-good PNG. Do not upload the annotated sheets in `.wordpress-org/assets/images/`.

## Boundaries

- The only public request filter is `nominal_aipo_opencode_request_timeout`. Do not add a headers filter, a base-URL filter, `OPENCODE_SESSION_ID`, or `nominal_ai_po_opencode_session_id`.
- Do not define `OPENCODE_API_KEY` in the plugin. The site sets it. Admin model dropdowns stay empty without a key OpenCode accepts, even though `GET /models` is public when the User-Agent is set.
- `src/autoload.php` exits when `ABSPATH` is missing. The main file returns. Class files must not exit, because Composer loads them.
- Keep `load_plugin_textdomain()` on `init` priority 5. Plugin Check warns. Bundled translations need that call.
- Requires PHP stays 7.4. Do not copy the WordPress version into that header. No typed class constants.
- `../ai-provider-for-opencode` still targets OpenCode Zen. Do not copy that checkout over this tree.

## Comments

In Kolja's plugin PHP (`nominal-ai-provider-for-opencode.php` and `src/`, not `vendor/`, `languages/`, or `wp-stubs.php`):

- Put a `//` comment above every statement. Full sentence, capital letter, period.
- Leave a blank line before the next statement. No blank line before a closing brace.
- Keep PHPDoc directly above the function, class, or constant.
- Keep the hook docblock directly above `apply_filters`, with a `//` comment above that docblock.
- Keep `phpcs:ignore` on the line directly above `throw`.

User-facing readme text stays plain. The endpoint, session header, User-Agent, and timeout filter stay in External services, PHPDoc, this file, and the short README configuration sentence.
