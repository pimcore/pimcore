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
 * Collects host, credentials, and SSL settings from the user,
 * then assembles them into a single `PIMCORE_OPENSEARCH_DSN` env var
 * with the format: opensearch://user:pass@host:port?ssl_verify=bool
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
                'PIMCORE_OPENSEARCH_HOST',
                'Host',
                ParameterType::Url,
                defaultValue: 'https://localhost:9200',
                transient: true,
            ),
            new ConfigParameter(
                'PIMCORE_OPENSEARCH_USERNAME',
                'Username',
                ParameterType::String,
                defaultValue: 'admin',
                transient: true,
            ),
            new ConfigParameter(
                'PIMCORE_OPENSEARCH_PASSWORD',
                'Password',
                ParameterType::Secret,
                required: false,
                transient: true,
            ),
            new ConfigParameter(
                'PIMCORE_OPENSEARCH_SSL_VERIFY',
                'Verify SSL?',
                ParameterType::Boolean,
                defaultValue: 'false',
                transient: true,
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'PIMCORE_OPENSEARCH_DSN' => $this->buildDsn($collectedValues),
        ];
    }

    public function validate(array $collectedValues): array
    {
        $host = $collectedValues['PIMCORE_OPENSEARCH_HOST'] ?? '';

        $validator = new FormatValidator();
        $validator
            ->requireNonEmpty($host, 'OpenSearch host')
            ->requireValidUrl($host, 'OpenSearch host');

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        return $this->testConnection($collectedValues);
    }

    private function buildDsn(array $collectedValues): string
    {
        $host = $collectedValues['PIMCORE_OPENSEARCH_HOST'] ?? 'https://localhost:9200';
        $username = $collectedValues['PIMCORE_OPENSEARCH_USERNAME'] ?? '';
        $password = $collectedValues['PIMCORE_OPENSEARCH_PASSWORD'] ?? '';
        $sslVerify = $collectedValues['PIMCORE_OPENSEARCH_SSL_VERIFY'] ?? 'false';

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
            'opensearch://%s%s:%d?ssl_verify=%s',
            $userinfo,
            $hostname,
            $port,
            $sslVerify,
        );
    }

    private function testConnection(array $collectedValues): array
    {
        $host = $collectedValues['PIMCORE_OPENSEARCH_HOST'] ?? '';
        $username = $collectedValues['PIMCORE_OPENSEARCH_USERNAME'] ?? '';
        $password = $collectedValues['PIMCORE_OPENSEARCH_PASSWORD'] ?? '';
        $sslVerify = ($collectedValues['PIMCORE_OPENSEARCH_SSL_VERIFY'] ?? 'false') === 'true';

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
                    'OpenSearch connection failed at %s. '
                    . 'Verify the host is reachable and credentials are correct.',
                    $host,
                )];
            }
        } catch (\Exception $e) {
            return [sprintf('OpenSearch connection failed: %s', $e->getMessage())];
        }

        return [];
    }
}
