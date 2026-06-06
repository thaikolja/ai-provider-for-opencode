=== AI Provider for OpenCode ===
Contributors: wordpressdotorg
Tags: ai, opencode, deepseek, artificial-intelligence, connector
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI Provider for OpenCode for the PHP AI Client SDK.

== Description ==

This plugin provides OpenCode integration for the PHP AI Client SDK. It enables WordPress sites to use OpenCode's models (like DeepSeek) for text generation and other AI capabilities.

**Features:**

* Text generation with OpenCode models
* Automatic provider registration

Available models are dynamically discovered from the OpenCode API.

**Requirements:**

* PHP 7.4 or higher
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional changes are required
* OpenCode API key

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-opencode/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your OpenCode API key via the `OPENCODE_API_KEY` environment variable or constant

== Frequently Asked Questions ==

= How do I get an OpenCode API key? =

Visit the [OpenCode Platform](https://opencode.ai/) to create an account and generate an API key.

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client SDK to be installed and activated (or WordPress 7.0+). It provides the OpenCode-specific implementation that the PHP AI Client uses.

== Changelog ==

= 1.0.0 =

* Initial release of the plugin
* Support for OpenCode text generation models
