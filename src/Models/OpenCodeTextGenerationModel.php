<?php

declare(strict_types=1);

namespace WordPress\OpenCodeAiProvider\Models;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use WordPress\OpenCodeAiProvider\Provider\OpenCodeProvider;

/**
 * Class for an OpenCode text generation model using the OpenAI-compatible chat completions API.
 *
 * @since 1.0.0
 */
class OpenCodeTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    /**
     * {@inheritDoc}
     *
     * @since 1.0.0
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            OpenCodeProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }
}
