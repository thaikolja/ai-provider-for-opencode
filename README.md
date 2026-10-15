# Nominal AI Provider for OpenCode

[![Stable Version](https://img.shields.io/badge/stable-1.1.0-blue)](https://github.com/thaikolja/nominal-ai-provider-for-opencode/releases) [![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-purple)](https://www.php.net/supported-versions.php) [![WordPress Version](https://img.shields.io/badge/wordpress-%3E%3D6.9-blue)](https://wordpress.org/download/) [![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)](https://www.gnu.org/licenses/gpl-2.0.html) [![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/nominal-ai-provider-for-opencode)](https://wordpress.org/plugins/nominal-ai-provider-for-opencode/) [![Packagist Version](https://img.shields.io/packagist/v/thaikolja/nominal-ai-provider-for-opencode)](https://packagist.org/packages/thaikolja/nominal-ai-provider-for-opencode)

This plugin is a Nominal AI Provider for [OpenCode](https://opencode.ai/) for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

**Note:** You are viewing the the *development* repository. You *can* clone or download this entire repository and use it as a plugin, but for safety and speed, you should use the built version from WordPress.org.

## Description

This plugin integrates OpenCode's AI models into WordPress as a provider for the PHP AI Client SDK. Once activated, OpenCode is automatically registered as a provider – no manual configuration required.

OpenCode provides access to a variety of open-source models through a unified API, enabling text generation, chat history, and image understanding for multimodal models within WordPress sites.

## Features

- **Text Generation** — Use OpenCode's language models for content creation, summarization, analysis, and more.
- **Vision** — Send images to OpenCode's multimodal models (Claude Haiku 5.5, Gemini 4 Argon, GPT 6 Luna, Grok 4.7, Kimi K3, Qwen3.8, MiMo, MiniMax M3, Muse Spark, and others).
- **Automatic image conversion** — Images in formats OpenCode rejects (BMP, TIFF, HEIC, SVG, or a wrong MIME label) are re-encoded on the fly to webp/png/jpeg/gif before the request is sent, so they don't fail with a 400.
- **Chat History** — Maintain conversation context across multiple interactions.
- **OpenCode Go Session Header** — Every request carries the `x-opencode-session` header so OpenCode Go can optimize routing and prompt caching.
- **Dynamic Model Discovery** — Available models are fetched directly from the OpenCode API, so your plugin stays compatible with new releases automatically.
- **Automatic Provider Registration** — Registers itself with the PHP AI Client on WordPress `init`; no manual wiring needed.

## Requirements

- PHP 7.4 or higher
- For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
- For WordPress 7.0 and above, no additional changes are required
- OpenCode API key

## Installation

### As a WordPress Plugin

1. Download the latest release from the plugin's WordPress page
2. Upload to `/wp-content/plugins/nominal-ai-provider-for-opencode/`
3. Activate the plugin through the WordPress admin
4. Configure your OpenCode API key (see [Configuration](#configuration))

### As a Composer Package

```bash
composer require thaikolja/nominal-ai-provider-for-opencode
```

Then register the provider manually:

```php
use WordPress\AiClient\AiClient;
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;

$registry = AiClient::defaultRegistry();
$registry->registerProvider( Nominal_AIPO_OpenCodeProvider::class );
```

## Usage

### With WordPress

1. **Install and activate the plugin, and add your API key – that's it!**

You can use the provider in your WordPress theme or plugin. It automatically registers itself on the `init` hook. Simply ensure the plugin is active and configure your API key either on your *Providers* page or via a constant: 

```php
// Set your OpenCode API key (or use the OPENCODE_API_KEY environment variable)
putenv( 'OPENCODE_API_KEY=your-api-key' );

// Use the provider
$result = AiClient::prompt( 'Hello, world!' )
	->usingProvider( 'opencode' )
	->generateTextResult();

echo $result->toText();
```

### As a Standalone Package

```php
use WordPress\AiClient\AiClient;
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;

// Register the provider
$registry = AiClient::defaultRegistry();
$registry->registerProvider( Nominal_AIPO_OpenCodeProvider::class );

// Set your API key
putenv( 'OPENCODE_API_KEY=your-api-key' );

// Generate text
$result = AiClient::prompt( 'Explain quantum computing' )
	->usingProvider( 'opencode' )
	->generateTextResult();

echo $result->toText();
```

## Supported Models

Available models are dynamically discovered from the OpenCode API at `https://opencode.ai/inference/go/v1/models`. The catalogue changes as OpenCode adds and retires models; current examples include:

- **DeepSeek V4 Pro** (`deepseek-v4-pro`) — High-quality text generation
- **DeepSeek V4.1 Flash** (`deepseek-v4.1-flash`) — Fast text and image understanding
- **Claude Haiku 5.5** (`claude-haiku-5-5`) — Text and image understanding
- **Gemini 4 Argon Contributor** (`gemini-4-argon-contributor`) — Text and image understanding
- **GPT 6 Luna** (`gpt-6-luna`) / **GPT 5.6 Luna** (`gpt-5.6-luna`) — Text and image understanding
- **Grok 4.7** (`grok-4.7`) / **Grok 4.6** (`grok-4.6`) — Text and image understanding
- **Kimi K3** (`kimi-k3`), **Kimi K2.7 Code** (`kimi-k2.7-code`), **Kimi K2.6** (`kimi-k2.6`) — Text and image understanding
- **Qwen3.8 Max** (`qwen3.8-max`), **Qwen3.8 Flash** (`qwen3.8-flash`), **Qwen3.7 Plus** (`qwen3.7-plus`), **Qwen3.6 Plus** (`qwen3.6-plus`) — Text and image understanding
- **GLM 5.3** (`glm-5.3`), **GLM 5.3 Flash** (`glm-5.3-flash`), **GLM 5.2** (`glm-5.2`), **GLM 5.1** (`glm-5.1`) — Text generation
- **MiMo V2.6 Pro** (`mimo-v2.6-pro`), **MiMo V2.6 Flash** (`mimo-v2.6-flash`), **MiMo V2.5** (`mimo-v2.5`) — Text and image understanding
- **MiniMax M3** (`minimax-m3`) — Text and image understanding
- **Muse Spark 1.3 Contributor** (`muse-spark-1.3-contributor`), **Muse Spark 1.2 Contributor** (`muse-spark-1.2-contributor`) — Text and image understanding
- **Hy4 Preview** (`hy4-preview`), **Hy3** (`hy3`), **LongCat 2.0** (`longcat-2.0`), **Qwen3.7 Max** (`qwen3.7-max`) — Text generation
- Free and preview models such as **LongCat 2.5 Preview Free**, **Step 5 Preview Free**, **Cat Jam Free**, and **Space Bunny**

Every model returned from the API receives `textGeneration` and `chatHistory` capabilities. Models that accept images additionally advertise image input, so the AI Client can attach images to prompts sent to them.

The vision-capable model list is baked into the plugin (OpenCode's `/models` endpoint does not expose capabilities). Extend or override it with the `nominal_ai_po_opencode_vision_models` filter:

```php
add_filter( 'nominal_ai_po_opencode_vision_models', function ( array $models ): array {
	$models[] = 'my-new-vision-model';
	return $models;
} );
```

### Using Vision

Send an image to a vision-capable model using the standard PHP AI Client file part:

```php
use WordPress\AiClient\AiClient;

$result = AiClient::prompt( 'What is in this image?' )
	->withFile( 'https://example.com/photo.jpg' )
	->usingProvider( 'opencode' )
	->usingModelPreference( 'claude-haiku-5-5' )
	->generateTextResult();

echo $result->toText();
```

OpenCode's upstream providers only accept **webp, png, jpeg, and gif** images. If you attach anything else (a BMP, TIFF, HEIC, SVG, or a file with a wrong MIME label), the plugin sniffs the real format and re-encodes it automatically — remote images are downloaded to your server first, then converted. Change the target format with the `nominal_ai_po_opencode_image_conversion_mime_type` filter:

```php
// Convert unsupported images to JPEG instead of the default webp.
add_filter( 'nominal_ai_po_opencode_image_conversion_mime_type', fn() => 'image/jpeg' );
```

SVG, TIFF, and HEIC cannot be decoded by GD, so they are passed through unchanged and will still be rejected upstream.

## Configuration

The provider uses the `OPENCODE_API_KEY` environment variable for authentication. Set it via your environment, `wp-config.php`, or inline:

```php
// Via wp-config.php
define( 'OPENCODE_API_KEY', 'your-api-key' );

// Or via PHP environment
putenv( 'OPENCODE_API_KEY=your-api-key' );
```

Visit the [OpenCode Platform](https://opencode.ai/) to sign up for a Go or Zen plan and generate an API key.

### Session ID

Every request is tagged with an `x-opencode-session` header so OpenCode Go can optimize routing and prompt caching. By default the plugin generates a random ID that is stable for the duration of a single PHP request. To use a stable value across requests, define the `OPENCODE_SESSION_ID` constant:

```php
// Via wp-config.php
define( 'OPENCODE_SESSION_ID', 'my-site-conversation-id' );
```

Or set it dynamically with the `nominal_ai_po_opencode_session_id` filter — for example, to reuse a conversation ID from your own chat application:

```php
add_filter( 'nominal_ai_po_opencode_session_id', function ( string $session_id ): string {
	return my_app_current_conversation_id();
} );
```

## Development

```bash
# Install dependencies
composer install

# Lint (phpcs + phpstan)
composer lint

# Auto-fix coding standards issues
composer phpcbf

# Build for production (creates nominal-ai-provider-for-opencode.zip)
./scripts/build.sh
```

## External Services

This plugin connects to the OpenCode API to provide AI capabilities within WordPress. Connection to this service is required to enable text generation, chat history, and dynamic model discovery.

**What data is sent and when:**
- Your OpenCode API key is sent with every request to authenticate with the service.
- Any text prompts, messages, or content you submit for AI processing are sent to OpenCode's servers. When you use a vision-capable model, any images you attach are sent as well; unsupported images are converted to a supported format first, which involves downloading remote images to your server.
- A stable session identifier is sent in the `x-opencode-session` header so OpenCode Go can optimize routing and prompt caching.
- A request is made to the OpenCode API to fetch available models when the plugin initializes.
- Data is only transmitted when AI features are actively used.

**Service provider:** OpenCode
- Website: [https://opencode.ai/](https://opencode.ai/)
- API Base URL: `https://opencode.ai/inference/go/v1`
- Terms of Service: [https://opencode.ai/terms](https://opencode.ai/terms)

## Contributors

* **Kolja Nolte** (kolja.nolte@gmail.com)

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
