<?php declare(strict_types=1);

namespace GraphQL\Tests\Server;

use GraphQL\Server\HttpRequestError;
use GraphQL\Server\HttpRequestParser;
use PHPUnit\Framework\TestCase;

final class HttpRequestParserTest extends TestCase
{
    public function testParsesGetAndPostWithoutLegacyAliases(): void
    {
        $parser = new HttpRequestParser();
        $get = $parser->parse('GET', '', '', [
            'query' => '',
            'operationName' => '',
            'variables' => '{"input":{"items":[],"empty":{},"numeric":{"0":"zero"}}}',
            'extensions' => '',
            'Query' => '{ ignored }',
            'queryId' => 'legacy-id',
        ]);
        $post = $parser->parse(
            'POST',
            'Application/JSON; charset="UTF-8"',
            '{"query":"{ value }","operationName":"","variables":{"input":{"items":[],"empty":{},"numeric":{"0":"zero"}}},"extensions":{"persistedQuery":{"sha256Hash":"hash"}},"queryId":"legacy-id"}',
            []
        );

        self::assertSame('', $get->query);
        self::assertNull($get->operation);
        self::assertSame('{"input":{"items":[],"empty":{},"numeric":{"0":"zero"}}}', json_encode($get->variables, JSON_THROW_ON_ERROR));
        self::assertNull($get->extensions);
        self::assertNull($get->queryId);
        self::assertTrue($get->readOnly);
        self::assertSame(['query' => '', 'variables' => $get->variables], $get->originalInput);

        self::assertSame('{ value }', $post->query);
        self::assertSame('', $post->operation);
        self::assertSame('{"input":{"items":[],"empty":{},"numeric":{"0":"zero"}}}', json_encode($post->variables, JSON_THROW_ON_ERROR));
        self::assertSame('{"persistedQuery":{"sha256Hash":"hash"}}', json_encode($post->extensions, JSON_THROW_ON_ERROR));
        self::assertNull($post->queryId);
        self::assertFalse($post->readOnly);
        self::assertSame('', $post->originalInput['operationName']);
    }

    public function testAcceptsEmptyObjectForVariables(): void
    {
        $parsed = (new HttpRequestParser())->parse(
            'POST',
            'application/json',
            '{"query":"{ value }","variables":{}}',
            []
        );

        self::assertSame([], $parsed->variables);
    }

    /**
     * @dataProvider provideInvalidRequests
     *
     * @param array<string, mixed> $queryParams
     */
    public function testRejectsInvalidRequests(
        string $method,
        string $contentType,
        string $body,
        array $queryParams,
        int $statusCode,
        ?string $allowHeader = null
    ): void {
        try {
            (new HttpRequestParser())->parse($method, $contentType, $body, $queryParams);
            self::fail('Expected an HTTP request parsing error');
        } catch (HttpRequestError $error) {
            self::assertSame($statusCode, $error->statusCode);
            self::assertSame(
                $allowHeader === null ? [] : ['Allow' => $allowHeader],
                $error->headers
            );
        }
    }

    /** @return \Generator<string, array{string, string, string, array<string, mixed>, int, ?string}> */
    public static function provideInvalidRequests(): \Generator
    {
        yield 'unsupported method' => ['PUT', '', '', [], 405, 'GET, POST'];
        yield 'missing content type' => ['POST', '', '{}', [], 415, null];
        yield 'unsupported media type' => ['POST', 'application/jsonp', '{}', [], 415, null];
        yield 'unsupported charset' => ['POST', 'application/json; charset=iso-8859-1', '{}', [], 415, null];
        yield 'invalid JSON body' => ['POST', 'application/json', '{"query":', [], 400, null];
        yield 'array body is not an object' => ['POST', 'application/json', '[]', [], 422, null];
        yield 'missing query' => ['POST', 'application/json', '{"Query":"{ value }"}', [], 422, null];
        yield 'query must be string' => ['GET', '', '', ['query' => ['{ value }']], 422, null];
        yield 'operation name must be a string' => ['POST', 'application/json', '{"query":"{ value }","operationName":3}', [], 422, null];
        yield 'variables must be map on post' => [
            'POST',
            'application/json',
            '{"query":"{ value }","variables":[]}',
            [],
            422,
            null,
        ];
        yield 'variables must be JSON object on get' => [
            'GET',
            '',
            '',
            ['query' => '{ value }', 'variables' => '[]'],
            422,
            null,
        ];
        yield 'extensions must be a map' => ['POST', 'application/json', '{"query":"{ value }","extensions":[]}', [], 422, null];
        yield 'post variables must not be an encoded string' => ['POST', 'application/json', '{"query":"{ value }","variables":"{}"}', [], 422, null];
        yield 'malformed variables JSON on get' => [
            'GET',
            '',
            '',
            ['query' => '{ value }', 'variables' => '{'],
            422,
            null,
        ];
    }
}
