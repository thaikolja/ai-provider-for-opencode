# Changelog

## 1.1.0 (2026-11-02)

- fix: do not define a placeholder `OPENCODE_API_KEY`
- feat: send the `x-opencode-session` header and call the OpenCode Go inference endpoint
- feat: advertise function calling on discovered text models
- feat: send reasoning content back on thinking-model tool calls
- feat: send a plugin User-Agent on text-generation requests
- fix: load bundled translations for the plugin text domain
- docs: mark the plugin tested up to WordPress 7.1
- docs: describe live OpenCode Go model discovery instead of a fixed model list
- chore: develop against PHP AI Client SDK 1.3.1, matching WordPress 7.1 core

## 1.0.0 (2026-06-07)

- feat: initial release of text generation via OpenCode Go and Zen models
- feat: chat history across discovered models
- feat: dynamic model discovery from the OpenCode API
- feat: register the provider automatically on WordPress init
- feat: provider logo and description when the PHP AI Client SDK is 1.2.0 or newer

[1.1.0]: https://github.com/thaikolja/nominal-ai-provider-for-opencode/releases/tag/1.1.0
[1.0.0]: https://github.com/thaikolja/nominal-ai-provider-for-opencode/releases/tag/1.0.0
