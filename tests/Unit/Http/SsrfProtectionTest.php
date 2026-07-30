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
}
