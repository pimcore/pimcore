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

namespace Pimcore\Tests\Unit\Http;

use GuzzleHttp\RequestOptions;
use Pimcore\Http\SsrfProtection;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-qr7g-3424-h9pr (blind SSRF via the email test-send CSS <link> fetch).
 *
 * Only IP literals are exercised so the assertions stay deterministic and do not depend on DNS
 * resolution or network access.
 */
class SsrfProtectionTest extends TestCase
{
    /**
     * URLs that point at private, loopback, link-local, reserved or metadata targets (or use a
     * non-http(s) scheme) must never be fetchable server-side.
     *
     * @dataProvider unsafeUrlProvider
     */
    public function testBlockedUrlsAreRejected(string $url): void
    {
        $this->assertFalse(SsrfProtection::isUrlSafe($url), $url . ' must be rejected');
        $this->assertSame([], SsrfProtection::resolvePublicIps($url), $url . ' must resolve to no public IPs');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unsafeUrlProvider(): array
    {
        return [
            'ipv4 loopback' => ['http://127.0.0.1/style.css'],
            'ipv4 loopback range' => ['http://127.5.5.5/style.css'],
            'cloud metadata endpoint' => ['http://169.254.169.254/latest/meta-data/'],
            'ipv4 link-local range' => ['http://169.254.10.20/x.css'],
            'private 10/8' => ['http://10.0.0.1/x.css'],
            'private 172.16/12' => ['http://172.16.0.1/x.css'],
            'private 192.168/16' => ['http://192.168.1.1/x.css'],
            'unspecified address' => ['http://0.0.0.0/x.css'],
            'ipv6 loopback' => ['http://[::1]/x.css'],
            'ipv6 unique-local' => ['http://[fc00::1]/x.css'],
            'ipv6 link-local' => ['http://[fe80::1]/x.css'],
            'ipv4-mapped loopback' => ['http://[::ffff:127.0.0.1]/x.css'],
            'ipv4-mapped metadata' => ['http://[::ffff:169.254.169.254]/x.css'],
            'non-http scheme ftp' => ['ftp://127.0.0.1/x.css'],
            'file scheme' => ['file:///etc/passwd'],
            'gopher scheme' => ['gopher://127.0.0.1:6379/_INFO'],
            'javascript scheme' => ['javascript:alert(1)'],
            'no host' => ['http:///x.css'],
        ];
    }

    /**
     * Legitimate public targets keep working — the fix must not break embedding remote CSS from a
     * public host.
     *
     * @dataProvider safeUrlProvider
     *
     * @param list<string> $expectedIps
     */
    public function testPublicUrlsAreAllowed(string $url, array $expectedIps): void
    {
        $this->assertTrue(SsrfProtection::isUrlSafe($url), $url . ' must be allowed');
        $this->assertSame($expectedIps, SsrfProtection::resolvePublicIps($url), $url . ' must resolve to public IP');
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function safeUrlProvider(): array
    {
        return [
            'public ipv4 http' => ['http://8.8.8.8/style.css', ['8.8.8.8']],
            'public ipv4 https' => ['https://1.1.1.1/style.css', ['1.1.1.1']],
            'public ipv4 with port' => ['http://8.8.8.8:8080/style.css', ['8.8.8.8']],
            'public ipv6 https' => ['https://[2606:4700:4700::1111]/style.css', ['2606:4700:4700::1111']],
        ];
    }

    /**
     * Whatever the URL looks like, a fetch must never follow a redirect: a public URL that is
     * allowed to redirect could hand the request to an internal host after the fact.
     *
     * @dataProvider requestOptionUrlProvider
     *
     * @param list<string> $publicIps
     */
    public function testRequestOptionsNeverFollowRedirects(string $url, array $publicIps): void
    {
        $options = SsrfProtection::getRequestOptions($url, $publicIps);

        $this->assertArrayHasKey(RequestOptions::ALLOW_REDIRECTS, $options);
        $this->assertFalse($options[RequestOptions::ALLOW_REDIRECTS]);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function requestOptionUrlProvider(): array
    {
        return [
            'ipv4 literal' => ['http://8.8.8.8/style.css', ['8.8.8.8']],
            'ipv6 literal' => ['https://[2606:4700:4700::1111]/style.css', ['2606:4700:4700::1111']],
            'resolved host' => ['https://example.com/style.css', ['93.184.216.34']],
            'nothing validated' => ['https://example.com/style.css', []],
        ];
    }

    /**
     * A host that is already an IP literal is never resolved, so there is nothing to pin. Building
     * a CURLOPT_RESOLVE entry for it is not just pointless: for an IPv6 literal the address' colons
     * collide with cURL's HOST:PORT:ADDRESS parsing, which makes cURL reject the whole entry with
     * "Couldn't parse CURLOPT_RESOLVE entry" (CURLE_COULDNT_RESOLVE_HOST) and abort the transfer —
     * so the CSS of a perfectly valid public IPv6 URL would silently never be embedded.
     *
     * @dataProvider ipLiteralUrlProvider
     *
     * @param list<string> $publicIps
     */
    public function testIpLiteralHostsAreNotPinned(string $url, array $publicIps): void
    {
        $options = SsrfProtection::getRequestOptions($url, $publicIps);

        $this->assertArrayNotHasKey('curl', $options, $url . ' must not carry a CURLOPT_RESOLVE entry');
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function ipLiteralUrlProvider(): array
    {
        return [
            'ipv4 literal' => ['http://8.8.8.8/style.css', ['8.8.8.8']],
            'ipv4 literal with port' => ['http://8.8.8.8:8080/style.css', ['8.8.8.8']],
            'ipv6 literal' => ['https://[2606:4700:4700::1111]/style.css', ['2606:4700:4700::1111']],
            'no validated address' => ['https://example.com/style.css', []],
        ];
    }

    /**
     * All validated addresses of a resolved host have to end up in a *single* CURLOPT_RESOLVE
     * entry: cURL discards the addresses it already holds when a second entry for the same
     * host and port is registered ("RESOLVE example.com:443 - old addresses discarded"), so one
     * entry per address would silently drop every address but the last and leave the fetch
     * without a fallback.
     *
     * @dataProvider resolvedHostProvider
     *
     * @param list<string> $publicIps
     */
    public function testResolvedHostsArePinnedInASingleEntry(string $url, array $publicIps, string $expectedEntry): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('ext-curl is required to pin the connection to a validated address');
        }

        $options = SsrfProtection::getRequestOptions($url, $publicIps);

        $this->assertSame([CURLOPT_RESOLVE => [$expectedEntry]], $options['curl'] ?? null);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>, 2: string}>
     */
    public static function resolvedHostProvider(): array
    {
        return [
            'single address, default http port' => [
                'http://example.com/style.css',
                ['93.184.216.34'],
                'example.com:80:93.184.216.34',
            ],
            'single address, default https port' => [
                'https://example.com/style.css',
                ['93.184.216.34'],
                'example.com:443:93.184.216.34',
            ],
            'explicit port wins over the scheme default' => [
                'http://example.com:8080/style.css',
                ['93.184.216.34'],
                'example.com:8080:93.184.216.34',
            ],
            'multiple addresses share one entry' => [
                'https://example.com/style.css',
                ['93.184.216.34', '2606:4700:4700::1111'],
                'example.com:443:93.184.216.34,2606:4700:4700::1111',
            ],
        ];
    }
}
