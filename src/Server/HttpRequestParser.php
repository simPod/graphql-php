<?php declare(strict_types=1);

namespace GraphQL\Server;

/**
 * Parses strict GraphQL-over-HTTP requests.
 *
 * @internal
 */
final class HttpRequestParser
{
    /**
     * @param array<mixed> $queryParams
     *
     * @throws HttpRequestError
     */
    public function parse(
        string $method,
        string $contentType,
        string $body,
        array $queryParams
    ): OperationParams {
        if ($method === 'GET') {
            return $this->parseParameters($queryParams, true);
        }

        if ($method !== 'POST') {
            throw new HttpRequestError("HTTP method \"{$method}\" is not supported", 405, ['Allow' => 'GET, POST']);
        }

        if ($contentType === '') {
            throw new HttpRequestError('Missing "Content-Type" header', 415);
        }

        $this->assertSupportedContentType($contentType);

        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new HttpRequestError('Invalid JSON request body', 400);
        }

        if (! $decoded instanceof \stdClass) {
            throw new HttpRequestError('The JSON request body must be an object', 422);
        }

        return $this->parseParameters((array) $decoded, false);
    }

    /**
     * @param array<mixed> $parameters
     *
     * @throws HttpRequestError
     */
    private function parseParameters(array $parameters, bool $get): OperationParams
    {
        if (! array_key_exists('query', $parameters) || ! is_string($parameters['query'])) {
            throw new HttpRequestError('The "query" parameter must be a string', 422);
        }

        $query = $parameters['query'];
        $operation = $parameters['operationName'] ?? null;
        if ($get && $operation === '') {
            $operation = null;
        }

        if ($operation !== null && ! is_string($operation)) {
            throw new HttpRequestError('The "operationName" parameter must be a string', 422);
        }

        $variables = $this->parseMapParameter($parameters, 'variables', $get);
        $extensions = $this->parseMapParameter($parameters, 'extensions', $get);

        $originalInput = ['query' => $query];
        if ($operation !== null) {
            $originalInput['operationName'] = $operation;
        }

        if ($variables !== null) {
            $originalInput['variables'] = $variables;
        }

        if ($extensions !== null) {
            $originalInput['extensions'] = $extensions;
        }

        $operationParams = new OperationParams();
        $operationParams->query = $query;
        $operationParams->queryId = null;
        $operationParams->operation = $operation;
        $operationParams->variables = $variables;
        $operationParams->extensions = $extensions;
        $operationParams->readOnly = $get;
        $operationParams->originalInput = $originalInput;

        return $operationParams;
    }

    /**
     * @param array<mixed> $parameters
     *
     * @throws HttpRequestError
     *
     * @return array<string, mixed>|null
     */
    private function parseMapParameter(array $parameters, string $name, bool $get): ?array
    {
        if (! array_key_exists($name, $parameters)) {
            return null;
        }

        $value = $parameters[$name];
        if ($get && $value === '') {
            return null;
        }

        if (! $get && $value === null) {
            return null;
        }

        if ($get) {
            if (! is_string($value)) {
                throw new HttpRequestError("The \"{$name}\" parameter must be a JSON object string", 422);
            }

            try {
                $value = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $error) {
                throw new HttpRequestError("The \"{$name}\" parameter contains invalid JSON", 422);
            }
        }

        if (! $value instanceof \stdClass) {
            throw new HttpRequestError("The \"{$name}\" parameter must be an object", 422);
        }

        // Coercion already supports stdClass input objects. Keep nested JSON
        // objects intact, including objects with numeric keys and empty objects.
        return (array) $value;
    }

    /** @throws HttpRequestError */
    private function assertSupportedContentType(string $contentType): void
    {
        $parts = explode(';', $contentType);
        if (strcasecmp(trim($parts[0]), 'application/json') !== 0) {
            throw new HttpRequestError('Unsupported request Content-Type', 415);
        }

        $charsetFound = false;
        foreach (array_slice($parts, 1) as $parameter) {
            $parameterParts = explode('=', $parameter, 2);
            if (count($parameterParts) !== 2 || strcasecmp(trim($parameterParts[0]), 'charset') !== 0) {
                continue;
            }

            if ($charsetFound) {
                throw new HttpRequestError('Unsupported request charset', 415);
            }

            $charsetFound = true;
            $charset = trim(trim($parameterParts[1]), " \t\n\r\0\x0B\"");
            if (strcasecmp($charset, 'utf-8') !== 0) {
                throw new HttpRequestError('Unsupported request charset', 415);
            }
        }
    }
}
