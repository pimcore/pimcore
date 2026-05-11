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

        $this->client->purgeByTag('asset-1');
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
}
