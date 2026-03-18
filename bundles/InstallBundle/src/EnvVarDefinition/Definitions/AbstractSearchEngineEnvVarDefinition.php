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

/**
 * Base class for search engine env var definitions (OpenSearch, Elasticsearch).
 *
 * Provides shared validation, connection testing, and protocol detection logic.
 * Subclasses only need to define their specific scheme, env var name, label,
 * section name, and default DSN.
 *
 * @internal
 */
abstract readonly class AbstractSearchEngineEnvVarDefinition implements SearchEngineDefinitionInterface
{
    abstract protected function getScheme(): string;

    abstract protected function getEnvVarName(): string;

    abstract protected function getDefaultDsn(): string;

    abstract protected function getDefaultPort(): int;

    abstract protected function getDefaultSslVerify(): bool;

    public function isRequired(): bool
    {
        return true;
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                $this->getEnvVarName(),
                $this->getLabel() . ' DSN',
                ParameterType::Url,
                defaultValue: $this->getDefaultDsn(),
                description: sprintf(
                    '%s://user:pass@host:port?ssl_verify=bool',
                    $this->getScheme(),
                ),
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            $this->getEnvVarName() => $collectedValues[$this->getEnvVarName()] ?? '',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $dsn = $collectedValues[$this->getEnvVarName()] ?? '';
        $label = $this->getLabel() . ' DSN';

        if ($dsn === '') {
            return [sprintf('%s is required and cannot be empty.', $label)];
        }

        $parsed = parse_url($dsn);
        if ($parsed === false) {
            return [sprintf('%s is not a valid URL.', $label)];
        }

        $scheme = $parsed['scheme'] ?? '';
        if ($scheme !== $this->getScheme()) {
            return [sprintf(
                '%s scheme must be "%s" (got "%s").',
                $label,
                $this->getScheme(),
                $scheme,
            )];
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            return [sprintf('%s must contain a host.', $label)];
        }

        return $this->testConnection($parsed);
    }

    /**
     * @param array<string, mixed> $parsed Result of parse_url()
     *
     * @return list<string> error messages
     */
    protected function testConnection(array $parsed): array
    {
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? $this->getDefaultPort();
        $username = isset($parsed['user']) ? rawurldecode($parsed['user']) : '';
        $password = isset($parsed['pass']) ? rawurldecode($parsed['pass']) : '';

        $query = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        $sslVerify = ($query['ssl_verify'] ?? ($this->getDefaultSslVerify() ? 'true' : 'false')) === 'true';

        $protocol = $sslVerify ? 'https' : 'http';
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
                    '%s connection failed at %s. '
                    . 'Verify the host is reachable and credentials are correct.',
                    $this->getLabel(),
                    $url,
                )];
            }
        } catch (\Exception $e) {
            return [sprintf('%s connection failed: %s', $this->getLabel(), $e->getMessage())];
        }

        return [];
    }
}
