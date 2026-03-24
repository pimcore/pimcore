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

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

final readonly class DatabaseEnvVarDefinition implements EnvVarDefinitionInterface
{
    private const ALLOWED_SCHEMES = ['mysql', 'mysqli', 'pdo-mysql'];

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
                'DATABASE_URL',
                'Database URL',
                ParameterType::Url,
                defaultValue: 'mysql://pimcore:pimcore@127.0.0.1:3306/pimcore',
                description: 'Doctrine DBAL URL (mysql://user:pass@host:port/dbname)',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'DATABASE_URL' => $collectedValues['DATABASE_URL'] ?? '',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $url = $collectedValues['DATABASE_URL'] ?? '';
        $label = 'Database URL';

        $validator = new FormatValidator();
        $validator->requireNonEmpty($url, $label);

        if ($validator->hasErrors()) {
            return $validator->getErrors();
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return [sprintf('%s is not a valid URL.', $label)];
        }

        $scheme = $parsed['scheme'] ?? '';
        if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            return [sprintf(
                '%s scheme must be one of: %s (got "%s").',
                $label,
                implode(', ', self::ALLOWED_SCHEMES),
                $scheme,
            )];
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            return [sprintf('%s must contain a host.', $label)];
        }

        $path = trim($parsed['path'] ?? '', '/');
        if ($path === '') {
            return [sprintf('%s must contain a database name in the path (e.g. mysql://...host/dbname).', $label)];
        }

        return $this->testConnection($url);
    }

    private function testConnection(string $url): array
    {
        try {
            $dsnParser = new DsnParser(ConnectionFactory::DEFAULT_SCHEME_MAP);
            $params = $dsnParser->parse($url);
            $connection = DriverManager::getConnection($params);
            $connection->executeQuery('SELECT 1');
            $connection->close();
        } catch (\Exception $e) {
            return ['Database connection failed: ' . $e->getMessage()];
        }

        return [];
    }
}
