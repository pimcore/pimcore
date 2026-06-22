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

namespace Pimcore\Cdn;

use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AutoconfigureTag('pimcore.cdn.purge_client', ['provider' => 'fastly'])]
class FastlyPurgeClient implements PurgeClientInterface
{
    /**
     * Fastly rejects batch purges with more than 256 surrogate keys per request;
     * larger sets must be split across requests.
     */
    private const MAX_KEYS_PER_REQUEST = 256;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%pimcore.cdn.fastly.api_token%')]
        private readonly string $apiToken,
        #[Autowire('%pimcore.cdn.fastly.service_id%')]
        private readonly string $serviceId,
        #[Autowire('%pimcore.cdn.fastly.api_base_url%')]
        private readonly string $apiBaseUrl,
    ) {
    }

    public function purgeByTag(string $tag): void
    {
        // Surrogate keys are URL path segments; reserved characters (e.g. '/', ' ') must be
        // percent-encoded so they reach Fastly intact. rawurlencode() is idempotent on the
        // safe character class, so already-clean tags pass through unchanged.
        $this->request('POST', sprintf('%s/service/%s/purge/%s', $this->apiBaseUrl, $this->serviceId, rawurlencode($tag)));
    }

    public function purgeByTags(array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        // Surrogate keys are space-separated in the header; a key containing whitespace
        // would silently split into different keys and purge unrelated cache entries.
        foreach ($tags as $tag) {
            if (preg_match('/\s/', $tag)) {
                throw new InvalidArgumentException(sprintf(
                    'Surrogate key "%s" must not contain whitespace.',
                    $tag
                ));
            }
        }

        foreach (array_chunk($tags, self::MAX_KEYS_PER_REQUEST) as $chunk) {
            $this->request('POST', sprintf('%s/service/%s/purge', $this->apiBaseUrl, $this->serviceId), [
                'headers' => ['Surrogate-Key' => implode(' ', $chunk)],
            ]);
        }
    }

    public function purgeByUrl(string $url): void
    {
        $this->request('PURGE', $url);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws Throwable when the HTTP request fails or Fastly returns a non-2xx response
     */
    private function request(string $method, string $url, array $options = []): void
    {
        try {
            $response = $this->httpClient->request($method, $url, $this->mergeRequestOptions($options));
        } catch (Throwable $e) {
            $this->logger->error(
                'Fastly purge request threw an exception. Method: {method}, URL: {url}, Error: {error}',
                ['method' => $method, 'url' => $url, 'error' => $e->getMessage()]
            );

            throw $e;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->error(
                'Fastly purge request failed. Method: {method}, URL: {url}, Status: {status}',
                ['method' => $method, 'url' => $url, 'status' => $statusCode]
            );

            // Throw so Symfony Messenger sees the failure and applies its retry policy.
            // Returning silently here would mark the message handled and a revoked token
            // or transient 5xx would result in a permanently un-purged cache entry.
            throw new RuntimeException(sprintf(
                'Fastly purge request failed with HTTP %d for %s %s',
                $statusCode,
                $method,
                $url
            ));
        }
    }

    /**
     * Merge caller-supplied Guzzle options with this client's defaults.
     *
     * Uses array_replace() for top-level options so a colliding scalar (e.g. http_errors)
     * is REPLACED rather than promoted to a list — array_merge_recursive() would turn
     * [false] + [true] into [false, true], which Guzzle rejects at runtime. Headers are
     * merged key-by-key with the same replace semantics so callers can override a single
     * default header (e.g. Accept) without dropping the others (e.g. Fastly-Key).
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function mergeRequestOptions(array $overrides): array
    {
        $defaults = [
            'headers' => [
                'Fastly-Key' => $this->apiToken,
                'Accept' => 'application/json',
            ],
            // Disable Guzzle's automatic exception on 4xx/5xx so request() can read the
            // status code, log it, and throw its own RuntimeException. That explicit throw
            // is what lets Symfony Messenger see the failure and apply its retry policy —
            // this flag does not mean "log and continue", it only defers the throw to us.
            'http_errors' => false,
        ];

        $merged = array_replace($defaults, $overrides);
        $merged['headers'] = array_replace($defaults['headers'], $overrides['headers'] ?? []);

        return $merged;
    }
}
