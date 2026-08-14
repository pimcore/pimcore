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

namespace Pimcore\Http;

use GuzzleHttp\RequestOptions;

/**
 * Guards server-side HTTP requests against SSRF: a URL is only considered safe
 * to fetch when it uses an http(s) scheme and its host resolves exclusively to
 * public IP addresses. Private, loopback, link-local, unique-local and other
 * reserved ranges (including the cloud metadata endpoint at 169.254.169.254)
 * are rejected.
 *
 * @internal
 */
final class SsrfProtection
{
    private const ALLOWED_SCHEMES = ['http', 'https'];

    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    /**
     * Resolves the host of the given URL and returns the list of public IP
     * addresses it points to. Returns an empty list when the URL is not safe to
     * fetch, i.e. it uses a disallowed scheme, has no host, cannot be resolved,
     * or resolves to at least one non-public (private/reserved) address. The
     * check is intentionally all-or-nothing: a single blocked address rejects
     * the whole URL, so a host that resolves to both a public and a private
     * address cannot be used to reach the private one.
     *
     * @return list<string>
     */
    public static function resolvePublicIps(string $url): array
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme) || !in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            return [];
        }

        $host = self::getHost($url);
        if ($host === null) {
            return [];
        }

        $ips = self::resolveHost($host);
        if ($ips === []) {
            return [];
        }

        foreach ($ips as $ip) {
            if (!self::isPublicIp($ip)) {
                return [];
            }
        }

        return $ips;
    }

    /**
     * Convenience predicate around {@see self::resolvePublicIps()}.
     */
    public static function isUrlSafe(string $url): bool
    {
        return self::resolvePublicIps($url) !== [];
    }

    /**
     * Builds the Guzzle request options for fetching a URL that {@see self::resolvePublicIps()}
     * has cleared, without re-opening the SSRF vector:
     *
     * - redirects are disabled, a public URL must not be able to hand the request to an internal
     *   host after the fact;
     * - where cURL is available the connection is pinned to the already validated addresses, so a
     *   rebinding DNS response cannot swap in a private target between the validation and the
     *   actual request.
     *
     * Note that the pinning cannot apply when the request runs through a proxy (configured via
     * `httpclient.adapter` or the HTTP(S)_PROXY environment variables): the proxy resolves the
     * host itself, so the validated addresses never come into play there.
     *
     * @param list<string> $publicIps the addresses returned by {@see self::resolvePublicIps()}
     *
     * @return array<string, mixed>
     */
    public static function getRequestOptions(string $url, array $publicIps): array
    {
        $options = [
            RequestOptions::ALLOW_REDIRECTS => false,
        ];

        $host = self::getHost($url);
        if ($publicIps === [] || $host === null || !extension_loaded('curl')) {
            return $options;
        }

        // an IP literal is never resolved, so there is nothing to pin - and passing one as the
        // host of an entry would make cURL reject the entry (an IPv6 address' colons collide
        // with the HOST:PORT:ADDRESS format) and abort the whole transfer
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $options;
        }

        $options['curl'] = [
            // every address has to go into one entry: cURL discards the addresses it already
            // holds as soon as a second entry for the same host and port is registered
            CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, self::getPort($url), implode(',', $publicIps))],
        ];

        return $options;
    }

    /**
     * Returns the URL's host without the brackets of an IPv6 literal (e.g. http://[::1]/), or
     * null when the URL carries no host at all.
     */
    private static function getHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            return null;
        }

        $host = trim($host, '[]');

        return $host === '' ? null : $host;
    }

    private static function getPort(string $url): int
    {
        $port = parse_url($url, PHP_URL_PORT);
        if (is_int($port)) {
            return $port;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return self::DEFAULT_PORTS[$scheme] ?? self::DEFAULT_PORTS['http'];
    }

    /**
     * @return list<string>
     */
    private static function resolveHost(string $host): array
    {
        // an IP literal is used as-is; no name resolution is performed
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = [];

        $ipv4 = gethostbynamel($host);
        if (is_array($ipv4)) {
            $ips = $ipv4;
        }

        $ipv6Records = @dns_get_record($host, DNS_AAAA);
        if (is_array($ipv6Records)) {
            foreach ($ipv6Records as $record) {
                if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private static function isPublicIp(string $ip): bool
    {
        $ip = self::normalizeIp($ip);

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Unwraps an IPv4-mapped IPv6 address (e.g. ::ffff:127.0.0.1) to its plain
     * IPv4 form. filter_var()'s range flags do not otherwise recognize the
     * embedded IPv4 address, which would let a mapped loopback/metadata address
     * bypass the check.
     */
    private static function normalizeIp(string $ip): string
    {
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) !== 16) {
            return $ip;
        }

        // first 10 bytes zero, followed by 0xff 0xff marks an IPv4-mapped address
        if (str_starts_with($packed, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff")) {
            $ipv4 = inet_ntop(substr($packed, 12));
            if ($ipv4 !== false) {
                return $ipv4;
            }
        }

        return $ip;
    }
}
