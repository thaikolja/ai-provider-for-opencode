# Nominal AI Provider for OpenCode

[![Stable Version](https://img.shields.io/badge/stable-1.1.2-blue)](https://github.com/thaikolja/ai-provider-for-opencode/releases) [![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-purple)](https://www.php.net/supported-versions.php) [![WordPress Version](https://img.shields.io/badge/wordpress-%3E%3D6.9-blue)](https://wordpress.org/download/) [![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)](https://www.gnu.org/licenses/gpl-2.0.html) [![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/nominal-ai-provider-for-opencode)](https://wordpress.org/plugins/nominal-ai-provider-for-opencode/) [![Packagist Version](https://img.shields.io/packagist/v/thaikolja/nominal-ai-provider-for-opencode)](https://packagist.org/packages/thaikolja/nominal-ai-provider-for-opencode)

This plugin lets WordPress generate text with [OpenCode](https://opencode.ai/). It also works as a Composer package for the [PHP AI Client](https://github.com/WordPress/php-ai-client).

**Note:** This GitHub repository is the plugin source. You can clone it and run it as a plugin. For a production site, install the built release from [WordPress.org](https://wordpress.org/plugins/nominal-ai-provider-for-opencode/).

## Description

This plugin connects [OpenCode](https://opencode.ai/) to the AI tools already in WordPress.

Activate it and add your OpenCode API key. OpenCode then shows up with your other AI providers. You can draft, summarize, rewrite, and continue a conversation. The model list comes from OpenCode, so new models can appear without a plugin update. There is no separate settings page.

This plugin uses OpenCode Go. OpenCode Zen is a different product and is not included.

## Features

- **Write with OpenCode.** Draft, summarize, rewrite, or review content.
- **Keep the conversation.** A follow-up can still use what was said before.
- **See current models.** WordPress asks OpenCode which models are available right now.
- **Start after activation.** WordPress 7.0 and newer already include the AI tools. WordPress 6.9 needs the PHP AI Client as well.

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

Each model uses the `name` returned by that endpoint. If the name is missing, `deepseek-v4-pro` and `deepseek-v4.1-flash` have fallback labels and every other ID is auto-formatted. Models are sorted alphabetically by ID.

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

Text generation waits 120 seconds when the caller did not set a limit. The `nominal_aipo_opencode_request_timeout` filter can change that for one model.

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

This plugin contacts OpenCode when WordPress uses it to generate text or to list models.

**What is sent and when:**

- Your API key, so OpenCode can recognize your site.
- The text you submit, when you ask for a generation.
- A session id on those requests. OpenCode Go requires it.
- A request for the model list, when WordPress checks whether OpenCode is available or which models it has. Activating the plugin does not contact OpenCode by itself.

**Service provider:** OpenCode

- Website: [https://opencode.ai/](https://opencode.ai/)
- API Base URL: `https://opencode.ai/inference/go/v1`
- Terms of Service: [https://opencode.ai/terms](https://opencode.ai/terms)

## Contributors

* **Kolja Nolte** (kolja.nolte@gmail.com)

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
