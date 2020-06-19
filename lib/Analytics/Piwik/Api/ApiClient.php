<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Analytics\Piwik\Api;

use GuzzleHttp\Client;
use Pimcore\Analytics\Piwik\Api\Exception\ApiException;
use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\Piwik\Config\ConfigProvider;
use Pimcore\Http\ClientFactory;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiClient
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var SerializerInterface|DecoderInterface
     */
    private $serializer;

    /**
     * @var Client
     */
    private $client;

    public function __construct(
        ConfigProvider $configProvider,
        ClientFactory $clientFactory,
        SerializerInterface $serializer
    ) {
        $this->configProvider = $configProvider;
        $this->clientFactory = $clientFactory;
        $this->serializer = $serializer;
    }

    /**
     * Send a GET request
     *
     * @param array $query   Query params
     * @param array $options Valid options for Guzzle
     *
     * @return array
     */
    public function get(array $query = [], array $options = []): array
    {
        $options['query'] = array_merge($options['query'] ?? [], $query);

        return $this->requestRaw('GET', $options);
    }

    /**
     * Send a request
     *
     * @param string $method Request method
     * @param array $options Valid options for Guzzle
     *
     * @return array
     */
    public function request(string $method, array $options = []): array
    {
        return $this->requestRaw($method, $options);
    }

    /**
     * @param string $method
     * @param array $options
     *
     * @return array
     */
    private function requestRaw(string $method, array $options): array
    {
        $client = $this->getClient();
        $response = $client->request($method, '', $options);

        if (200 !== $response->getStatusCode()) {
            throw new ApiException($response->getReasonPhrase());
        }

        $json = $this->serializer->decode($response->getBody()->getContents(), 'json');

        if (!is_array($json)) {
            throw new ApiException('Unexpected response format');
        }

        if (isset($json['result']) && 'error' === $json['result']) {
            throw ApiException::fromResponse($json['message'], $json);
        }

        return $json;
    }

    private function getClient(): Client
    {
        if (null !== $this->client) {
            return $this->client;
        }

        $config = $this->configProvider->getConfig();

        $options = array_merge([
            'base_uri' => $this->getBaseUri($config),
        ], $config->getApiClientOptions());

        $this->client = $this->clientFactory->createClient($options);

        return $this->client;
    }

    private function getBaseUri(Config $config): string
    {
        if (!$config->isConfigured()) {
            throw new \RuntimeException('Piwik is not configured');
        }

        return $config->getPiwikUrl();
    }
}
