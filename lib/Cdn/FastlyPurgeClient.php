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
    private const API_BASE = 'https://api.fastly.com';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%pimcore.cdn.fastly.api_token%')]
        private readonly string $apiToken,
        #[Autowire('%pimcore.cdn.fastly.service_id%')]
        private readonly string $serviceId,
    ) {
    }

    public function purgeByTag(string $tag): void
    {
        $this->request('POST', sprintf('%s/service/%s/purge/%s', self::API_BASE, $this->serviceId, $tag));
    }

    public function purgeByTags(array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $this->request('POST', sprintf('%s/service/%s/purge', self::API_BASE, $this->serviceId), [
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
            $response = $this->httpClient->request($method, $url, array_merge_recursive([
                'headers' => [
                    'Fastly-Key' => $this->apiToken,
                    'Accept' => 'application/json',
                ],
                // Do not throw on 4xx/5xx — we want to log status and continue
                // (Fastly purge is idempotent; transient errors should not crash the worker).
                'http_errors' => false,
            ], $options));

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
}
