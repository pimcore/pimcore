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
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Validation\FormatValidator;

final readonly class MercureEnvVarDefinition implements EnvVarDefinitionInterface
{
    public function getKey(): string
    {
        return 'mercure';
    }

    public function getLabel(): string
    {
        return 'Mercure (Real-time Updates)';
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getSectionName(): string
    {
        return 'pimcore/studio-backend-bundle';
    }

    public function getParameters(): array
    {
        return [
            new ConfigParameter(
                'MERCURE_JWT_KEY',
                'Mercure JWT Key',
                ParameterType::Secret,
                description: 'Hex-encoded secret key for Mercure JWT tokens',
            ),
            new ConfigParameter(
                'MERCURE_URL',
                'Mercure Public URL',
                ParameterType::Url,
                defaultValue: 'http://localhost/hub',
                description: 'URL clients connect to for real-time updates',
            ),
            new ConfigParameter(
                'MERCURE_SERVER_URL',
                'Mercure Internal Server URL',
                ParameterType::Url,
                defaultValue: 'http://mercure/.well-known/mercure',
                description: 'URL the server uses to publish updates',
            ),
        ];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return [
            'MERCURE_JWT_KEY' => $collectedValues['MERCURE_JWT_KEY'] ?? '',
            'MERCURE_URL' => $collectedValues['MERCURE_URL']
                ?? 'http://localhost/hub',
            'MERCURE_SERVER_URL' => $collectedValues['MERCURE_SERVER_URL']
                ?? 'http://mercure/.well-known/mercure',
        ];
    }

    public function validate(array $collectedValues): array
    {
        $validator = new FormatValidator();

        $jwtKey = $collectedValues['MERCURE_JWT_KEY'] ?? '';
        $publicUrl = $collectedValues['MERCURE_URL'] ?? '';
        $serverUrl = $collectedValues['MERCURE_SERVER_URL'] ?? '';

        // JWT key validation
        // Minimum 32 characters for backward compatibility.
        // HMAC-SHA256 recommends 256-bit (64 hex chars) keys for optimal security,
        // but 32 characters is accepted for BC with existing installations.
        $validator
            ->requireNonEmpty($jwtKey, 'Mercure JWT key')
            ->requireMinLength($jwtKey, 'Mercure JWT key', 32);

        // URL format validation (standardized on parse_url via FormatValidator)
        $validator
            ->requireNonEmpty($publicUrl, 'Mercure public URL')
            ->requireValidUrl($publicUrl, 'Mercure public')
            ->requireNonEmpty($serverUrl, 'Mercure internal server URL')
            ->requireValidUrl($serverUrl, 'Mercure internal server');

        // Note: We do NOT attempt a connection to Mercure here.
        // Mercure may run in a Docker container not reachable from the host
        // during installation. URL format validation is sufficient.

        return $validator->getErrors();
    }
}
