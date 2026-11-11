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

namespace Pimcore\Tests\Unit\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Pimcore;
use Pimcore\Helper\Mail as MailHelper;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-qr7g-3424-h9pr (blind SSRF via the email test-send CSS <link> fetch).
 *
 * Where SsrfProtectionTest covers the URL classification in isolation, this test pins down the
 * fetch boundary itself: that an unsafe <link> never reaches the HTTP client at all, and that a
 * safe one is requested with every egress restriction in place.
 */
class MailSsrfTest extends TestCase
{
    protected bool $cleanupDbInSetup = false;

    private ?object $originalHttpClient = null;

    /**
     * @var array<int, array{request: mixed, options: array<string, mixed>}>
     */
    private array $recordedRequests = [];

    protected function needsDb(): bool
    {
        return false;
    }

    protected function tearDown(): void
    {
        if ($this->originalHttpClient !== null) {
            Pimcore::getContainer()->set('pimcore.http_client', $this->originalHttpClient);
            $this->originalHttpClient = null;
        }

        $this->recordedRequests = [];

        parent::tearDown();
    }

    public function testUnsafeLinkIsNeverRequested(): void
    {
        $this->mockHttpClient(new Response(200, [], 'p { color: red; }'));

        MailHelper::embedAndModifyCss('<link rel="stylesheet" href="http://169.254.169.254/latest/meta-data/"><p>x</p>');

        $this->assertSame([], $this->recordedRequests, 'a <link> pointing at a blocked host must not be fetched');
    }

    public function testSafeLinkIsRequestedWithEgressRestrictions(): void
    {
        $this->mockHttpClient(new Response(200, [], 'p { color: red; }'));

        $result = MailHelper::embedAndModifyCss('<link rel="stylesheet" href="http://8.8.8.8/style.css"><p>x</p>');

        $this->assertCount(1, $this->recordedRequests, 'a <link> pointing at a public host must still be fetched');

        $recorded = $this->recordedRequests[0];
        $this->assertSame('http://8.8.8.8/style.css', (string) $recorded['request']->getUri());
        $this->assertFalse(
            $recorded['options'][RequestOptions::ALLOW_REDIRECTS] ?? null,
            'the fetch must not follow redirects, they could target an internal host'
        );

        $this->assertStringContainsString('color: red', $result, 'the fetched css must still be inlined');
    }

    private function mockHttpClient(Response $response): void
    {
        $stack = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($this->recordedRequests));

        $container = Pimcore::getContainer();
        $this->originalHttpClient = $container->get('pimcore.http_client');
        $container->set('pimcore.http_client', new Client(['handler' => $stack]));
    }
}
