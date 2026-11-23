=== Nominal AI Provider for OpenCode ===
Contributors:               thaikolja
Tags:                       ai, opencode, artificial-intelligence, connector, provider
Requires at least:          6.9
Tested up to:               7.1
Stable tag:                 1.1.2
Requires PHP:               7.4
License:                    GPL-2.0-or-later
License URI:                https://www.gnu.org/licenses/gpl-2.0.html
Donate link:                https://paypal.me/thaikolja

Use OpenCode in WordPress to write, summarize, and revise content.

== Description ==

**Nominal AI Provider for OpenCode** connects [OpenCode](https://opencode.ai/) to the AI tools already in WordPress.

Activate the plugin and add your OpenCode API key. OpenCode then shows up with your other AI providers. You can draft, summarize, rewrite, and continue a conversation. The model list comes from OpenCode, so new models can appear without a plugin update. There is no separate settings page.

This plugin uses OpenCode Go. OpenCode Zen is a different product and is not included.

= What you can do =

* **Write with OpenCode.** Draft, summarize, rewrite, or review content.
* **Keep the conversation.** A follow-up can still use what was said before.
* **See current models.** WordPress asks OpenCode which models are available right now.
* **Start after activation.** WordPress 7.0 and newer already include the AI tools. WordPress 6.9 needs the PHP AI Client as well.

= Requirements =

* PHP 7.4 or newer
* WordPress 7.0 or newer, or WordPress 6.9 with the [PHP AI Client](https://github.com/WordPress/php-ai-client) installed
* An OpenCode API key

== Installation ==

1. Install the plugin from Plugins > Add New, or upload it to `/wp-content/plugins/nominal-ai-provider-for-opencode/`
2. Activate it under Plugins
3. Add your OpenCode API key in `wp-config.php`, for example `define( 'OPENCODE_API_KEY', 'your-api-key-here' );`. You can also set the `OPENCODE_API_KEY` environment variable. OpenCode stays unavailable until a key is set.

== Frequently Asked Questions ==

= How do I get an OpenCode API key? =

Open the [OpenCode console](https://opencode.ai/console), subscribe to OpenCode Go, and copy the key from the dashboard. A Zen key belongs to a different product. This plugin does not use it.

= Does this plugin work on its own? =

It needs WordPress's AI tools. Those are included in WordPress 7.0 and newer. On WordPress 6.9, install the PHP AI Client first. This plugin is the OpenCode connection, not a separate chat product.

= Which models can I use? =

Whatever OpenCode offers at the moment. The list updates on its own when OpenCode adds or removes models.

= How long does a request wait? =

About two minutes, unless the tool that started the request already chose a different limit.

== External Services ==

This plugin contacts OpenCode when WordPress uses it to generate text or to list models.

**What is sent and when:**
* Your API key, so OpenCode can recognize your site.
* The text you submit, when you ask for a generation.
* A session id on those requests. OpenCode Go requires it.
* A request for the model list, when WordPress checks whether OpenCode is available or which models it has. Activating the plugin does not contact OpenCode by itself.

**Service provider:** OpenCode
* Website: [https://opencode.ai/](https://opencode.ai/)
* API Base URL: https://opencode.ai/inference/go/v1
* Terms of Service: [https://opencode.ai/terms](https://opencode.ai/terms)

== Changelog ==

= 1.1.2 =
* fix: give text generation a 120-second timeout when the caller did not set one
* feat: filter that timeout with `nominal_aipo_opencode_request_timeout`
* docs: state that requests use the OpenCode Go endpoint and send `x-opencode-session`
* docs: state when prompts and the model list are sent to OpenCode
* docs: describe the plugin in plain language for WordPress users

= 1.1.1 =
* fix: use the display name returned by the OpenCode model list
* chore: point the plugin URI at the GitHub source repository

= 1.1.0 =
* fix: do not define a placeholder `OPENCODE_API_KEY`
* fix: ship `icon-256x256.png` at 256×256 and remove proprietary comments from the banner and icon
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

= 1.1.2 =
Text generation now waits 120 seconds unless a request already has its own timeout.

= 1.1.1 =
Model names now come from OpenCode's model list.

= 1.1.0 =
Tested with WordPress 7.1. Function calling is now advertised to the AI Client. Set `OPENCODE_API_KEY` if the provider does not appear.

= 1.0.0 =
Initial release.
