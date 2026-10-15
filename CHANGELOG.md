# Changelog

## 1.1.0 (2026-10-15)

- **Vision support** — models that accept image input now advertise image input modalities, so the
  AI Client can send attachments to OpenCode's multimodal models (Claude Haiku 5.5, Gemini 4 Argon,
  GPT 6 Luna, Grok 4.7, Kimi K3, Qwen3.8, MiMo, MiniMax M3, Muse Spark, and more).
- **OpenCode Go session header** — every request now sends `x-opencode-session`, which OpenCode Go
  uses for routing and prompt caching. The value is stable for the whole PHP request and can be
  overridden with the `OPENCODE_SESSION_ID` constant or the `nominal_ai_po_opencode_session_id`
  filter.
- **Automatic image conversion** — images in formats OpenCode rejects (BMP, TIFF, HEIC, SVG, or a wrong MIME label) are now re-encoded on the fly (via GD) to webp/png/jpeg/gif before the request is sent.
- `nominal_ai_po_opencode_vision_models` filter to extend or override the vision-capable model list.
- `nominal_ai_po_opencode_image_conversion_mime_type` filter to change the target image format.
- Updated the API base URL from `https://opencode.ai/zen/go/v1` to the documented `https://opencode.ai/inference/go/v1`.
- Refreshed model display names and sort order for the current OpenCode Go catalogue.
- Added `apply_filters()` to the PHPStan WordPress stubs.
- Added PHPStan stubs for the GD and WordPress HTTP functions used by the image converter.

## 1.0.0 (2026-06-07)

- Initial release.
- Text generation via OpenCode Go and Zen AI models.
- Chat history across all discovered models.
- Dynamic model discovery — models are fetched from the API, so new releases appear automatically.
- Automatic provider registration on WordPress init.
- Provider logo and description (requires PHP AI Client SDK 1.2.0+).

[1.1.0]: https://github.com/thaikolja/nominal-ai-provider-for-opencode/releases/tag/1.1.0
[1.0.0]: https://github.com/thaikolja/nominal-ai-provider-for-opencode/releases/tag/1.0.0
