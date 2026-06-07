<?php

declare( strict_types = 1 );

namespace WordPress\OpenCodeAiProvider\Models;

// Building blocks for HTTP requests and the abstract base that does the real work.
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
// We need the provider to construct the full API URL for requests.
use WordPress\OpenCodeAiProvider\Provider\OpenCodeProvider;

/**
 * Represents a text generation model backed by OpenCode's API.
 *
 * Extends the SDK's abstract class, which means all the chat completions
 * plumbing is already done. We only need to override one method: building
 * the HTTP request pointed at our own API base URL.
 *
 * @since 1.0.0
 */
class OpenCodeTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * Builds an HTTP request bound to the OpenCode API.
	 *
	 * The only difference from the parent is that we use OpenCodeProvider::url()
	 * to construct the full URL, and we pass along any request options (like
	 * timeouts) that were set on this model instance.
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

		// OpenCodeProvider::url() handles gluing the base URL to the relative path.
		return new Request(
			$method,
			OpenCodeProvider::url( $path ),
			$headers,
			$data,
			$this->getRequestOptions() // Timeouts, retries, etc. set on this model.
		);
	}
}
