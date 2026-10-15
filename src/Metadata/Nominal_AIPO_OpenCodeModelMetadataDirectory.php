<?php

declare( strict_types = 1 );

namespace Nominal\AIProviderOpenCode\Metadata;

// We need the provider to build full API URLs (base URL + path).
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;
// Enums and value objects the SDK needs for describing what models can do.
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
// The abstract base that handles most of the OpenAI-compatible model listing logic.
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Handles discovery and parsing of OpenCode's model list.
 *
 * This is where we hit the /models endpoint, parse the JSON response,
 * and turn each model into a fully described ModelMetadata object that
 * the SDK and UI can work with.
 *
 * @since 1.0.0
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id: string}>
 * }
 */
class Nominal_AIPO_OpenCodeModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * Model IDs that accept image input in addition to text.
	 *
	 * OpenCode's /models endpoint does not describe model capabilities, so vision
	 * support has to be tracked explicitly. The list below mirrors the OpenCode Go
	 * catalogue; any model that is not listed keeps text-only input. Integrations can
	 * extend the list at runtime via the `nominal_ai_po_opencode_vision_models` filter.
	 *
	 * Only image input is declared, because that is the only non-text modality the
	 * SDK's OpenAI-compatible text generation model can serialise.
	 *
	 * @since 1.1.0
	 * @var string[]
	 */
	private const VISION_MODEL_IDS = [
		'claude-haiku-5-5',
		'deepseek-v4.1-flash',
		'gemini-4-argon-contributor',
		'glm-5.3-flash',
		'gpt-5.6-luna',
		'gpt-6-luna',
		'grok-4.6',
		'grok-4.7',
		'kimi-k2.6',
		'kimi-k2.7-code',
		'kimi-k3',
		'longcat-2.5-preview-free',
		'mimo-v2.5',
		'mimo-v2.6-flash',
		'mimo-v2.6-pro',
		'minimax-m3',
		'muse-spark-1.2-contributor',
		'muse-spark-1.3-contributor',
		'qwen3.6-plus',
		'qwen3.7-plus',
		'qwen3.8-flash',
		'qwen3.8-max',
		'space-bunny',
		'step-5-preview-free',
	];

	/**
	 * Builds a proper HTTP request pointed at the OpenCode API.
	 *
	 * Takes the relative path (like "/models") and prepends the full base URL.
	 * The OpenCode session headers are attached so the listing call carries the
	 * same `x-opencode-session` value as the generation calls.
	 *
	 * @since 1.0.0
	 * @param HttpMethodEnum $method GET or POST, depending on the call.
	 * @param string $path Relative API path (e.g. "/models").
	 * @param array $headers Extra HTTP headers if needed.
	 * @param mixed $data Request body for POST calls.
	 * @return Request A fully configured request ready to send.
	 */
	protected function createRequest(
		HttpMethodEnum $method, string $path, array $headers = [], $data = null
	): Request {

		// Merge in the OpenCode-specific headers (currently the session ID).
		$headers = array_merge( $headers, Nominal_AIPO_OpenCodeProvider::requestHeaders() );

		// Nominal_AIPO_OpenCodeProvider::url() glues the base URL and the path together for us.
		return new Request( $method, Nominal_AIPO_OpenCodeProvider::url( $path ), $headers, $data );
	}

	/**
	 * Takes the raw /models API response and turns it into a list of model metadata.
	 *
	 * This is the core of the discovery process. We parse the JSON, build a
	 * standard set of options every model gets, layer on image input support for
	 * the models that can see, and wrap everything up as ModelMetadata objects.
	 *
	 * @since 1.0.0
	 * @param Response $response The raw API response from OpenCode.
	 * @return ModelMetadata[] A sorted list of models the provider can use.
	 * @throws ResponseException If the response is missing the expected data key.
	 */
	protected function parseResponseToModelMetadataList( Response $response ): array {

		// Pull the decoded JSON body out of the response object.
		/** @var ModelsResponseData $responseData */
		$responseData = $response->getData();

		// Bail loudly if the API didn't give us a model list under the "data" key.
		if ( ! isset( $responseData['data'] ) || ! $responseData['data'] ) {
			throw ResponseException::fromMissingData( 'OpenCode', 'data' );
		}

		// Every text-generation model shares this same set of supported options.
		// Input modalities are added per-model below, since only some models can
		// accept images.
		$baseTextOptions = [
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption(
				OptionEnum::outputMimeType(),
				[ 'text/plain', 'application/json' ]
			),
			new SupportedOption( OptionEnum::customOptions() ),
			new SupportedOption(
				OptionEnum::outputModalities(),
				[ [ ModalityEnum::text() ] ]
			),
		];

		// Resolve the vision-capable model list once, letting integrations extend it.
		$visionModelIds = self::VISION_MODEL_IDS;
		if ( function_exists( 'apply_filters' ) ) {
			$filteredVisionModelIds = apply_filters(
				'nominal_ai_po_opencode_vision_models',
				$visionModelIds
			);
			if ( is_array( $filteredVisionModelIds ) ) {
				$visionModelIds = $filteredVisionModelIds;
			}
		}

		// Cast to array just in case the API returned something unexpected.
		$modelsData = (array) $responseData['data'];

		// Transform each raw model entry from the API into a ModelMetadata object.
		$models = array_map(
			function ( array $modelData ) use ( $baseTextOptions, $visionModelIds ): ModelMetadata {

				// The model ID is the canonical slug (e.g. "deepseek-v4-pro").
				$modelId = $modelData['id'];

				// Text input is the baseline; vision models also accept images.
				$inputModalityCombinations = [ [ ModalityEnum::text() ] ];
				if ( in_array( $modelId, $visionModelIds, true ) ) {
					$inputModalityCombinations[] = [ ModalityEnum::text(), ModalityEnum::image() ];
				}

				$options = $baseTextOptions;
				$options[] = new SupportedOption(
					OptionEnum::inputModalities(),
					$inputModalityCombinations
				);

				// Every model gets text generation + chat history. We don't do
				// fine-grained capability checks here — it's simpler and covers
				// everything OpenCode currently offers.
				return new ModelMetadata(
					$modelId,
					self::formatDisplayName( $modelId ),
					[CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()],
					$options
				);
			},
			$modelsData
		);

		// Sort models so the "pro" and "flash" variants show up first in lists.
		usort( $models, [$this, 'modelSortCallback'] );

		return $models;
	}

	/**
	 * Converts a technical model ID into something humans can read.
	 *
	 * We have pretty names for the models we know about; anything else
	 * gets an auto-generated name by replacing dashes and underscores
	 * with spaces and capitalising each word.
	 *
	 * @since 1.0.0
	 * @param string $id The raw model ID from the API (e.g. "deepseek-v4-flash").
	 * @return string A display-friendly name (e.g. "DeepSeek-V4-Flash").
	 */
	private static function formatDisplayName( string $id ): string {

		// Explicit display names for every known model so the UI looks polished.
		$map = [
			'deepseek-v4-pro' => 'DeepSeek-V4-Pro',
			'deepseek-v4.1-flash' => 'DeepSeek-V4.1-Flash',
			'deepseek-v4-flash' => 'DeepSeek-V4-Flash',
			'claude-haiku-5-5' => 'Claude-Haiku-5.5',
			'gemini-4-argon-contributor' => 'Gemini-4-Argon-Contributor',
			'glm-5.3' => 'GLM-5.3',
			'glm-5.3-flash' => 'GLM-5.3-Flash',
			'glm-5.2' => 'GLM-5.2',
			'glm-5.1' => 'GLM-5.1',
			'glm-5' => 'GLM-5',
			'gpt-6-luna' => 'GPT-6-Luna',
			'gpt-5.6-luna' => 'GPT-5.6-Luna',
			'grok-4.7' => 'Grok-4.7',
			'grok-4.6' => 'Grok-4.6',
			'kimi-k3' => 'Kimi-K3',
			'kimi-k2.7-code' => 'Kimi-K2.7-Code',
			'kimi-k2.6' => 'Kimi-K2.6',
			'kimi-k2.5' => 'Kimi-K2.5',
			'longcat-2.5-preview-free' => 'LongCat-2.5-Preview-Free',
			'longcat-2.0' => 'LongCat-2.0',
			'mimo-v2.6-pro' => 'MiMo-V2.6-Pro',
			'mimo-v2.6-flash' => 'MiMo-V2.6-Flash',
			'mimo-v2.5-pro' => 'MiMo-V2.5-Pro',
			'mimo-v2.5' => 'MiMo-V2.5',
			'mimo-v2-pro' => 'MiMo-V2-Pro',
			'mimo-v2-omni' => 'MiMo-V2-Omni',
			'minimax-m3' => 'MiniMax-M3',
			'minimax-m2.7' => 'MiniMax-M2.7',
			'minimax-m2.5' => 'MiniMax-M2.5',
			'muse-spark-1.3-contributor' => 'Muse-Spark-1.3-Contributor',
			'muse-spark-1.2-contributor' => 'Muse-Spark-1.2-Contributor',
			'qwen3.8-max' => 'Qwen3.8-Max',
			'qwen3.8-flash' => 'Qwen3.8-Flash',
			'qwen3.7-max' => 'Qwen3.7-Max',
			'qwen3.7-plus' => 'Qwen3.7-Plus',
			'qwen3.6-plus' => 'Qwen3.6-Plus',
			'qwen3.5-plus' => 'Qwen3.5-Plus',
			'space-bunny' => 'Space-Bunny',
			'step-5-preview-free' => 'Step-5-Preview-Free',
			'cat-jam-free' => 'Cat-Jam-Free',
			'hy4-preview' => 'Hy4-Preview',
			'hy3' => 'Hy3',
			'hy3-preview' => 'Hy3-Preview',
			'omen-alpha' => 'Omen-Alpha',
		];

		// If the ID is in our map, use the pretty name; otherwise auto-format it.
		return $map[ $id ] ?? ucwords( str_replace( ['-', '_'], ' ', $id ) );
	}

	/**
	 * Sorting callback used with usort() to order models in a sensible way.
	 *
	 * Known flagship models (pro, flash) are bumped to the top; everything
	 * else is sorted alphabetically.
	 *
	 * @since 1.0.0
	 * @param ModelMetadata $a First model to compare.
	 * @param ModelMetadata $b Second model to compare.
	 * @return int Negative if $a comes first, positive if $b comes first.
	 */
	protected function modelSortCallback( ModelMetadata $a, ModelMetadata $b ): int {

		// Grab the raw IDs so we can look them up in the priority list.
		$aId = $a->getId();
		$bId = $b->getId();

		// Lower number = higher priority. Unknown models default to 99 (bottom).
		$priority = [
			'deepseek-v4-pro' => 1,
			'gpt-6-luna' => 2,
			'claude-haiku-5-5' => 3,
			'gemini-4-argon-contributor' => 4,
			'grok-4.7' => 5,
			'kimi-k3' => 6,
			'qwen3.8-max' => 7,
			'glm-5.3' => 8,
			'minimax-m3' => 9,
			'gpt-5.6-luna' => 10,
			'grok-4.6' => 11,
			'qwen3.8-flash' => 12,
			'deepseek-v4.1-flash' => 13,
			'glm-5.3-flash' => 14,
			'qwen3.7-plus' => 15,
			'qwen3.7-max' => 16,
			'qwen3.6-plus' => 17,
			'kimi-k2.7-code' => 18,
			'kimi-k2.6' => 19,
			'glm-5.2' => 20,
			'glm-5.1' => 21,
			'minimax-m2.7' => 22,
			'minimax-m2.5' => 23,
			'mimo-v2.6-pro' => 24,
			'mimo-v2.6-flash' => 25,
			'mimo-v2.5-pro' => 26,
			'mimo-v2.5' => 27,
			'hy4-preview' => 28,
			'hy3' => 29,
			'muse-spark-1.3-contributor' => 30,
			'muse-spark-1.2-contributor' => 31,
			'space-bunny' => 32,
			'longcat-2.0' => 33,
			'longcat-2.5-preview-free' => 34,
			'step-5-preview-free' => 35,
			'cat-jam-free' => 36,
			'omen-alpha' => 37,
		];

		$aPriority = $priority[ $aId ] ?? 99;
		$bPriority = $priority[ $bId ] ?? 99;

		// First, sort by our manual priority tier.
		if ( $aPriority !== $bPriority ) {
			// The spaceship operator returns -1, 0, or 1 — perfect for usort.
			return $aPriority <=> $bPriority;
		}

		// Within the same tier, fall back to alphabetical order.
		return strcmp( $aId, $bId );
	}
}
