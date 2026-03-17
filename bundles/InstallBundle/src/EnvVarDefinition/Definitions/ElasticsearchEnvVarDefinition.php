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
 * Collects host, credentials, and SSL settings from the user,
 * then assembles them into a single `PIMCORE_ELASTICSEARCH_DSN` env var
 * with the format: elasticsearch://user:pass@host:port?ssl_verify=bool
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
                'PIMCORE_ELASTICSEARCH_HOST',
                'Host',
                ParameterType::Url,
                defaultValue: 'https://localhost:9200',
                transient: true,
            ),
            new ConfigParameter(
                'PIMCORE_ELASTICSEARCH_USERNAME',
                'Username',
                ParameterType::String,
                defaultValue: 'elastic',
                transient: true,
            ),
            new ConfigParameter(
                'PIMCORE_ELASTICSEARCH_PASSWORD',
                'Password',
                ParameterType::Secret,
                required: false,
                transient: true,
            ),
            new ConfigParameter(
                'PIMCORE_ELASTICSEARCH_SSL_VERIFY',
                'Verify SSL?',
                ParameterType::Boolean,
                defaultValue: 'true',
                transient: true,
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_ELASTICSEARCH_DSN' => $this->buildDsn($collectedValues),
        ];
    }

    public function validate(array $collectedValues): array
    {
        $host = $collectedValues['PIMCORE_ELASTICSEARCH_HOST'] ?? '';

        $validator = new FormatValidator();
        $validator
            ->requireNonEmpty($host, 'Elasticsearch host')
            ->requireValidUrl($host, 'Elasticsearch host');

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        return $this->testConnection($collectedValues);
    }

    private function buildDsn(array $collectedValues): string
    {
        $host = $collectedValues['PIMCORE_ELASTICSEARCH_HOST'] ?? 'https://localhost:9200';
        $username = $collectedValues['PIMCORE_ELASTICSEARCH_USERNAME'] ?? '';
        $password = $collectedValues['PIMCORE_ELASTICSEARCH_PASSWORD'] ?? '';
        $sslVerify = $collectedValues['PIMCORE_ELASTICSEARCH_SSL_VERIFY'] ?? 'true';

        $parsed = parse_url($host);
        $hostname = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? 9200;

        $userinfo = '';
        if ($username !== '') {
            $userinfo = rawurlencode($username);
            if ($password !== '') {
                $userinfo .= ':' . rawurlencode($password);
            }
            $userinfo .= '@';
        }

        return sprintf(
            'elasticsearch://%s%s:%d?ssl_verify=%s',
            $userinfo,
            $hostname,
            $port,
            $sslVerify,
        );
    }

    private function testConnection(array $collectedValues): array
    {
        $host = $collectedValues['PIMCORE_ELASTICSEARCH_HOST'] ?? '';
        $username = $collectedValues['PIMCORE_ELASTICSEARCH_USERNAME'] ?? '';
        $password = $collectedValues['PIMCORE_ELASTICSEARCH_PASSWORD'] ?? '';
        $sslVerify = ($collectedValues['PIMCORE_ELASTICSEARCH_SSL_VERIFY'] ?? 'true') === 'true';

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
            $response = @file_get_contents($host, false, $context);

            if ($response === false) {
                return [sprintf(
                    'Elasticsearch connection failed at %s. '
                    . 'Verify the host is reachable and credentials are correct.',
                    $host,
                )];
            }
        } catch (\Exception $e) {
            return [sprintf('Elasticsearch connection failed: %s', $e->getMessage())];
        }

        return [];
    }
}
