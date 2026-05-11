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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AutoconfigureTag('pimcore.cdn.purge_client', ['provider' => 'fastly'])]
class FastlyPurgeClient implements PurgeClientInterface
{
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

        $this->request('POST', sprintf('%s/service/%s/purge', $this->apiBaseUrl, $this->serviceId), [
            'headers' => ['Surrogate-Key' => implode(' ', $tags)],
        ]);
    }

    public function purgeByUrl(string $url): void
    {
        $this->request('PURGE', $url);
    }

    private function request(string $method, string $url, array $options = []): void
    {
        try {
            $response = $this->httpClient->request($method, $url, $this->mergeRequestOptions($options));

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->error(
                    'Fastly purge request failed. Method: {method}, URL: {url}, Status: {status}',
                    ['method' => $method, 'url' => $url, 'status' => $statusCode]
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Fastly purge request threw an exception. Method: {method}, URL: {url}, Error: {error}',
                ['method' => $method, 'url' => $url, 'error' => $e->getMessage()]
            );

            throw $e;
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
            // Do not throw on 4xx/5xx — we want to log status and continue
            // (Fastly purge is idempotent; transient errors should not crash the worker).
            'http_errors' => false,
        ];

        $merged = array_replace($defaults, $overrides);
        $merged['headers'] = array_replace($defaults['headers'], $overrides['headers'] ?? []);

        return $merged;
    }
}
