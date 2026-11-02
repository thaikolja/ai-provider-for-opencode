<?php
/**
 * AI Provider for OpenCode.
 *
 * Connects OpenCode models to WordPress' native AI SDK.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Released under the GNU General Public License v2 or later.
 * See: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package    Nominal\AIProviderOpenCode
 * @author     Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright  2026 (C) Kolja Nolte
 * @license    GPL-2.0-or-later
 * @link       https://docs.kolja-nolte.com/opencode-wordpress-ai-provider
 */

/**
 * Text generation model for the OpenCode provider.
 *
 * @package Nominal\AIProviderOpenCode
 */

declare( strict_types=1 );

namespace Nominal\AIProviderOpenCode\Models;

// We need the provider to construct the full API URL for requests.
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;

// Message types used when a thinking model has to send reasoning back.
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

// Building blocks for HTTP requests and the abstract base that does the real work.
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Represents a text generation model backed by OpenCode's API.
 *
 * Extends the SDK's abstract class, which means all the chat completions
 * plumbing is already done. We point requests at the OpenCode Go URL, send the
 * session header and a plugin user agent, and pass thinking-model reasoning
 * back on tool turns.
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
	 * @param  HttpMethodEnum                      $method   GET or POST.
	 * @param  string                              $path     API endpoint path (for example "/chat/completions").
	 * @param  array<string, string|list<string>>  $headers  Additional HTTP headers.
	 * @param  string|array<string, mixed>|null    $data     The request body for POST calls.
	 *
	 * @return Request                             A fully built request ready to dispatch.
	 * @since  1.0.0
	 */
	final protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = array(),
		$data = null
	): Request {
		return new Request(
			$method,
			Nominal_AIPO_OpenCodeProvider::url( $path ),
			Nominal_AIPO_OpenCodeProvider::applyRequestHeaders( $headers ),
			$data,
			$this->getRequestOptions()
		);
	}

	/**
	 * Re-attaches `reasoning_content` to prior assistant turns.
	 *
	 * Thinking models on the OpenAI-compatible Go path expect the same
	 * round-trip DeepSeek documents for tool calls. The SDK already parses
	 * `reasoning_content` into thought parts. This sends those parts back.
	 *
	 * @param  array<int, mixed>  $prompt  Prompt messages for this turn.
	 *
	 * @return array<string, mixed>  Chat-completions parameters.
	 * @since  1.1.0
	 */
	final public function prepareGenerateTextParams( array $prompt ): array {
		$params = parent::prepareGenerateTextParams( $prompt );

		if ( ! isset( $params['messages'] ) || ! is_array( $params['messages'] ) ) {
			return $params;
		}

		$params['messages'] = self::withReasoningContent(
			$params['messages'],
			self::collectModelThoughts( $prompt )
		);

		return $params;
	}

	/**
	 * Thought text for each model turn that is not a lone function response.
	 *
	 * Empty strings stay in the list so assistant turns keep their alignment.
	 *
	 * @param  array<int, mixed>  $prompt  Prompt messages for this turn.
	 *
	 * @return array<int, string>  Thought strings aligned with model turns.
	 * @since  1.1.0
	 */
	private static function collectModelThoughts( array $prompt ): array {
		$thoughts = [];

		foreach ( $prompt as $message ) {
			if ( ! self::isModelTurn( $message ) ) {
				continue;
			}

			$thoughts[] = self::thoughtText( $message );
		}

		return $thoughts;
	}

	/**
	 * True when the entry is a model message that should carry reasoning.
	 *
	 * @param  mixed  $message  Candidate prompt entry.
	 *
	 * @return bool   Whether this entry maps to an assistant thought.
	 * @since  1.1.0
	 */
	private static function isModelTurn( $message ): bool {
		if ( ! $message instanceof Message ) {
			return false;
		}

		if ( $message->getRole() !== MessageRoleEnum::model() ) {
			return false;
		}

		return ! self::isLoneFunctionResponse( $message );
	}

	/**
	 * True when the model message is only a function response.
	 *
	 * @param  Message  $message  Model message to inspect.
	 *
	 * @return bool     Whether the message is a single function response.
	 * @since  1.1.0
	 */
	private static function isLoneFunctionResponse( Message $message ): bool {
		$parts = $message->getParts();

		return 1 === count( $parts ) && $parts[0]->getType()->isFunctionResponse();
	}

	/**
	 * Concatenates thought-channel text from a model message.
	 *
	 * @param  Message  $message  Model message to read.
	 *
	 * @return string   Thought text, or an empty string when there is none.
	 * @since  1.1.0
	 */
	private static function thoughtText( Message $message ): string {
		$thought = '';

		foreach ( $message->getParts() as $part ) {
			if ( ! $part->getType()->isText() || ! $part->getChannel()->isThought() ) {
				continue;
			}

			$thought .= $part->getText();
		}

		return $thought;
	}

	/**
	 * Copies thought text onto assistant wire messages.
	 *
	 * @param  array<int, mixed>   $messages  Wire messages.
	 * @param  array<int, string>  $thoughts  Thought text aligned with assistant turns.
	 *
	 * @return array<int, mixed>   Wire messages, with reasoning_content where a thought exists.
	 * @since  1.1.0
	 */
	private static function withReasoningContent( array $messages, array $thoughts ): array {
		$index = 0;

		foreach ( $messages as $wire_index => $entry ) {
			$next = self::entryWithThought( $entry, $thoughts, $index );

			if ( null === $next ) {
				break;
			}

			$messages[ $wire_index ] = $next['entry'];
			$index                   = $next['index'];
		}

		return $messages;
	}

	/**
	 * Applies the next thought to one wire message.
	 *
	 * Returns null when the thought list is exhausted on an assistant turn.
	 *
	 * @param  mixed               $entry     One decoded wire message.
	 * @param  array<int, string>  $thoughts  Thought text aligned with assistant turns.
	 * @param  int                 $index     Next thought index.
	 *
	 * @return array{entry: mixed, index: int}|null Updated entry and index, or null to stop.
	 * @since  1.1.0
	 */
	private static function entryWithThought( $entry, array $thoughts, int $index ): ?array {
		if ( ! self::isAssistantEntry( $entry ) ) {
			return [
				'entry' => $entry,
				'index' => $index,
			];
		}

		if ( ! isset( $thoughts[ $index ] ) ) {
			return null;
		}

		$thought = $thoughts[ $index ];
		++$index;

		if ( '' !== $thought ) {
			$entry['reasoning_content'] = $thought;
		}

		return [
			'entry' => $entry,
			'index' => $index,
		];
	}

	/**
	 * True when a wire message is an assistant turn.
	 *
	 * @param  mixed  $entry  One decoded wire message.
	 *
	 * @return bool   Whether the entry is an assistant message.
	 * @since  1.1.0
	 */
	private static function isAssistantEntry( $entry ): bool {
		return is_array( $entry ) && isset( $entry['role'] ) && 'assistant' === $entry['role'];
	}
}
