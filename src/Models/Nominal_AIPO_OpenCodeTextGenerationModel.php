<?php
/**
 * AI Provider for OpenCode.
 *
 * Connects OpenCode to WordPress.
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
 * Text model for OpenCode.
 *
 * @package Nominal\AIProviderOpenCode
 */

// Keep type checks strict.
declare( strict_types=1 );

// This class lives with the other model code.
namespace Nominal\AIProviderOpenCode\Models;

// Builds the OpenCode address and shared headers.
use Nominal\AIProviderOpenCode\Provider\Nominal_AIPO_OpenCodeProvider;

// One message in the conversation.
use WordPress\AiClient\Messages\DTO\Message;

// User, tool, or model. Only model turns carry reasoning.
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

// The request sent to OpenCode.
use WordPress\AiClient\Providers\Http\DTO\Request;

// Timeout and the other transport settings for one request.
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

// GET or POST.
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

// Shared text-generation behavior for an OpenAI-compatible API.
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Generates text with one OpenCode model.
 *
 * Each request goes to OpenCode, waits long enough for a reply, and sends
 * a thinking model's notes back when tools are involved.
 *
 * @since 1.0.0
 */
class Nominal_AIPO_OpenCodeTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * Builds one request to OpenCode.
	 *
	 * The OpenCode address and shared headers are added here. A timeout the
	 * caller already set is kept. An empty one becomes 120 seconds.
	 *
	 * @param HttpMethodEnum                     $method  GET or POST.
	 * @param string                             $path    Path after the OpenCode address.
	 * @param array<string, string|list<string>> $headers Extra headers, if the caller set any.
	 * @param string|array<string, mixed>|null   $data    Body for a POST request.
	 *
	 * @return Request The request ready to send.
	 * @since  1.0.0
	 */
	final protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = array(),
		$data = null
	): Request {
		// Point the call at OpenCode, with headers and a timeout.
		return new Request(
			$method,
			Nominal_AIPO_OpenCodeProvider::url( $path ),
			Nominal_AIPO_OpenCodeProvider::applyRequestHeaders( $headers ),
			$data,
			$this->generationRequestOptions()
		);
	}

	/**
	 * Timeout and other transport settings for one text request.
	 *
	 * The caller's settings are copied, so the object stored on the model
	 * stays as it was. An empty timeout becomes 120 seconds. WordPress would
	 * otherwise stop at about 5 seconds. A site can replace the timeout with
	 * the nominal_aipo_opencode_request_timeout filter. A non-numeric or
	 * negative return value is ignored.
	 *
	 * @return RequestOptions Settings for this request.
	 * @since  1.1.2
	 */
	private function generationRequestOptions(): RequestOptions {
		// Settings the caller already stored, if any.
		$stored = $this->getRequestOptions();

		// Copy them so this request does not change the stored object.
		$options = null === $stored ? new RequestOptions() : clone $stored;

		// The wait the caller chose. Empty means they did not choose one.
		$timeout = $options->getTimeout();

		// Give a request with no timeout two minutes.
		if ( null === $timeout ) {
			// Long enough for a short thinking-model reply.
			$timeout = 120.0;
		}

		// Filters exist when WordPress is loaded, not in every Composer script.
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters how many seconds one OpenCode text request may run.
			 *
			 * The value is the caller's timeout, or 120 when the caller did not set one.
			 * Return a non-numeric or negative value to keep that timeout.
			 *
			 * @since 1.1.2
			 *
			 * @param float  $timeout  Timeout in seconds.
			 * @param string $model_id OpenCode model id for this request.
			 */
			// Pass the current timeout and this model's id.
			$filtered = apply_filters(
				'nominal_aipo_opencode_request_timeout',
				$timeout,
				$this->metadata()->getId()
			);

			// Keep the old timeout when the return value is not a real wait time.
			if ( is_numeric( $filtered ) && (float) $filtered >= 0 ) {
				// Use the timeout the site asked for.
				$timeout = (float) $filtered;
			}
		}

		// Store the wait on this request only.
		$options->setTimeout( $timeout );

		// These settings go out with the request.
		return $options;
	}

	/**
	 * Puts a thinking model's notes back on earlier model messages.
	 *
	 * Some models expect those notes on the next tool turn. The AI Client
	 * already read them. This sends them back.
	 *
	 * @param array<int, mixed> $prompt Messages for this turn.
	 *
	 * @return array<string, mixed> The request body.
	 * @since  1.1.0
	 */
	final public function prepareGenerateTextParams( array $prompt ): array {
		// Let the AI Client build the normal request first.
		$params = parent::prepareGenerateTextParams( $prompt );

		// Nothing to adjust when there is no message list.
		if ( ! isset( $params['messages'] ) || ! is_array( $params['messages'] ) ) {
			// Send the result unchanged.
			return $params;
		}

		// Put each model's earlier notes back on its message.
		$params['messages'] = self::withReasoningContent(
			$params['messages'],
			self::collectModelThoughts( $prompt )
		);

		// The request body, with those notes included.
		return $params;
	}

	/**
	 * Collects the notes from each model turn.
	 *
	 * Empty notes stay in the list so later turns still line up.
	 * A message that is only a tool result is not a model turn here.
	 *
	 * @param array<int, mixed> $prompt Messages for this turn.
	 *
	 * @return array<int, string> Notes, in the same order as the model turns.
	 * @since  1.1.0
	 */
	private static function collectModelThoughts( array $prompt ): array {
		// Notes found so far.
		$thoughts = [];

		// Look through the conversation in order.
		foreach ( $prompt as $message ) {
			// User and tool messages do not carry these notes.
			if ( ! self::isModelTurn( $message ) ) {
				// This entry has nothing to collect.
				continue;
			}

			// Keep this turn's notes, even when they are empty.
			$thoughts[] = self::thoughtText( $message );
		}

		// One entry per model turn.
		return $thoughts;
	}

	/**
	 * Whether this message is a model turn that can carry notes.
	 *
	 * @param mixed $message One entry from the conversation.
	 *
	 * @return bool Whether this entry should be paired with model notes.
	 * @since  1.1.0
	 */
	private static function isModelTurn( $message ): bool {
		// Anything that is not a message is ignored.
		if ( ! $message instanceof Message ) {
			// Not a model turn.
			return false;
		}

		// Only the model's own messages carry the notes.
		if ( $message->getRole() !== MessageRoleEnum::model() ) {
			// A user or tool message.
			return false;
		}

		// A lone tool result is not one of those turns.
		return ! self::isLoneFunctionResponse( $message );
	}

	/**
	 * Whether the message is only a tool result.
	 *
	 * @param Message $message A model message.
	 *
	 * @return bool Whether the message is a single tool result.
	 * @since  1.1.0
	 */
	private static function isLoneFunctionResponse( Message $message ): bool {
		// The pieces that make up the message.
		$parts = $message->getParts();

		// One piece, and that piece is a tool result.
		return 1 === count( $parts ) && $parts[0]->getType()->isFunctionResponse();
	}

	/**
	 * Joins the thought text from one model message.
	 *
	 * @param Message $message A model message.
	 *
	 * @return string The notes, or an empty string when there are none.
	 * @since  1.1.0
	 */
	private static function thoughtText( Message $message ): string {
		// Start with nothing.
		$thought = '';

		// Walk each piece of the message.
		foreach ( $message->getParts() as $part ) {
			// Keep only text that was marked as a thought.
			if ( ! $part->getType()->isText() || ! $part->getChannel()->isThought() ) {
				// Ordinary text, or something else.
				continue;
			}

			// Add this thought to the rest.
			$thought .= $part->getText();
		}

		// Everything the model noted, as one string.
		return $thought;
	}

	/**
	 * Copies notes onto the outgoing model messages.
	 *
	 * @param array<int, mixed>  $messages Outgoing messages.
	 * @param array<int, string> $thoughts Notes, in model-turn order.
	 *
	 * @return array<int, mixed> Messages, with notes where we had them.
	 * @since  1.1.0
	 */
	private static function withReasoningContent( array $messages, array $thoughts ): array {
		// The first model turn uses the first note.
		$index = 0;

		// Pair each outgoing message with the next note.
		foreach ( $messages as $wire_index => $entry ) {
			// Attach a note, or learn that we should stop.
			$next = self::entryWithThought( $entry, $thoughts, $index );

			// Ran out of notes on a model turn.
			if ( null === $next ) {
				// Leave the remaining messages untouched.
				break;
			}

			// Save the updated message.
			$messages[ $wire_index ] = $next['entry'];

			// The next model turn uses the following note.
			$index = $next['index'];
		}

		// Messages, with notes put back where we had them.
		return $messages;
	}

	/**
	 * Applies the next note to one outgoing message.
	 *
	 * Returns null when a model turn has no note left. That keeps later
	 * turns from being paired with the wrong text.
	 *
	 * @param mixed              $entry    One outgoing message.
	 * @param array<int, string> $thoughts Notes, in model-turn order.
	 * @param int                $index    Which note to use next.
	 *
	 * @return array{entry: mixed, index: int}|null The updated message and next index, or null to stop.
	 * @since  1.1.0
	 */
	private static function entryWithThought( $entry, array $thoughts, int $index ): ?array {
		// User and tool messages do not carry a note.
		if ( ! self::isAssistantEntry( $entry ) ) {
			// Leave the message as it is and keep the same note index.
			return [
				'entry' => $entry,
				'index' => $index,
			];
		}

		// No note left for this model turn.
		if ( ! isset( $thoughts[ $index ] ) ) {
			// Stop, so later turns are not paired with the wrong text.
			return null;
		}

		// Take this note.
		$thought = $thoughts[ $index ];

		// The next model turn uses the following note.
		++$index;

		// Skip an empty note. The model did not send any.
		if ( '' !== $thought ) {
			// Put the notes back on the model message.
			$entry['reasoning_content'] = $thought;
		}

		// The message, and where the next note starts.
		return [
			'entry' => $entry,
			'index' => $index,
		];
	}

	/**
	 * Whether an outgoing message is from the model.
	 *
	 * @param mixed $entry One outgoing message.
	 *
	 * @return bool Whether the message is a model turn.
	 * @since  1.1.0
	 */
	private static function isAssistantEntry( $entry ): bool {
		// A model message is an array whose role is assistant.
		return is_array( $entry ) && isset( $entry['role'] ) && 'assistant' === $entry['role'];
	}
}
