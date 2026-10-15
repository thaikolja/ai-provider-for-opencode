=== Nominal AI Provider for OpenCode ===
Contributors:               thaikolja
Tags:                       ai, opencode, artificial-intelligence, connector, provider
Requires at least:          6.9
Tested up to:               7.0
Stable tag:                 1.1.0
Requires PHP:               7.4
License:                    GPL-2.0-or-later
License URI:                https://www.gnu.org/licenses/gpl-2.0.html
Donate link:                https://paypal.me/thaikolja

**Nominal AI Provider for [OpenCode](https://opencode.ai/)** for the PHP AI Client SDK. Use your OpenCode Go or Zen AI
models within WordPress.

== Description ==

**Nominal AI Provider for OpenCode** integrates OpenCode's AI models into WordPress as a provider for the PHP AI Client
 SDK. Once activated, OpenCode is automatically registered as a provider — no manual configuration required.

OpenCode provides access to several open-source models through a unified API, enabling text generation and chat history
capabilities — plus image understanding for the multimodal models — directly within your WordPress site.

= Features =

* **Text Generation** — Use OpenCode's language models for content creation, summarisation, analysis, and more.
* **Vision** — Send images to OpenCode's multimodal models, such as Claude Haiku 5.5, Gemini 4 Argon, GPT 6 Luna, Grok 4.7, Kimi K3, Qwen3.8, MiMo, MiniMax M3, and Muse Spark.
* **Automatic image conversion** — Images that OpenCode rejects (BMP, TIFF, and other unsupported formats) are converted to a supported format (webp/png/jpeg/gif) on the fly.
* **Chat History** — Maintain conversation context across multiple interactions.
* **OpenCode Go session header** — Requests carry the `x-opencode-session` header so OpenCode Go can optimise routing and prompt caching.
* **Dynamic Model Discovery** — Available models are fetched directly from the OpenCode API, so your plugin stays compatible with new releases automatically.
* **Automatic Provider Registration** — Registers itself with the PHP AI Client on WordPress init; no manual wiring needed.

= Requirements =

* PHP 7.4 or higher
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional changes are required
* OpenCode API key

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/nominal-ai-provider-for-opencode/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. (Optional) For safety, configure your OpenCode API key via the `OPENCODE_API_KEY` environment variable or constant, e
.g., `define( 'OPENCODE_API_KEY', 'your-api-key-here' );` in your `wp-config.php` file.

== Frequently Asked Questions ==

= How do I get an OpenCode API key? =

Visit the [OpenCode Platform](https://opencode.ai/) and sign up to either the "Go" or "Zen" plan. Once you have an account, you can generate an API key from the dashboard. This key is required to authenticate your requests to the OpenCode API.

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client SDK to be installed and activated (or WordPress 7.0+). It provides the OpenCode-specific implementation that the PHP AI Client uses.

= What models are available? =

Models are dynamically discovered from the OpenCode API and change over time. Current OpenCode Go models include DeepSeek V4 Pro, DeepSeek V4.1 Flash, Claude Haiku 5.5, Gemini 4 Argon, GPT 6 Luna, GPT 5.6 Luna, Grok 4.7, Grok 4.6, Kimi K3, Kimi K2.7 Code, Kimi K2.6, GLM 5.3, GLM 5.3 Flash, GLM 5.2, GLM 5.1, Qwen3.8 Max, Qwen3.8 Flash, Qwen3.7 Plus, Qwen3.7 Max, Qwen3.6 Plus, MiniMax M3, MiniMax M2.7, MiniMax M2.5, MiMo V2.6 Pro, MiMo V2.6 Flash, MiMo V2.5 Pro, MiMo V2.5, Muse Spark 1.3/1.2 Contributor, Hy4 Preview, Hy3, LongCat 2.0, Space Bunny, and a number of free and preview models. New models are added automatically as OpenCode expands their offerings.

Most models accept text only; the multimodal ones (Claude Haiku 5.5, Gemini 4 Argon, GPT 6 Luna, Grok 4.7, Kimi K3, Qwen3.8, MiMo V2.5/V2.6, MiniMax M3, Muse Spark, and others) also accept images.

== External Services ==

This plugin connects to the OpenCode API to provide AI capabilities within WordPress. Connection to this service is required to enable text generation, chat history, and dynamic model discovery features.

**What data is sent and when:**
* Your OpenCode API key is sent with every request to authenticate with the service.
* Any text prompts, messages, or content you submit for AI processing are sent to OpenCode's servers. When you use a vision-capable model, any images you attach are sent as well; unsupported images are converted to a supported format first (and remote images may be downloaded to your server for that conversion).
* A stable session identifier is sent in the `x-opencode-session` header so OpenCode Go can optimise routing and prompt caching.
* A request is made to the OpenCode API to fetch available models when the plugin initializes.
* Data is only transmitted when AI features are actively used.

**Service provider:** OpenCode
* Website: [https://opencode.ai/](https://opencode.ai/)
* API Base URL: https://opencode.ai/inference/go/v1
* Terms of Service: [https://opencode.ai/terms](https://opencode.ai/terms)

== Changelog ==

= 1.1.0 =
* Vision support — multimodal models now advertise image input, so images can be sent to them.
* Automatic image conversion — unsupported image formats are re-encoded to webp/png/jpeg/gif before sending.
* OpenCode Go session header — all requests now send `x-opencode-session` for routing and prompt caching.
* `nominal_ai_po_opencode_vision_models` filter to extend the vision-capable model list.
* `OPENCODE_SESSION_ID` constant and `nominal_ai_po_opencode_session_id` filter to control the session ID.
* `nominal_ai_po_opencode_image_conversion_mime_type` filter to change the target image format.
* Refreshed model display names and sort order.

= 1.0.0 =
* Initial release.
* Text generation via OpenCode Go and Zen AI models.
* Chat history across all discovered models.
* Dynamic model discovery — models are fetched from the API, so new releases appear automatically.
* Automatic provider registration on WordPress init.
* Provider logo and description (requires PHP AI Client SDK 1.2.0+).

== Upgrade Notice ==

= 1.1.0 =
Adds vision (image input) support for multimodal models and sends the OpenCode Go `x-opencode-session` header.

= 1.0.0 =
Initial release — no upgrade concerns.
