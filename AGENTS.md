# AI Provider for OpenCode - Developer Documentation

## Overview
The `ai-provider-for-opencode` plugin integrates OpenCode's API (`https://opencode.ai/zen/go/v1`) into the official WordPress AI Client SDK (`wordpress/php-ai-client`).

This connector strictly adheres to the official `wordpress/php-ai-client` conventions and utilizes the built-in OpenAI compatibility layer to map requests directly to OpenCode's chat completion endpoints seamlessly.

## File Structure
- `plugin.php`: Main WordPress entry point. Registers the `OpenCodeProvider` to the SDK's `ProviderRegistry` on the `init` hook (priority 5).
- `src/autoload.php`: A custom PSR-4 autoloader to prevent bundling Composer's `vendor/` directory in the final production `.zip`.
- `src/Provider/OpenCodeProvider.php`: Handles API routing, defining the base URL, provider metadata (name, URL, logo), and instantiating the text generation model.
- `src/Metadata/OpenCodeModelMetadataDirectory.php`: Connects to OpenCode's `/models` endpoint to query model capabilities. This dynamically registers `deepseek-v4-flash` and `deepseek-v4-pro` and maps them to text generation capabilities.
- `src/Models/OpenCodeTextGenerationModel.php`: Extends `AbstractOpenAiCompatibleTextGenerationModel` to manage `/chat/completions` API calls seamlessly.
- `scripts/build.sh`: Developer script to package the plugin into a production-ready zip archive, strictly ignoring developer artifacts.
- `.distignore`: Ensures `.git`, `tests`, `scripts`, and Composer dependencies do not ship in the final zip file.

## Build Process
To prepare the plugin for production or WordPress plugin repository submission:

```bash
# Set executable permissions (if not already set)
chmod +x scripts/build.sh

# Run the build process
./scripts/build.sh
```

**What happens during the build:**
1. Removes previous build artifacts.
2. Runs `composer install --no-dev --optimize-autoloader` to clean dependencies.
3. Copies all necessary files based on rules defined in `.distignore`.
4. Creates a clean `ai-provider-for-opencode.zip` in the root directory.
5. Restores the developer dependencies by re-running `composer install`.
