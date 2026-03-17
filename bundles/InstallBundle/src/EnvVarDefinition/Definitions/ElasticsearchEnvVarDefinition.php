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

namespace Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

/**
 * Elasticsearch search engine definition.
 *
 * Collects a single `PIMCORE_ELASTICSEARCH_DSN` env var with the format:
 * elasticsearch://user:pass@host:port?ssl_verify=bool
 *
 * @internal
 */
final readonly class ElasticsearchEnvVarDefinition implements SearchEngineDefinitionInterface
{
    public function getKey(): string
    {
        return 'elasticsearch';
    }

    public function getLabel(): string
    {
        return 'Elasticsearch';
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getSectionName(): string
    {
        return 'pimcore/elasticsearch-client';
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                'PIMCORE_ELASTICSEARCH_DSN',
                'Elasticsearch DSN',
                ParameterType::Url,
                defaultValue: 'elasticsearch://elastic@localhost:9200?ssl_verify=true',
                description: 'elasticsearch://user:pass@host:port?ssl_verify=bool',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_ELASTICSEARCH_DSN' => $collectedValues['PIMCORE_ELASTICSEARCH_DSN'] ?? '',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $dsn = $collectedValues['PIMCORE_ELASTICSEARCH_DSN'] ?? '';

        $validator = new FormatValidator();
        $validator->requireNonEmpty($dsn, 'Elasticsearch DSN');

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        $parsed = parse_url($dsn);
        if ($parsed === false) {
            return ['Elasticsearch DSN is not a valid URL.'];
        }

        $scheme = $parsed['scheme'] ?? '';
        if ($scheme !== 'elasticsearch') {
            return [sprintf('Elasticsearch DSN scheme must be "elasticsearch" (got "%s").', $scheme)];
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            return ['Elasticsearch DSN must contain a host.'];
        }

        return $this->testConnection($parsed);
    }

    /**
     * @param array<string, mixed> $parsed Result of parse_url()
     */
    private function testConnection(array $parsed): array
    {
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? 9200;
        $username = isset($parsed['user']) ? rawurldecode($parsed['user']) : '';
        $password = isset($parsed['pass']) ? rawurldecode($parsed['pass']) : '';

        $query = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        $sslVerify = ($query['ssl_verify'] ?? 'true') === 'true';

        $protocol = ($port === 80) ? 'http' : 'https';
        $url = sprintf('%s://%s:%d', $protocol, $host, $port);

        $contextOptions = [
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => $sslVerify,
                'verify_peer_name' => $sslVerify,
            ],
        ];

        if ($username !== '' && $password !== '') {
            $contextOptions['http']['header'] = 'Authorization: Basic '
                . base64_encode($username . ':' . $password);
        }

        try {
            $context = stream_context_create($contextOptions);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return [sprintf(
                    'Elasticsearch connection failed at %s. '
                    . 'Verify the host is reachable and credentials are correct.',
                    $url,
                )];
            }
        } catch (\Exception $e) {
            return [sprintf('Elasticsearch connection failed: %s', $e->getMessage())];
        }

        return [];
    }
}
