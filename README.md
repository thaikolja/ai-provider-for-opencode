# AI Provider for OpenCode

[![Stable Version](https://img.shields.io/badge/stable-1.0.0-blue)](https://github.com/thaikolja/ai-provider-for-opencode/releases)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-purple)](https://www.php.net/supported-versions.php)
[![WordPress Version](https://img.shields.io/badge/wordpress-%3E%3D6.9-blue)](https://wordpress.org/download/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/ai-provider-for-opencode)](https://wordpress.org/plugins/ai-provider-for-opencode/)
[![Packagist Version](https://img.shields.io/packagist/v/thaikolja/ai-provider-for-opencode)](https://packagist.org/packages/thaikolja/ai-provider-for-opencode)

An AI Provider for OpenCode for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

## Description

This plugin integrates OpenCode's AI models into WordPress as a provider for the PHP AI Client SDK. Once activated, OpenCode is automatically registered as a provider — no manual configuration required.

OpenCode provides access to DeepSeek models through a unified API, enabling text generation and chat history capabilities for WordPress sites.

## Features

- **Text Generation** — Use OpenCode's language models for content creation, summarization, analysis, and more.
- **Chat History** — Maintain conversation context across multiple interactions.
- **Dynamic Model Discovery** — Available models are fetched directly from the OpenCode API, so your plugin stays compatible with new releases automatically.
- **Automatic Provider Registration** — Registers itself with the PHP AI Client on WordPress `init`; no manual wiring needed.

## Requirements

- PHP 7.4 or higher
- For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
- For WordPress 7.0 and above, no additional changes are required
- OpenCode API key

## Installation

### As a Composer Package

```bash
composer require thaikolja/ai-provider-for-opencode
```

Then register the provider manually:

```php
use WordPress\AiClient\AiClient;
use WordPress\OpenCodeAiProvider\Provider\OpenCodeProvider;

$registry = AiClient::defaultRegistry();
$registry->registerProvider( OpenCodeProvider::class );
```

### As a WordPress Plugin

1. Download the latest release from the [releases page](https://github.com/thaikolja/ai-provider-for-opencode/releases)
2. Upload to `/wp-content/plugins/ai-provider-for-opencode/`
3. Activate the plugin through the WordPress admin
4. Configure your OpenCode API key (see [Configuration](#configuration))

## Usage

### With WordPress

The provider automatically registers itself on the `init` hook. Simply ensure the plugin is active and configure your API key:

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
use WordPress\OpenCodeAiProvider\Provider\OpenCodeProvider;

// Register the provider
$registry = AiClient::defaultRegistry();
$registry->registerProvider( OpenCodeProvider::class );

// Set your API key
putenv( 'OPENCODE_API_KEY=your-api-key' );

// Generate text
$result = AiClient::prompt( 'Explain quantum computing' )
	->usingProvider( 'opencode' )
	->generateTextResult();

echo $result->toText();
```

## Supported Models

Available models are dynamically discovered from the OpenCode API at `https://opencode.ai/zen/go/v1/models`. Currently available models include:

- **DeepSeek-V4-Flash** (`deepseek-v4-flash`) — Fast, efficient text generation
- **DeepSeek-V4-Pro** (`deepseek-v4-pro`) — High-quality text generation

All models returned from the API receive `textGeneration` and `chatHistory` capabilities automatically.

## Configuration

The provider uses the `OPENCODE_API_KEY` environment variable for authentication. Set it via your environment, `wp-config.php`, or inline:

```php
// Via wp-config.php
define( 'OPENCODE_API_KEY', 'your-api-key' );

// Or via PHP environment
putenv( 'OPENCODE_API_KEY=your-api-key' );
```

Visit the [OpenCode Platform](https://opencode.ai/) to sign up for a Go or Zen plan and generate an API key.

## Development

```bash
# Install dependencies
composer install

# Lint (phpcs + phpstan)
composer lint

# Auto-fix coding standards issues
composer phpcbf

# Build for production (creates ai-provider-for-opencode.zip)
./scripts/build.sh
```

See [`AGENTS.md`](AGENTS.md) for architecture details and conventions.

## External Services

This plugin connects to the OpenCode API to provide AI capabilities within WordPress. Connection to this service is required to enable text generation, chat history, and dynamic model discovery.

**What data is sent and when:**
- Your OpenCode API key is sent with every request to authenticate with the service.
- Any text prompts, messages, or content you submit for AI processing are sent to OpenCode's servers.
- A request is made to the OpenCode API to fetch available models when the plugin initializes.
- Data is only transmitted when AI features are actively used.

**Service provider:** OpenCode
- Website: [https://opencode.ai/](https://opencode.ai/)
- API Base URL: `https://opencode.ai/zen/go/v1`

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
