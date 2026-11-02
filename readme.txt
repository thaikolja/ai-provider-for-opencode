=== Nominal AI Provider for OpenCode ===
Contributors:               thaikolja
Tags:                       ai, opencode, artificial-intelligence, connector, provider
Requires at least:          6.9
Tested up to:               7.1
Stable tag:                 1.1.0
Requires PHP:               7.4
License:                    GPL-2.0-or-later
License URI:                https://www.gnu.org/licenses/gpl-2.0.html
Donate link:                https://paypal.me/thaikolja

**Nominal AI Provider for [OpenCode](https://opencode.ai/)** for the PHP AI Client SDK. Use your OpenCode Go models within WordPress.

== Description ==

**Nominal AI Provider for OpenCode** integrates OpenCode's AI models into WordPress as a provider for the PHP AI Client
 SDK. Once activated, OpenCode is automatically registered as a provider — no manual configuration required.

OpenCode Go provides access to models through an OpenAI-compatible API, enabling text generation and chat history
capabilities directly within your WordPress site. Go is built for coding agents. WordPress content requests are a
different kind of traffic. The plugin sends its own User-Agent on generation requests and an `x-opencode-session` header on every request.

= Features =

* **Text Generation** — Use OpenCode's language models for content creation, summarisation, analysis, and more.
* **Chat History** — Maintain conversation context across multiple interactions.
* **OpenCode Go session header** — Requests carry the `x-opencode-session` header so OpenCode Go can optimise routing and prompt caching.
* **Dynamic Model Discovery** — Available models are fetched directly from the OpenCode API, so your plugin stays compatible with new releases automatically.
* **Automatic Provider Registration** — Registers itself with the PHP AI Client on WordPress init; no manual wiring needed.

= Requirements =

* PHP 7.4 or higher
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional changes are required
* OpenCode Go API key

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/nominal-ai-provider-for-opencode/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set your OpenCode Go API key as the `OPENCODE_API_KEY` environment variable or constant, for example `define( 'OPENCODE_API_KEY', 'your-api-key-here' );` in `wp-config.php`. Without this key the provider stays unavailable.

== Frequently Asked Questions ==

= How do I get an OpenCode API key? =

Visit the [OpenCode Console](https://opencode.ai/console) and subscribe to OpenCode Go. Copy the API key from the dashboard. This plugin talks to the Go API at `https://opencode.ai/inference/go/v1`. OpenCode Zen is a separate product and is not connected here.

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client SDK to be installed and activated (or WordPress 7.0+). It provides the OpenCode-specific implementation that the PHP AI Client uses.

= What models are available? =

Models are discovered live from `GET https://opencode.ai/inference/go/v1/models`. The list changes as OpenCode adds and removes models, so this plugin does not ship a fixed catalog.

== External Services ==

This plugin connects to the OpenCode API to provide AI capabilities within WordPress. Connection to this service is required to enable text generation, chat history, and dynamic model discovery features.

**What data is sent and when:**
* Your OpenCode API key is sent with every request to authenticate with the service.
* Any text prompts, messages, or content you submit for AI processing are sent to OpenCode's servers.
* A session identifier is sent in the `x-opencode-session` header so OpenCode Go can optimise routing and prompt caching.
* A request is made to the OpenCode API to fetch available models when the plugin initializes.
* Data is only transmitted when AI features are actively used.

**Service provider:** OpenCode
* Website: [https://opencode.ai/](https://opencode.ai/)
* API Base URL: https://opencode.ai/inference/go/v1
* Terms of Service: [https://opencode.ai/terms](https://opencode.ai/terms)

== Changelog ==

= 1.1.0 =
* fix: do not define a placeholder `OPENCODE_API_KEY`
* feat: send the `x-opencode-session` header and call the OpenCode Go inference endpoint
* feat: advertise function calling on discovered text models
* feat: send reasoning content back on thinking-model tool calls
* feat: send a plugin User-Agent on text-generation requests
* fix: load bundled translations for the plugin text domain
* docs: mark the plugin tested up to WordPress 7.1
* docs: describe live OpenCode Go model discovery instead of a fixed model list

= 1.0.0 =
* feat: initial release of text generation via OpenCode Go and Zen models
* feat: chat history across discovered models
* feat: dynamic model discovery from the OpenCode API
* feat: register the provider automatically on WordPress init
* feat: provider logo and description when the PHP AI Client SDK is 1.2.0 or newer

== Upgrade Notice ==

= 1.1.0 =
Tested with WordPress 7.1. Function calling is now advertised to the AI Client. Set `OPENCODE_API_KEY` if the provider does not appear.

= 1.0.0 =
Initial release.
