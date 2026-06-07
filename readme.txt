=== AI Provider for OpenCode ===
Contributors:               thaikolja
Tags:                       ai, opencode, artificial-intelligence, connector, deepseek
Requires at least:          6.9
Tested up to:               7.0
Stable tag:                 1.0.0
Requires PHP:               7.4
License:                    GPL-2.0-or-later
License URI:                https://www.gnu.org/licenses/gpl-2.0.html

AI Provider for OpenCode for the PHP AI Client SDK.

== Description ==

AI Provider for OpenCode integrates OpenCode's AI models into WordPress as a provider for the PHP AI Client SDK. Once activated, OpenCode is automatically registered as a provider — no manual configuration required.

OpenCode provides access to DeepSeek models through a unified API, enabling text generation and chat history capabilities directly within your WordPress site.

= Features =

* **Text Generation** — Use OpenCode's language models for content creation, summarisation, analysis, and more.
* **Chat History** — Maintain conversation context across multiple interactions.
* **Dynamic Model Discovery** — Available models are fetched directly from the OpenCode API, so your plugin stays compatible with new releases automatically.
* **Automatic Provider Registration** — Registers itself with the PHP AI Client on WordPress init; no manual wiring needed.

= Requirements =

* PHP 7.4 or higher
* For WordPress 6.9, the <a href="https://github.com/WordPress/php-ai-client">wordpress/php-ai-client</a> package must be installed
* For WordPress 7.0 and above, no additional changes are required
* OpenCode API key

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-opencode/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your OpenCode API key via the `OPENCODE_API_KEY` environment variable or constant

== Frequently Asked Questions ==

= How do I get an OpenCode API key? =

Visit the <a href="https://opencode.ai/">OpenCode Platform</a> and sign up to either the "Go" or "Zen" plan. Once you have an account, you can generate an API key from the dashboard. This key is required to authenticate your requests to the OpenCode API.

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client SDK to be installed and activated (or WordPress 7.0+). It provides the OpenCode-specific implementation that the PHP AI Client uses.

= What models are available? =

Models are dynamically discovered from the OpenCode API. Currently available models include DeepSeek-V4-Flash and DeepSeek-V4-Pro, with more added automatically as OpenCode expands their offerings.

== External Services ==

This plugin connects to the OpenCode API to provide AI capabilities within WordPress. Connection to this service is required to enable text generation, chat history, and dynamic model discovery features.

**What data is sent and when:**
* Your OpenCode API key is sent with every request to authenticate with the service.
* Any text prompts, messages, or content you submit for AI processing are sent to OpenCode's servers.
* A request is made to the OpenCode API to fetch available models when the plugin initializes.
* Data is only transmitted when AI features are actively used.

**Service provider:** OpenCode
* Website: <a href="https://opencode.ai/">https://opencode.ai/</a>
* API Base URL: https://opencode.ai/zen/go/v1

== Screenshots ==

1. Connectors Page — The OpenCode provider listed alongside other available AI providers.

== Changelog ==

= 1.0.0 =
* Initial release.
* Support for OpenCode text generation models.
* Dynamic model discovery from the OpenCode API.
* Chat history capability for all discovered models.
* Automatic provider registration on WordPress init.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade concerns.
