<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Cdn;

use GuzzleHttp\ClientInterface;
use Pimcore\Cdn\FastlyPurgeClient;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class FastlyPurgeClientTest extends TestCase
{
    private const API_TOKEN = 'test-token';

    private const SERVICE_ID = 'svc123';

    private const API_BASE_URL = 'https://api.fastly.com';

    private ClientInterface $httpClient;

    private LoggerInterface $logger;

    private FastlyPurgeClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = new FastlyPurgeClient(
            $this->httpClient,
            $this->logger,
            self::API_TOKEN,
            self::SERVICE_ID,
            self::API_BASE_URL,
        );
    }

    private function mockResponse(int $statusCode): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);

        return $response;
    }

    public function testPurgeByTagSendsPostToCorrectUrl(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.fastly.com/service/' . self::SERVICE_ID . '/purge/asset-42',
                $this->callback(fn (array $opts) => ($opts['headers']['Fastly-Key'] ?? null) === self::API_TOKEN),
            )
            ->willReturn($this->mockResponse(200));

        $this->client->purgeByTag('asset-42');
    }

    public function testPurgeByTagsSendsBatchPostWithSurrogateKeyHeader(): void
    {
        $tags = ['asset-1', 'thumb-product', 'asset-1-thumb-product'];

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.fastly.com/service/' . self::SERVICE_ID . '/purge',
                $this->callback(function (array $opts) use ($tags) {
                    $headerValue = $opts['headers']['Surrogate-Key'] ?? null;

                    return $headerValue === implode(' ', $tags)
                        && ($opts['headers']['Fastly-Key'] ?? null) === self::API_TOKEN;
                }),
            )
            ->willReturn($this->mockResponse(200));

        $this->client->purgeByTags($tags);
    }

    public function testPurgeByTagsWithEmptyArrayMakesNoRequest(): void
    {
        $this->httpClient->expects($this->never())->method('request');

        $this->client->purgeByTags([]);
    }

    public function testPurgeByUrlSendsPurgeMethod(): void
    {
        $url = 'https://cdn.example.com/var/assets/image.jpg';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PURGE',
                $url,
                $this->callback(fn (array $opts) => ($opts['headers']['Fastly-Key'] ?? null) === self::API_TOKEN),
            )
            ->willReturn($this->mockResponse(200));

        $this->client->purgeByUrl($url);
    }

    public function testNon2xxResponseLogsError(): void
    {
        $this->httpClient->method('request')->willReturn($this->mockResponse(503));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('failed'),
                $this->arrayHasKey('status'),
            );

        $this->expectException(\RuntimeException::class);

        $this->client->purgeByTag('asset-1');
    }

    public function testPurgeByTagThrowsRuntimeExceptionOnFastlyHttpError(): void
    {
        // A 401 from Fastly (e.g. revoked/expired API token) must surface as a thrown
        // exception so Symfony Messenger sees the failure and applies its retry policy.
        // Silently logging-and-returning would mark the message successful and the cache
        // would never be purged.
        $this->httpClient->method('request')->willReturn($this->mockResponse(401));

        $this->logger->expects($this->atLeastOnce())->method('error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/401/');

        $this->client->purgeByTag('asset-1');
    }

    public function testPurgeByUrlThrowsRuntimeExceptionOnFastlyHttpError(): void
    {
        // A 503 from Fastly (transient upstream error) must surface as a thrown exception
        // so Messenger's retry policy can re-attempt the purge instead of silently
        // dropping the work.
        $this->httpClient->method('request')->willReturn($this->mockResponse(503));

        $this->logger->expects($this->atLeastOnce())->method('error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/503/');

        $this->client->purgeByUrl('https://cdn.example.com/var/assets/image.jpg');
    }

    public function testExceptionIsLoggedAndRethrown(): void
    {
        $exception = new \RuntimeException('connection refused');

        $this->httpClient->method('request')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('exception'),
                $this->arrayHasKey('error'),
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('connection refused');

        $this->client->purgeByTag('asset-1');
    }

    public function testPurgeByTagSendsCorrectApiTokenHeader(): void
    {
        $capturedOptions = null;

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use (&$capturedOptions) {
                $capturedOptions = $options;

                return $this->mockResponse(200);
            });

        $this->client->purgeByTag('thumb-hero');

        $this->assertSame(self::API_TOKEN, $capturedOptions['headers']['Fastly-Key']);
        $this->assertSame('application/json', $capturedOptions['headers']['Accept']);
    }

    public function testCustomApiBaseUrlIsUsed(): void
    {
        $client = new FastlyPurgeClient(
            $this->httpClient,
            $this->logger,
            self::API_TOKEN,
            self::SERVICE_ID,
            'http://fastly-mock:8080',
        );

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://fastly-mock:8080/service/' . self::SERVICE_ID . '/purge/asset-99',
                $this->callback(fn (array $opts) => ($opts['headers']['Fastly-Key'] ?? null) === self::API_TOKEN),
            )
            ->willReturn($this->mockResponse(200));

        $client->purgeByTag('asset-99');
    }

    public function testCustomApiBaseUrlIsUsedForBatchPurge(): void
    {
        $client = new FastlyPurgeClient(
            $this->httpClient,
            $this->logger,
            self::API_TOKEN,
            self::SERVICE_ID,
            'http://fastly-mock:8080',
        );

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://fastly-mock:8080/service/' . self::SERVICE_ID . '/purge',
                $this->callback(fn (array $opts) => ($opts['headers']['Surrogate-Key'] ?? null) === 'asset-1 asset-2'),
            )
            ->willReturn($this->mockResponse(200));

        $client->purgeByTags(['asset-1', 'asset-2']);
    }

    public function testPurgeByTagUrlEncodesTagWithReservedCharacters(): void
    {
        // Surrogate keys with characters reserved in RFC 3986 path components must be
        // URL-encoded before concatenating into the Fastly purge URL. Without encoding,
        // characters like '/', '?', '#', space can produce invalid URLs or wrong routing.
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.fastly.com/service/' . self::SERVICE_ID . '/purge/' . rawurlencode('weird tag/with spaces'),
                $this->anything(),
            )
            ->willReturn($this->mockResponse(200));

        $this->client->purgeByTag('weird tag/with spaces');
    }

    public function testPurgeByTagDoesNotDoubleEncodeAlreadySafeCharacters(): void
    {
        // Pimcore's normal tags (asset-42, asset-path-1a2b3c, thumb-product-hero) contain
        // only unreserved characters. rawurlencode() must leave them unchanged.
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.fastly.com/service/' . self::SERVICE_ID . '/purge/asset-path-1a2b3c4d5e6f',
                $this->anything(),
            )
            ->willReturn($this->mockResponse(200));

        $this->client->purgeByTag('asset-path-1a2b3c4d5e6f');
    }

    public function testRequestDoesNotMergeScalarOptionsIntoArrays(): void
    {
        // Regression: request() previously used array_merge_recursive() to combine its built-in
        // defaults (headers + http_errors=false) with caller-supplied overrides. With that
        // strategy, a colliding scalar like http_errors=true would be merged into [false, true],
        // and a colliding header like Accept='text/plain' would be merged into ['application/json',
        // 'text/plain'] — both of which Guzzle rejects at runtime. The merger must let scalar
        // overrides REPLACE defaults while still merging header arrays key-by-key.
        //
        // We test the private merger directly via reflection so the assertion targets the
        // actual bug surface, independent of whether request() currently exposes an override path.
        $merger = new \ReflectionMethod($this->client, 'mergeRequestOptions');

        $merged = $merger->invoke($this->client, [
            'http_errors' => true,
            'headers' => [
                'Accept' => 'text/plain',
                'X-Custom' => 'foo',
            ],
        ]);

        // Scalar override must REPLACE the default scalar, not become a list.
        $this->assertIsBool($merged['http_errors']);
        $this->assertTrue($merged['http_errors']);

        // Colliding header value must be replaced; non-colliding header values must be preserved.
        $this->assertIsString($merged['headers']['Accept']);
        $this->assertSame('text/plain', $merged['headers']['Accept']);
        $this->assertSame('foo', $merged['headers']['X-Custom']);

        // Default header that was not overridden must still be present and scalar.
        $this->assertIsString($merged['headers']['Fastly-Key']);
    }
}
