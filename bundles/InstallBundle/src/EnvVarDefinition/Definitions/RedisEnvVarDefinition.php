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

use Exception;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;
use Redis;

final readonly class RedisEnvVarDefinition implements EnvVarDefinitionInterface
{
    public function getKey(): string
    {
        return 'redis';
    }

    public function getLabel(): string
    {
        return 'Redis (Cache/Sessions)';
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function getSectionName(): string
    {
        return 'pimcore/pimcore';
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                'REDIS_URL',
                'Redis URL',
                ParameterType::Url,
                defaultValue: 'redis://127.0.0.1:6379/0',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'REDIS_URL' => $collectedValues['REDIS_URL']
                ?? 'redis://127.0.0.1:6379/0',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $url = $collectedValues['REDIS_URL'] ?? '';

        $validator = new FormatValidator();
        $validator
            ->requireNonEmpty($url, 'Redis URL')
            ->requireValidUrl($url, 'Redis');

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        return $this->testConnection($url);
    }

    private function testConnection(string $url): array
    {
        if (!extension_loaded('redis')) {
            return [];
        }

        $parsed = parse_url($url);

        try {
            $redis = new Redis();
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = $parsed['port'] ?? 6379;

            $connected = @$redis->connect($host, $port, 3.0);
            if (!$connected) {
                return [sprintf(
                    'Redis connection failed at %s:%d.',
                    $host,
                    $port,
                )];
            }

            $password = $parsed['pass'] ?? null;
            if ($password !== null) {
                $authed = $redis->auth($password);
                if (!$authed) {
                    return ['Redis authentication failed. Check the password in the URL.'];
                }
            }

            $redis->ping();
            $redis->close();
        } catch (Exception $e) {
            return ['Redis connection failed: ' . $e->getMessage()];
        }

        return [];
    }
}
