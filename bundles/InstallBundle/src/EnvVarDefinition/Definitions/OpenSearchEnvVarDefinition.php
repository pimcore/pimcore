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
 * OpenSearch search engine definition.
 *
 * Collects a single `PIMCORE_OPENSEARCH_DSN` env var with the format:
 * opensearch://user:pass@host:port?ssl_verify=bool
 *
 * @internal
 */
final readonly class OpenSearchEnvVarDefinition implements SearchEngineDefinitionInterface
{
    public function getKey(): string
    {
        return 'opensearch';
    }

    public function getLabel(): string
    {
        return 'OpenSearch';
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getSectionName(): string
    {
        return 'pimcore/opensearch-client';
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                'PIMCORE_OPENSEARCH_DSN',
                'OpenSearch DSN',
                ParameterType::Url,
                defaultValue: 'opensearch://admin@localhost:9200?ssl_verify=false',
                description: 'opensearch://user:pass@host:port?ssl_verify=bool',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_OPENSEARCH_DSN' => $collectedValues['PIMCORE_OPENSEARCH_DSN'] ?? '',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $dsn = $collectedValues['PIMCORE_OPENSEARCH_DSN'] ?? '';

        $validator = new FormatValidator();
        $validator->requireNonEmpty($dsn, 'OpenSearch DSN');

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        $parsed = parse_url($dsn);
        if ($parsed === false) {
            return ['OpenSearch DSN is not a valid URL.'];
        }

        $scheme = $parsed['scheme'] ?? '';
        if ($scheme !== 'opensearch') {
            return [sprintf('OpenSearch DSN scheme must be "opensearch" (got "%s").', $scheme)];
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            return ['OpenSearch DSN must contain a host.'];
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
        $sslVerify = ($query['ssl_verify'] ?? 'false') === 'true';

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
                    'OpenSearch connection failed at %s. '
                    . 'Verify the host is reachable and credentials are correct.',
                    $url,
                )];
            }
        } catch (\Exception $e) {
            return [sprintf('OpenSearch connection failed: %s', $e->getMessage())];
        }

        return [];
    }
}
