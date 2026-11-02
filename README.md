# Nominal AI Provider for OpenCode

[![Stable Version](https://img.shields.io/badge/stable-1.1.0-blue)](https://github.com/thaikolja/nominal-ai-provider-for-opencode/releases) [![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-purple)](https://www.php.net/supported-versions.php) [![WordPress Version](https://img.shields.io/badge/wordpress-%3E%3D6.9-blue)](https://wordpress.org/download/) [![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)](https://www.gnu.org/licenses/gpl-2.0.html) [![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/nominal-ai-provider-for-opencode)](https://wordpress.org/plugins/nominal-ai-provider-for-opencode/) [![Packagist Version](https://img.shields.io/packagist/v/thaikolja/nominal-ai-provider-for-opencode)](https://packagist.org/packages/thaikolja/nominal-ai-provider-for-opencode)

This plugin is a Nominal AI Provider for [OpenCode](https://opencode.ai/) for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

**Note:** You are viewing the *development* repository. You *can* clone or download this entire repository and use it as a plugin, but for safety and speed, you should use the built version from WordPress.org.

## Description

This plugin integrates OpenCode Go models into WordPress as a provider for the PHP AI Client SDK. Once activated, OpenCode is automatically registered as a provider – no manual configuration required beyond the API key.

OpenCode Go exposes models through `https://opencode.ai/inference/go/v1`. OpenCode Zen is a separate product and is not connected by this plugin. Go is built for coding agents. WordPress content requests are a different kind of traffic. Every request sends `x-opencode-session` and
`User-Agent: Nominal-AI-Provider-OpenCode/1.1.0`, unless the caller already set a User-Agent.

## Features

- **Text Generation** — Use OpenCode's language models for content creation, summarization, analysis, and more.
- **Chat History** — Maintain conversation context across multiple interactions.
- **OpenCode Go Session Header** — Every request carries the `x-opencode-session` header so OpenCode Go can optimize routing and prompt caching.
- **Dynamic Model Discovery** — Available models are fetched directly from the OpenCode API, so your plugin stays compatible with new releases automatically.
- **Automatic Provider Registration** — Registers itself with the PHP AI Client on WordPress `init`; no manual wiring needed.

## Requirements

- PHP 7.4 or higher
- For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
- For WordPress 7.0 and above, no additional changes are required
- OpenCode Go API key

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

Models are discovered live from `GET https://opencode.ai/inference/go/v1/models`. The catalog changes as OpenCode updates Go, so this plugin does not ship a fixed list.

`deepseek-v4-pro` and `deepseek-v4-flash` have explicit display names. Every other ID is auto-formatted. Models are sorted alphabetically by ID.

Each discovered model receives `textGeneration` and `chatHistory` capabilities, and advertises function calling.

## Configuration

The provider uses the `OPENCODE_API_KEY` environment variable for authentication. Set it via your environment, `wp-config.php`, or inline:

```php
// Via wp-config.php
define( 'OPENCODE_API_KEY', 'your-api-key' );

// Or via PHP environment
putenv( 'OPENCODE_API_KEY=your-api-key' );
```

Visit the [OpenCode Console](https://opencode.ai/console) to subscribe to Go and generate an API key. The provider stays unavailable until `OPENCODE_API_KEY` is set.

## Development

```bash
# Install dependencies
composer install

# Lint (phpcs, phpmd, phpstan). Rules live in phpcs.xml.dist.
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
- Any text prompts, messages, or content you submit for AI processing are sent to OpenCode's servers.
- A session identifier is sent in the `x-opencode-session` header so OpenCode Go can optimize routing and prompt caching.
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
