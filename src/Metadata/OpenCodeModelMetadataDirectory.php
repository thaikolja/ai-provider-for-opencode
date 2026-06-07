<?php

declare( strict_types = 1 );

namespace WordPress\OpenCodeAiProvider\Metadata;

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
// We need the provider to build full API URLs (base URL + path).
use WordPress\OpenCodeAiProvider\Provider\OpenCodeProvider;

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
class OpenCodeModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * Builds a proper HTTP request pointed at the OpenCode API.
	 *
	 * Takes the relative path (like "/models") and prepends the full base URL.
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

		// OpenCodeProvider::url() glues the base URL and the path together for us.
		return new Request( $method, OpenCodeProvider::url( $path ), $headers, $data );
	}

	/**
	 * Takes the raw /models API response and turns it into a list of model metadata.
	 *
	 * This is the core of the discovery process. We parse the JSON, build a
	 * standard set of options every model gets, and wrap everything up as
	 * ModelMetadata objects.
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
		// We keep it broad — most models can handle these, and it avoids
		// conditional logic for models that don't expose fine-grained capabilities.
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
				OptionEnum::inputModalities(),
				[ [ ModalityEnum::text() ] ]
			),
			new SupportedOption(
				OptionEnum::outputModalities(),
				[ [ ModalityEnum::text() ] ]
			),
		];

		// Cast to array just in case the API returned something unexpected.
		$modelsData = (array) $responseData['data'];

		// Transform each raw model entry from the API into a ModelMetadata object.
		$models = array_map(
			function ( array $modelData ) use ( $baseTextOptions ): ModelMetadata {

				// The model ID is the canonical slug (e.g. "deepseek-v4-pro").
				$modelId = $modelData['id'];

				// Every model gets text generation + chat history. We don't do
				// fine-grained capability checks here — it's simpler and covers
				// everything OpenCode currently offers.
				return new ModelMetadata(
					$modelId,
					self::formatDisplayName( $modelId ),
					[CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()],
					$baseTextOptions
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
			'deepseek-v4-flash' => 'DeepSeek-V4-Flash',
			'glm-5.1' => 'GLM-5.1',
			'glm-5' => 'GLM-5',
			'hy3-preview' => 'Hy3 Preview',
			'kimi-k2.6' => 'Kimi-K2.6',
			'kimi-k2.5' => 'Kimi-K2.5',
			'mimo-v2-pro' => 'Mimo-V2-Pro',
			'mimo-v2-omni' => 'Mimo-V2-Omni',
			'mimo-v2.5-pro' => 'Mimo-V2.5-Pro',
			'mimo-v2.5' => 'Mimo-V2.5',
			'minimax-m3' => 'MiniMax-M3',
			'minimax-m2.7' => 'MiniMax-M2.7',
			'minimax-m2.5' => 'MiniMax-M2.5',
			'qwen3.7-max' => 'Qwen3.7-Max',
			'qwen3.7-plus' => 'Qwen3.7-Plus',
			'qwen3.6-plus' => 'Qwen3.6-Plus',
			'qwen3.5-plus' => 'Qwen3.5-Plus',
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
			'deepseek-v4-flash' => 2,
			'qwen3.7-max' => 3,
			'qwen3.7-plus' => 4,
			'qwen3.6-plus' => 5,
			'qwen3.5-plus' => 6,
			'glm-5.1' => 7,
			'glm-5' => 8,
			'kimi-k2.6' => 9,
			'kimi-k2.5' => 10,
			'minimax-m3' => 11,
			'minimax-m2.7' => 12,
			'minimax-m2.5' => 13,
			'mimo-v2-pro' => 14,
			'mimo-v2-omni' => 15,
			'mimo-v2.5-pro' => 16,
			'mimo-v2.5' => 17,
			'hy3-preview' => 18,
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
