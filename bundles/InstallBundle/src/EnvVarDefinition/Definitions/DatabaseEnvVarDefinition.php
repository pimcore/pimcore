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

use Doctrine\DBAL\DriverManager;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

/**
 * @internal
 */
final readonly class DatabaseEnvVarDefinition implements EnvVarDefinitionInterface
{
    public function getKey(): string
    {
        return 'database';
    }

    public function getLabel(): string
    {
        return 'Database (MySQL/MariaDB)';
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getSectionName(): string
    {
        return 'pimcore/pimcore';
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                'DATABASE_HOST',
                'Database Host',
                ParameterType::String,
                defaultValue: '127.0.0.1',
                transient: true,
            ),
            new ConfigParameter(
                'DATABASE_PORT',
                'Database Port',
                ParameterType::Integer,
                defaultValue: '3306',
                transient: true,
            ),
            new ConfigParameter(
                'DATABASE_NAME',
                'Database Name',
                ParameterType::String,
                defaultValue: 'pimcore',
                transient: true,
            ),
            new ConfigParameter(
                'DATABASE_USER',
                'Database User',
                ParameterType::String,
                defaultValue: 'pimcore',
                transient: true,
            ),
            new ConfigParameter(
                'DATABASE_PASSWORD',
                'Database Password',
                ParameterType::Secret,
                required: false,
                transient: true,
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        $password = $collectedValues['DATABASE_PASSWORD'] ?? '';
        $passwordPart = $password !== '' ? ':' . urlencode($password) : '';
        $host = $collectedValues['DATABASE_HOST'] ?? '127.0.0.1';

        // Wrap IPv6 addresses in brackets for URL format compliance
        if (str_contains($host, ':')) {
            $host = '[' . $host . ']';
        }

        return [
            'DATABASE_URL' => sprintf(
                'mysql://%s%s@%s:%s/%s',
                urlencode($collectedValues['DATABASE_USER'] ?? 'pimcore'),
                $passwordPart,
                $host,
                $collectedValues['DATABASE_PORT'] ?? '3306',
                $collectedValues['DATABASE_NAME'] ?? 'pimcore',
            ),
        ];
    }

    public function validate(array $collectedValues): array
    {
        $validator = new FormatValidator();

        $validator
            ->requireNonEmpty($collectedValues['DATABASE_HOST'] ?? '', 'Database host')
            ->requirePortInRange(
                (int) ($collectedValues['DATABASE_PORT'] ?? 0),
                'Database port',
            )
            ->requireNonEmpty($collectedValues['DATABASE_NAME'] ?? '', 'Database name')
            ->requireNonEmpty($collectedValues['DATABASE_USER'] ?? '', 'Database user');

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        return array_merge($validator->getErrors(), $this->testConnection($collectedValues));
    }

    private function testConnection(array $collectedValues): array
    {
        try {
            $dsn = $this->resolveEnvVars($collectedValues)['DATABASE_URL'];
            $connection = DriverManager::getConnection(['url' => $dsn]);
            $connection->executeQuery('SELECT 1');
            $connection->close();
        } catch (\Exception $e) {
            return ['Database connection failed: ' . $e->getMessage()];
        }

        return [];
    }
}
