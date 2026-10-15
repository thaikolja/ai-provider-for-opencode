<?php

declare( strict_types = 1 );

namespace Nominal\AIProviderOpenCode\Models;

// We need the provider to construct the full API URL for requests.
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;
// Rewrites images the upstream API cannot accept into a supported format.
use Nominal\AIProviderOpenCode\Support\Nominal_AIPO_OpenCodeImageConverter;
// Building blocks for the message payload and HTTP request the base class does the real work for.
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Represents a text generation model backed by OpenCode's API.
 *
 * Extends the SDK's abstract class, which means all the chat completions
 * plumbing is already done. We override two methods: one to build the HTTP
 * request pointed at our own API base URL, and one to convert images the
 * upstream API would otherwise reject.
 *
 * @since 1.0.0
 */
class Nominal_AIPO_OpenCodeTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * Builds an HTTP request bound to the OpenCode API.
	 *
	 * The only difference from the parent is that we use Nominal_AIPO_OpenCodeProvider::url()
	 * to construct the full URL, we attach the OpenCode session headers, and we pass
	 * along any request options (like timeouts) that were set on this model instance.
	 *
	 * @since 1.0.0
	 * @param HttpMethodEnum $method GET or POST.
	 * @param string $path API endpoint path (e.g. "/chat/completions").
	 * @param array $headers Additional HTTP headers.
	 * @param mixed $data The request body for POST calls.
	 * @return Request A fully built request ready to dispatch.
	 */
	protected function createRequest(
		HttpMethodEnum $method, string $path, array $headers = [], $data = null
	): Request {

		// Merge in the OpenCode-specific headers (currently the session ID).
		$headers = array_merge( $headers, Nominal_AIPO_OpenCodeProvider::requestHeaders() );

		// Nominal_AIPO_OpenCodeProvider::url() handles gluing the base URL to the relative path.
		return new Request(
			$method,
			Nominal_AIPO_OpenCodeProvider::url( $path ),
			$headers,
			$data,
			$this->getRequestOptions() // Timeouts, retries, etc. set on this model.
		);
	}

	/**
	 * Builds the content payload for a single message part.
	 *
	 * The parent forwards images untouched, but OpenCode's upstream providers only
	 * accept webp, png, jpeg and gif. When the part is an image in another format
	 * (or one labelled with the wrong MIME type), it is re-encoded here before the
	 * request goes out.
	 *
	 * @since 1.1.0
	 * @param MessagePart $part The message part to build content data for.
	 * @return array<string, mixed>|null The content data, or null when not applicable.
	 */
	protected function getMessagePartContentData( MessagePart $part ): ?array {
		$contentData = parent::getMessagePartContentData( $part );

		if ( ! is_array( $contentData ) || 'image_url' !== ( $contentData['type'] ?? null ) ) {
			return $contentData;
		}

		$file = $part->getFile();
		if ( ! $file instanceof File || ! $file->isImage() ) {
			return $contentData;
		}

		$dataUri = Nominal_AIPO_OpenCodeImageConverter::toSupportedDataUri( $file );
		if ( null === $dataUri ) {
			return $contentData;
		}

		$contentData['image_url'] = [ 'url' => $dataUri ];

		return $contentData;
	}
}
