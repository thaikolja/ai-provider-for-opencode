# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.0.0

**Released:** 2026-06-07

### Added
- Initial release of the plugin.
- Support for OpenCode text generation models via the PHP AI Client SDK.
- Dynamic model discovery from the OpenCode API.
- Chat history capability for all discovered models.
- Automatic provider registration on WordPress `init`.
- SDK version gating for provider description (≥1.2.0) and logo path (≥1.3.0).
- Production build script (`scripts/build.sh`).
- PHPCS and PHPStan linting configuration.

[1.0.0]: https://github.com/thaikolja/ai-provider-for-opencode/releases/tag/1.0.0
